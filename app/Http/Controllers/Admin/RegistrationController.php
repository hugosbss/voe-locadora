<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientRegistration;
use App\Services\AuditService;
use App\Services\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly AuditService $audit,
    ) {}

    /**
     * Lista os cadastros recebidos pela locadora.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ClientRegistration::class);

        $statusFilter = $request->query('status');

        $registrations = ClientRegistration::query()
            ->when(RegistrationStatus::tryFrom($statusFilter), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.registrations.index', [
            'registrations' => $registrations,
            'statuses' => RegistrationStatus::cases(),
            'currentStatus' => $statusFilter,
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

        $validated = $request->validate([
            'status' => ['required', Rule::enum(RegistrationStatus::class)],
        ]);

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

        return back()->with('success', 'Status atualizado.');
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
}
