<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ThrottleCadastroSubmissions;
use App\Http\Requests\StoreClientRegistrationRequest;
use App\Services\CepService;
use App\Services\ClientRegistrationService;
use App\Services\ContractStorageService;
use App\Services\NewRegistrationNotifier;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

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
            'Contrato e assinatura',
        ];

        return view('client-registrations.create', [
            'steps' => $steps,
            'states' => config('locations.states'),
            'cnhCategories' => config('locations.cnh_categories'),
        ]);
    }

    /**
     * Serve o modelo do contrato (somente leitura) para o cliente ler e
     * assinar. Rota controlada pela aplicação: nunca expõe o caminho do
     * storage nem usa o webroot público.
     */
    public function contractView(ContractStorageService $contractStorage): Response
    {
        $response = $contractStorage->response((string) config('contracts.template_path'));

        $response->headers->set('Content-Disposition', 'inline; filename="contrato.pdf"');
        $response->headers->set('Cache-Control', 'public, max-age=3600');

        return $response;
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
    public function store(
        StoreClientRegistrationRequest $request,
        NewRegistrationNotifier $notifier,
        RateLimiter $limiter,
    ): RedirectResponse {
        try {
            $registration = $this->registrationService->create(
                $request->validated(),
                (string) $request->ip(),
            );

            // Cadastro realmente criado: conta para o limite de criações reais
            // (anti-spam), enquanto erros de validação ficam fora dele.
            $limiter->hit(
                ThrottleCadastroSubmissions::createdKey((string) $request->ip()),
                ThrottleCadastroSubmissions::createdDecaySeconds(),
            );
        } catch (RuntimeException $e) {
            // Arquivo legitimamente inválido que escapou da validação, ou
            // falha inesperada no reprocessamento: resposta genérica. A
            // exceção da assinatura/contrato recebe destaque no campo.
            Log::warning('Registration storage failed', ['kind' => get_class($e)]);

            $field = str_contains($e->getMessage(), 'assinatura') || str_contains($e->getMessage(), 'contrato')
                ? 'contract_signature'
                : 'documentos';

            return back()
                ->withErrors([$field => 'Não foi possível processar a assinatura do contrato. Tente novamente.'])
                ->withInput($request->except(['cnh_front_file', 'cnh_back_file', 'proof_of_residence_file', 'selfie_file', 'contract_signature']));
        }

        // Notifica os administradores. Qualquer falha de SMTP é registrada
        // em log e jamais impede ou desfaz a criação do cadastro.
        try {
            $notifier->notifyAdmins($registration);
        } catch (Throwable $e) {
            Log::error('Failed to notify admins about new registration', [
                'registration_uuid' => $registration->uuid,
                'error' => $e->getMessage(),
            ]);
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
