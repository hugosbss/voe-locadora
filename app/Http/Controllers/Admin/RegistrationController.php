<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Exceptions\QuotaUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClientRegistrationRequest;
use App\Models\ClientRegistration;
use App\Services\AuditService;
use App\Services\ContractStorageService;
use App\Services\DocumentStorageService;
use App\Services\QuotaAvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly ContractStorageService $contractStorage,
        private readonly AuditService $audit,
        private readonly QuotaAvailabilityService $quotaAvailability,
    ) {}

    /**
     * Lista os cadastros recebidos pela locadora.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('viewAny', ClientRegistration::class);

        // Consome o gatilho do toast de status: "status_updated" funciona como
        // entrada única após alterar o status. Ao chegar aqui, remove-se o
        // parâmetro da URL (e, por consequência, da paginação e dos filtros);
        // a mensagem é exibida uma única vez através do flash de sessão.
        if ($request->has('status_updated')) {
            return redirect()
                ->route('admin.registrations.index', $request->except('status_updated'))
                ->with('success', 'Status atualizado com sucesso.');
        }

        $statusFilter = $request->query('status');
        $status = is_string($statusFilter) ? RegistrationStatus::tryFrom($statusFilter) : null;

        $dateFrom = $request->query('date_from');
        if (! is_string($dateFrom) || ! Carbon::hasFormat($dateFrom, 'Y-m-d')) {
            $dateFrom = null;
        }

        $name = $request->query('name');
        $name = is_string($name) ? trim(mb_substr($name, 0, 100)) : '';

        $registrations = ClientRegistration::query()
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->when($dateFrom, fn ($query, $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($name !== '', fn ($query) => $query->where('full_name', 'like', '%'.$name.'%'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'statuses' => RegistrationStatus::cases(),
            'currentStatus' => is_string($statusFilter) ? $statusFilter : null,
            'currentDateFrom' => $dateFrom,
            'currentName' => $name,
            'hasActiveFilters' => $status !== null || $dateFrom !== null || $name !== '',
        ]);
    }

    /**
     * Exibe os dados completos e os documentos do cadastro.
     */
    public function show(ClientRegistration $registration): View
    {
        $this->authorize('view', $registration);

        $registration->load(['vehicle', 'quotaType']);

        $this->audit->log(
            AuditAction::ViewRegistration,
            ['registration_uuid' => $registration->uuid],
            $registration,
        );

        return view('admin.registrations.show', [
            'registration' => $registration,
            'statuses' => RegistrationStatus::cases(),
        ]);
    }

    public function edit(ClientRegistration $registration): View
    {
        $this->authorize('update', $registration);

        return view('admin.registrations.edit', [
            'registration' => $registration,
        ]);
    }

    public function update(UpdateClientRegistrationRequest $request, ClientRegistration $registration): RedirectResponse
    {
        $this->authorize('update', $registration);

        $validated = $request->validated();
        $newPaths = [];
        $oldPaths = [];

        try {
            foreach ([
                'vehicle_pickup_photo' => 'vehicle_pickup',
                'vehicle_delivery_photo' => 'vehicle_delivery',
            ] as $field => $document) {
                if (! $request->hasFile($field)) {
                    continue;
                }

                $newPaths[$document] = $this->documentStorage->store(
                    $request->file($field),
                    (string) $registration->uuid,
                    $document,
                );
                $oldPaths[$document] = $registration->documentPath($document);
            }

            $registration->fill([
                'vehicle_observation' => $validated['vehicle_observation'] ?? null,
            ]);

            foreach ($newPaths as $document => $path) {
                $registration->{$document === 'vehicle_pickup'
                    ? 'vehicle_pickup_photo_path'
                    : 'vehicle_delivery_photo_path'} = $path;
            }

            $registration->save();
        } catch (Throwable $exception) {
            foreach ($newPaths as $path) {
                $this->documentStorage->delete($path);
            }

            throw $exception;
        }

        foreach ($oldPaths as $path) {
            if (is_string($path)) {
                $this->documentStorage->delete($path);
            }
        }

        $this->audit->log(
            AuditAction::UpdateRegistration,
            [
                'registration_uuid' => $registration->uuid,
                'pickup_photo_updated' => array_key_exists('vehicle_pickup', $newPaths),
                'delivery_photo_updated' => array_key_exists('vehicle_delivery', $newPaths),
            ],
            $registration,
        );

        $message = match (array_keys($newPaths)) {
            ['vehicle_pickup'] => 'Foto da retirada atualizada com sucesso.',
            ['vehicle_delivery'] => 'Foto da entrega atualizada com sucesso.',
            ['vehicle_pickup', 'vehicle_delivery'], ['vehicle_delivery', 'vehicle_pickup'] => 'Fotos do veículo atualizadas com sucesso.',
            default => 'Cadastro atualizado com sucesso.',
        };

        return redirect()
            ->route('admin.registrations.show', $registration)
            ->with('success', $message);
    }

    /**
     * Atualiza o status do cadastro (Novo, Em análise, Aprovado, Reprovado).
     */
    public function updateStatus(Request $request, ClientRegistration $registration): RedirectResponse
    {
        $this->authorize('updateStatus', $registration);

        $validated = $request->validate(
            [
                'status' => ['required', Rule::enum(RegistrationStatus::class)],
            ],
            [
                'status.required' => 'Selecione um status válido.',
                'status.enum' => 'O status selecionado é inválido.',
            ],
        );

        $previous = $registration->status;
        $next = RegistrationStatus::from($validated['status']);

        // Reativar um cadastro reprovado faz ele voltar a ocupar vaga:
        // revalida a disponibilidade no período sob lock (ignorando o
        // próprio cadastro) para não gerar overbooking.
        if (! $previous->consumesQuota() && $next->consumesQuota()) {
            $refusal = $this->revalidateQuotaOnReactivate($registration);

            if ($refusal !== null) {
                return $refusal;
            }
        }

        $registration->status = $next;
        $registration->save();

        $this->audit->log(
            AuditAction::UpdateStatus,
            [
                'registration_uuid' => $registration->uuid,
                'previous' => $previous->value,
                'new' => $next->value,
            ],
            $registration,
        );

        // Sucesso: a página de detalhes mantém o toast e, após ~2s, é
        // levada de volta à listagem (flags consumidas pelo próprio layout).
        return back()
            ->with('success', 'Status atualizado com sucesso.')
            ->with('status_updated_redirect', true);
    }

    /**
     * Revalida a cota ao sair de "reprovado" para um status consumidor.
     * Retorna um redirect com erro amigável quando não há vaga; null quando
     * a transição pode seguir.
     */
    private function revalidateQuotaOnReactivate(ClientRegistration $registration): ?RedirectResponse
    {
        if (! $registration->vehicle_id || ! $registration->quota_type_id || ! $registration->start_date || ! $registration->end_date) {
            return null;
        }

        try {
            DB::transaction(function () use ($registration): void {
                $this->quotaAvailability->assertCanReserve(
                    (int) $registration->vehicle_id,
                    (int) $registration->quota_type_id,
                    Carbon::parse($registration->start_date),
                    Carbon::parse($registration->end_date),
                    $registration->getKey(),
                    lock: true,
                );
            });
        } catch (QuotaUnavailableException $e) {
            return back()->withErrors([
                'status' => 'Não é possível reativar este cadastro: '.$e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Serve um documento privado, somente para usuários autorizados sobre
     * o cadastro específico.
     *
     * Ordem de verificação: autenticação → autorização → pertencimento do
     * documento ao cadastro → whitelist do tipo → auditoria → entrega.
     */
    public function photo(ClientRegistration $registration, string $document): Response
    {
        $this->authorize('view', $registration);

        if (! array_key_exists($document, ClientRegistration::DOCUMENTS)) {
            abort(404);
        }

        $path = $registration->documentPath($document);

        // Nunca revela existência: respostas idênticas (404) para
        // documento inexistente ou que não pertença a este cadastro.
        if (! $path || ! $registration->ownsStoredDocument($document, $path) || ! $this->documentStorage->isSafePath($path)) {
            abort(404);
        }

        $this->audit->log(
            AuditAction::ViewDocument,
            [
                'registration_uuid' => $registration->uuid,
                'document' => $document,
            ],
            $registration,
        );

        $response = $this->documentStorage->response($path);

        // Impede cache compartilhado e navegação indevida.
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Serve o contrato assinado (visualização inline), somente para
     * usuários autorizados sobre o cadastro específico.
     */
    public function contract(ClientRegistration $registration): Response
    {
        $this->authorize('viewContract', $registration);

        $path = $registration->contract_signed_pdf_path;

        if (! is_string($path) || ! $registration->ownsStoredContractFile($path) || ! $this->contractStorage->isSafeSignedPath($path)) {
            abort(404);
        }

        $this->audit->log(
            AuditAction::ViewContract,
            ['registration_uuid' => $registration->uuid, 'mode' => 'view'],
            $registration,
        );

        $response = $this->contractStorage->response($path, null, ['Content-Disposition' => 'inline']);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Download do contrato assinado (autorização + auditoria).
     */
    public function contractDownload(ClientRegistration $registration): Response
    {
        $this->authorize('viewContract', $registration);

        $path = $registration->contract_signed_pdf_path;

        if (! is_string($path) || ! $registration->ownsStoredContractFile($path) || ! $this->contractStorage->isSafeSignedPath($path)) {
            abort(404);
        }

        $this->audit->log(
            AuditAction::ViewContract,
            ['registration_uuid' => $registration->uuid, 'mode' => 'download'],
            $registration,
        );

        $response = $this->contractStorage->response(
            $path,
            'contrato-assinado.pdf',
            ['Content-Disposition' => 'attachment; filename="contrato-assinado.pdf"'],
        );

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }

    /**
     * Serve a imagem da assinatura (PNG) do contrato assinado.
     */
    public function contractSignature(ClientRegistration $registration): Response
    {
        $this->authorize('viewContract', $registration);

        $path = $registration->contract_signature_path;

        if (! is_string($path) || ! $registration->ownsStoredContractFile($path) || ! $this->contractStorage->isSafeSignedPath($path)) {
            abort(404);
        }

        $this->audit->log(
            AuditAction::ViewContract,
            ['registration_uuid' => $registration->uuid, 'mode' => 'signature'],
            $registration,
        );

        $response = $this->contractStorage->response($path);

        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
