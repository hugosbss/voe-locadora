<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRegistrationRequest;
use App\Services\CepService;
use App\Services\ClientRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientRegistrationController extends Controller
{
    public function __construct(
        private readonly ClientRegistrationService $registrationService,
    ) {}

    /**
     * Exibe o formulário de cadastro dividido em etapas.
     */
    public function create(): View
    {
        $steps = [
            'Dados pessoais',
            'Endereço',
            'CNH',
            'Fotos',
            'Selfie',
            'Enviar',
        ];

        return view('client-registrations.create', [
            'steps' => $steps,
            'states' => config('locations.states'),
            'cnhCategories' => config('locations.cnh_categories'),
        ]);
    }

    /**
     * Busca o endereço automaticamente a partir do CEP.
     */
    public function lookupCep(Request $request, CepService $cepService): JsonResponse
    {
        $cep = $request->validate(['cep' => ['required', 'string', 'max:9']])['cep'];

        $address = $cepService->find($cep);

        if (! $address) {
            return response()->json(['error' => 'CEP não encontrado.'], 404);
        }

        return response()->json($address);
    }

    /**
     * Valida e armazena o cadastro do cliente.
     */
    public function store(StoreClientRegistrationRequest $request): RedirectResponse
    {
        $registration = $this->registrationService->create($request->validated());

        return redirect()
            ->route('client-registrations.success')
            ->with('registered_cpf', $registration->cpf);
    }

    /**
     * Tela de confirmação após o envio do cadastro.
     */
    public function success(): View
    {
        return view('client-registrations.success');
    }
}
