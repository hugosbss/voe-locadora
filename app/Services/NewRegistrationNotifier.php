<?php

namespace App\Services;

use App\Enums\AdminRole;
use App\Mail\NewRegistrationMail;
use App\Models\ClientRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Notifica todos os administradores ativos sobre um novo cadastro.
 *
 * Falhas de envio (ex.: SMTP indisponível) são registradas em log e nunca
 * impedem a criação do cadastro: uma falha em um destinatário não bloqueia
 * os demais.
 */
class NewRegistrationNotifier
{
    /**
     * @return int quantidade de e-mails enviados
     */
    public function notifyAdmins(ClientRegistration $registration): int
    {
        $sent = 0;

        /** @var User $admin */
        foreach (User::query()->where('role', AdminRole::Admin->value)->get() as $admin) {
            try {
                Mail::to($admin)->send(new NewRegistrationMail($registration));
                $sent++;
            } catch (Throwable $e) {
                Log::error('Failed to notify admin about new registration', [
                    'registration_uuid' => $registration->uuid,
                    'user_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
