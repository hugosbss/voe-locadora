<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientRegistration;
use App\Services\AuditService;
use App\Services\ContractStorageService;
use App\Services\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly ContractStorageService $contractStorage,
        private readonly AuditService $audit,
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
