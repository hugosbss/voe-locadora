<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\FacialStatus;
use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orquestra a criação de um cadastro de cliente, incluindo a
 * armazenamento dos arquivos em área privada e o registro do consentimento.
 *
 * Campos administrativos (status, facial_status, caminhos, consentimento,
 * uuid) são definidos exclusivamente aqui, nunca por dados do cliente.
 */
class ClientRegistrationService
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly FacialValidationService $facialValidation,
        private readonly AuditService $audit,
    ) {}

    /**
     * Cria o cadastro do cliente com os dados validados.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ClientRegistration
    {
        $uuid = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($data, $uuid): ClientRegistration {
                $registration = new ClientRegistration;
                $registration->uuid = $uuid;

                foreach (ClientRegistration::DOCUMENTS as $document => $label) {
                    $file = $data["{$document}_file"] ?? null;

                    $registration->{$document.'_path'} = $file
                        ? $this->documentStorage->store($file, $uuid, $document)
                        : null;
                }

                $registration->fill($this->mapFormData($data));

                // Valores controlados pelo servidor.
                $registration->status = RegistrationStatus::Novo;
                $registration->facial_status = FacialStatus::Pending;

                $registration->veracity_declaration_accepted = true;
                $registration->veracity_declaration_accepted_at = now();
                $registration->privacy_policy_accepted = true;
                $registration->privacy_policy_accepted_at = now();
                $registration->privacy_policy_version = (string) config('privacy.version');

                $registration->save();

                $this->audit->log(
                    AuditAction::ConsentRecorded,
                    [
                        'registration_uuid' => $uuid,
                        'policy_version' => $registration->privacy_policy_version,
                    ],
                    $registration,
                );

                return $registration;
            });
        } catch (RuntimeException $e) {
            // Se o armazenamento falhar no meio do caminho, remove os
            // arquivos já gravados; a transação do banco já foi revertida.
            $this->cleanupOnFailure($uuid);

            throw $e;
        }
    }

    /**
     * Remove dados temporários de arquivos que não devem ir para o banco.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapFormData(array $data): array
    {
        unset(
            $data['cnh_front_file'],
            $data['cnh_back_file'],
            $data['proof_of_residence_file'],
            $data['selfie_file'],
            $data['veracity_declaration_accepted'],
            $data['privacy_policy_accepted'],
        );

        return $data;
    }

    /**
     * Exclui o diretório do cadastro caso a criação falhe no meio do caminho.
     */
    public function cleanupOnFailure(string $registrationUuid): void
    {
        $this->documentStorage->deleteRegistrationDirectory($registrationUuid);
    }
}
