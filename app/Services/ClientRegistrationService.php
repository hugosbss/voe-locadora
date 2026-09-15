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
 * armazenamento dos arquivos em área privada, o consentimento e a
 * assinatura digital do contrato.
 *
 * Campos administrativos (status, facial_status, caminhos, consentimento,
 * contrato, uuid) são definidos exclusivamente aqui, nunca por dados do
 * cliente.
 */
class ClientRegistrationService
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly ContractStorageService $contractStorage,
        private readonly ContractPdfService $contractPdf,
        private readonly FacialValidationService $facialValidation,
        private readonly AuditService $audit,
    ) {}

    /**
     * Cria o cadastro do cliente com os dados validados.
     *
     * A criação definitiva (row no banco) só acontece após a assinatura
     * ser validada e armazenada e o PDF assinado ser gerado. Tudo roda
     * sob a mesma transação; qualquer falha destrói os arquivos já
     * gravados e reverte a transação — nunca fica cadastro sem contrato
     * (nem contrato órfão).
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?string $signerIp = null): ClientRegistration
    {
        $uuid = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($data, $uuid, $signerIp): ClientRegistration {
                $registration = new ClientRegistration;
                $registration->uuid = $uuid;

                foreach (ClientRegistration::DOCUMENTS as $document => $label) {
                    $file = $data["{$document}_file"] ?? null;

                    $registration->{$document.'_path'} = $file
                        ? $this->documentStorage->store($file, $uuid, $document)
                        : null;
                }

                // Contrato: gera os arquivos ANTES de salvar o cadastro.
                // Se qualquer passo falhar, a exceção propaga e o catch
                // remove os arquivos já gravados; a transação reverte.
                $signaturePath = $this->contractStorage->storeSignature(
                    (string) $data['contract_signature'],
                    $uuid,
                );

                $signedAt = now('America/Sao_Paulo');

                $contractPath = $this->contractPdf->generate(
                    $uuid,
                    $signaturePath,
                    (string) $data['contract_signer_name'],
                    (string) $data['cpf'],
                    $signedAt->format('d/m/Y H:i'),
                );

                $registration->fill($this->mapFormData($data));

                // Valores controlados pelo servidor.
                $registration->status = RegistrationStatus::Novo;
                $registration->facial_status = FacialStatus::Pending;

                $registration->veracity_declaration_accepted = true;
                $registration->veracity_declaration_accepted_at = now();
                $registration->privacy_policy_accepted = true;
                $registration->privacy_policy_accepted_at = now();
                $registration->privacy_policy_version = (string) config('privacy.version');

                $registration->contract_signed = true;
                // O instante é normalizado para UTC: as colunas `datetime`
                // são gravadas como relógio do app (UTC) e relidas como UTC.
                $registration->contract_signed_at = $signedAt->clone()->setTimezone('UTC');
                $registration->contract_version = (string) config('contracts.version');
                $registration->contract_signed_pdf_path = $contractPath;
                $registration->contract_signature_path = $signaturePath;
                $registration->contract_signer_name = $data['contract_signer_name'];
                $registration->contract_signer_ip = $signerIp ?? app('request')->ip();

                $registration->save();

                $this->audit->log(
                    AuditAction::ConsentRecorded,
                    [
                        'registration_uuid' => $uuid,
                        'policy_version' => $registration->privacy_policy_version,
                        'contract_version' => $registration->contract_version,
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
            $data['contract_signature'],
            $data['contract_signer_name'],
            $data['contract_accepted'],
        );

        return $data;
    }

    /**
     * Exclui os diretórios do cadastro e do contrato caso a criação falhe
     * no meio do caminho.
     */
    public function cleanupOnFailure(string $registrationUuid): void
    {
        $this->documentStorage->deleteRegistrationDirectory($registrationUuid);
        $this->contractStorage->deleteRegistrationDirectory($registrationUuid);
    }
}
