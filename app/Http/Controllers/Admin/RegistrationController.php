<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\ClientRegistration;
use App\Services\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class RegistrationController extends Controller
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
    ) {}

    /**
     * Lista os cadastros recebidos pela locadora.
     */
    public function index(Request $request): View
    {
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
        $validated = $request->validate([
            'status' => ['required', 'in:novo,em_analise,aprovado,reprovado'],
        ]);

        $registration->status = RegistrationStatus::from($validated['status']);
        $registration->save();

        return back()->with('success', 'Status atualizado com sucesso.');
    }

    /**
     * Serve um documento privado, somente para usuários autenticados.
     */
    public function photo(ClientRegistration $registration, string $document): Response
    {
        abort_unless(array_key_exists($document, ClientRegistration::DOCUMENTS), 404);

        $path = $registration->{$document.'_path'};

        abort_unless($path, 404);

        return $this->documentStorage->response($path);
    }
}
