<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRegistrationRequest;
use App\Services\CepService;
use App\Services\ClientRegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;

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
            'Documentos',
            'Selfie',
            'Revisão',
        ];

        return view('client-registrations.create', [
            'steps' => $steps,
            'states' => config('locations.states'),
            'cnhCategories' => config('locations.cnh_categories'),
        ]);
    }

    /**
     * Busca o endereço automaticamente a partir do CEP.
     *
     * Resposta sempre controlada pela aplicação: nunca repassa erros,
     * timeout ou malformações da API externa. A rota possui rate limiting.
     */
    public function lookupCep(Request $request, CepService $cepService): JsonResponse
    {
        $validated = $request->validate(['cep' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/']]);

        $address = $cepService->find($validated['cep']);

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
        try {
            $registration = $this->registrationService->create($request->validated());
        } catch (RuntimeException $e) {
            // Arquivo legitimamente inválido que escapou da validação,
            // ou falha inesperada no reprocessamento: resposta genérica.
            Log::warning('Registration storage failed', ['kind' => get_class($e)]);

            return back()
                ->withErrors(['documentos' => 'Não foi possível processar os arquivos enviados. Tente novamente.'])
                ->withInput($request->except(['cnh_front_file', 'cnh_back_file', 'proof_of_residence_file', 'selfie_file']));
        }

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
