<?php

namespace App\Http\Controllers\Public;

use App\Exceptions\ContractSignatureNotAllowedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractSignatureRequest;
use App\Models\ClientRegistration;
use App\Services\ClientRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Assinatura do contrato em um cadastro já existente.
 *
 * O link usa o UUID do cadastro (público e imprevisível) e é compartilhado
 * pelo administrador depois que a locadora aprova o cadastro. A tela é
 * SOMENTE LEITURA: nenhum dado do cadastro pode ser alterado por aqui; o
 * único dado aceito é a assinatura, gravada no próprio cadastro.
 */
class ContractSignatureController extends Controller
{
    public function __construct(
        private readonly ClientRegistrationService $registrationService,
    ) {}

    /**
     * Tela de assinatura: dados do titular para conferência, contrato para
     * leitura e canvas da assinatura.
     *
     * Links fora das condições (cadastro não aprovado, assinatura já feita ou
     * UUID inexistente) nunca renderizam o formulário.
     */
    public function create(ClientRegistration $registration): View|Response
    {
        if ($registration->hasSignedContract()) {
            return response()->view('client-registrations.signature-unavailable', [
                'title' => 'Contrato já assinado',
                'message' => 'A assinatura deste contrato já foi registrada e não pode ser feita novamente. Se precisar de um novo link, fale com a VCA.',
            ]);
        }

        if (! $registration->isApproved()) {
            return response()->view('client-registrations.signature-unavailable', [
                'title' => 'Link ainda indisponível',
                'message' => 'Este link de assinatura não está disponível no momento. Fale com a VCA para receber um novo link assim que o cadastro for aprovado.',
            ], Response::HTTP_FORBIDDEN);
        }

        $registration->loadMissing(['vehicle', 'quotaType']);

        return view('client-registrations.signature', [
            'registration' => $registration,
            'contractUrl' => route('client-registrations.contract'),
        ]);
    }

    /**
     * Registra a assinatura no cadastro original.
     */
    public function store(
        StoreContractSignatureRequest $request,
        ClientRegistration $registration,
    ): RedirectResponse {
        try {
            $this->registrationService->signContract(
                $registration,
                (string) $request->validated('contract_signature'),
                (string) $request->ip(),
            );
        } catch (ContractSignatureNotAllowedException $e) {
            // Link reutilizado ou cadastro que saiu das condições: volta para
            // a tela de assinatura, que explica a situação ao titular.
            return redirect()->route('client-registrations.signature', $registration);
        } catch (RuntimeException $e) {
            // Assinatura ilegível ou falha na geração do PDF: o cadastro não
            // foi alterado e os arquivos da tentativa foram removidos.
            Log::warning('Contract signature failed', [
                'kind' => get_class($e),
                'registration_uuid' => $registration->uuid,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withErrors(['contract_signature' => 'Não foi possível registrar sua assinatura. Tente novamente.']);
        }

        return redirect()->route('client-registrations.signature-done');
    }
}
