<?php

namespace App\Services;

use App\Enums\FacialStatus;
use App\Models\ClientRegistration;
use Illuminate\Support\Facades\Log;

/**
 * Etapa de validação facial.
 *
 * Hoje a selfie é capturada e armazenada para análise manual pela
 * equipe. Este serviço é o ponto de integração preparado para a
 * comparação automática futura entre a selfie e a foto da CNH.
 */
class FacialValidationService
{
    /**
     * Marca o cadastro como aguardando análise facial.
     */
    public function markAsPending(ClientRegistration $registration): ClientRegistration
    {
        $registration->facial_status = FacialStatus::Pending;
        $registration->save();

        return $registration;
    }

    /**
     * Ponto de integração futura.
     *
     * Deve comparar a selfie do cliente com a foto da CNH e retornar
     * um FacialStatus. Enquanto o provedor de biometria não é
     * contratado, mantém o cadastro como aguardando análise.
     */
    public function verify(ClientRegistration $registration): FacialStatus
    {
        Log::info(
            'Facial validation requested (manual analysis expected).',
            ['registration_id' => $registration->id]
        );

        return FacialStatus::Pending;
    }
}
