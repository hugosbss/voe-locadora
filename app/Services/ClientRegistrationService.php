<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\FacialStatus;
use App\Enums\RegistrationStatus;
use App\Exceptions\ContractSignatureNotAllowedException;
use App\Models\ClientRegistration;
use App\Models\QuotaType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Orquestra a criação de um cadastro de cliente e a assinatura posterior do
 * contrato, incluindo o armazenamento dos arquivos em área privada, o
 * consentimento e a assinatura digital.
 *
 * A criação do cadastro NÃO exige assinatura: o cliente envia os dados, o
 * aceite dos termos é registrado e o contrato preenchido (página 1) é gerado.
 * A assinatura é um passo posterior, feito pelo próprio titular através do
 * link público de assinatura (ver `signContract`), e grava a assinatura no
 * MESMO cadastro — nunca um novo registro.
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
        private readonly QuotaAvailabilityService $quotaAvailability,
    ) {}

    /**
     * Cria o cadastro do cliente com os dados validados.
     *
     * A criação definitiva (row no banco) só acontece após os arquivos
     * enviados serem validados e gravados. Tudo roda sob a mesma transação;
     * qualquer falha destrói os arquivos já gravados e reverte a transação.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ClientRegistration
    {
        $uuid = (string) Str::uuid();

        try {
            return DB::transaction(function () use ($data, $uuid): ClientRegistration {
                // Revalida a disponibilidade sob lock ANTES de gravar
                // qualquer arquivo: protege contra a corrida entre a
                // validação do formulário e a criação (overbooking).
                if (! empty($data['vehicle_id']) && ! empty($data['quota_type_id'])) {
                    $this->quotaAvailability->assertCanReserve(
                        (int) $data['vehicle_id'],
                        (int) $data['quota_type_id'],
                        Carbon::parse($data['start_date']),
                        Carbon::parse($data['end_date']),
                        lock: true,
                    );
                }

                $registration = new ClientRegistration;
                $registration->uuid = $uuid;

                foreach (ClientRegistration::UPLOAD_DOCUMENTS as $document => $label) {
                    $file = $data["{$document}_file"] ?? null;

                    $registration->{$document.'_path'} = $file
                        ? $this->documentStorage->store($file, $uuid, $document)
                        : null;
                }

                // Dados pessoais preenchidos antes do contrato: o PDF usa o
                // model para montar a página 1 e o bloco de assinatura.
                $registration->fill($this->mapFormData($data));

                if (! empty($data['vehicle_id']) && ! empty($data['quota_type_id'])) {
                    $quotaType = QuotaType::query()->find($data['quota_type_id']);
                    $startDate = $data['start_date'] ?? now('America/Sao_Paulo')->toDateString();
                    $endDate = $data['end_date'] ?? null;
                    $quotaDays = $quotaType?->days ?? 0;

                    $registration->vehicle_id = (int) $data['vehicle_id'];
                    $registration->quota_type_id = (int) $data['quota_type_id'];
                    $registration->start_date = $startDate;
                    $registration->end_date = $endDate;
                    $registration->quota_days = $quotaDays;
                }

                // Valores controlados pelo servidor.
                $registration->status = RegistrationStatus::Novo;
                $registration->facial_status = FacialStatus::Pending;

                $registration->veracity_declaration_accepted = true;
                $registration->veracity_declaration_accepted_at = now();
                $registration->privacy_policy_accepted = true;
                $registration->privacy_policy_accepted_at = now();
                $registration->privacy_policy_version = (string) config('privacy.version');

                // O aceite dos termos fica registrado agora, junto da versão
                // vigente do contrato; a assinatura em si acontece depois, no
                // fluxo público de assinatura (`signContract`).
                $registration->contract_signed = false;
                $registration->contract_version = (string) config('contracts.version');

                $registration->save();

                // Gera o contrato com dados preenchidos na página 1.
                try {
                    $filledPath = $this->contractPdf->generateFilled($registration);
                    $registration->update(['filled_contract_path' => $filledPath]);
                } catch (Throwable $e) {
                    Log::warning('Failed to generate filled contract', [
                        'registration_uuid' => $uuid,
                        'error' => $e->getMessage(),
                    ]);
                }

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
     * Registra a assinatura do contrato em um cadastro JÁ existente.
     *
     * Nada é duplicado: o cadastro não é criado de novo e nenhum outro campo
     * é alterado — apenas as colunas controladas pelo servidor da assinatura.
     * A elegibilidade (cadastro aprovado) e a assinatura única são reverificadas
     * sob lock dentro da transação, portanto dois envios simultâneos não
     * produzem duas assinaturas.
     *
     * @param  string  $signatureDataUrl  data URL PNG enviada pelo canvas.
     * @param  string|null  $signerIp  IP do titular no momento da assinatura.
     *
     * @throws ContractSignatureNotAllowedException quando o cadastro não está
     *                                              elegível ou a assinatura já foi registrada.
     * @throws RuntimeException quando a assinatura é inválida ou a geração do
     *                          PDF falha (nada é gravado no cadastro e os arquivos da tentativa
     *                          são removidos).
     */
    public function signContract(ClientRegistration $registration, string $signatureDataUrl, ?string $signerIp = null): ClientRegistration
    {
        $uuid = $registration->uuid;

        // A guarda de elegibilidade acontece ANTES de qualquer escrita: um
        // link reutilizado (cadastro já assinado) nunca toca nos arquivos
        // existentes, mesmo que a checagem seja repetida dentro do lock.
        $this->assertSignatureAllowed($registration);

        $filesTouched = false;

        try {
            return DB::transaction(function () use ($registration, $signatureDataUrl, $signerIp, $uuid, &$filesTouched): ClientRegistration {
                $locked = ClientRegistration::query()
                    ->whereKey($registration->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertSignatureAllowed($locked);

                // A tentativa passa a ser "suja" ANTES de gravar: se algo
                // falhar no meio (escrita do PNG ou geração do PDF), a limpeza
                // remove o que tiver sido criado. Apagar um diretório
                // inexistente não faz nada, então marcar antes é seguro.
                $filesTouched = true;

                $signaturePath = $this->contractStorage->storeSignature($signatureDataUrl, $uuid);

                $signedAt = now('America/Sao_Paulo');

                $contractPath = $this->contractPdf->generate(
                    $locked,
                    $signaturePath,
                    $signedAt->format('d/m/Y H:i'),
                );

                $locked->forceFill([
                    'contract_signed' => true,
                    // O instante é normalizado para UTC: as colunas `datetime`
                    // são gravadas como relógio do app (UTC) e relidas como UTC.
                    'contract_signed_at' => $signedAt->clone()->setTimezone('UTC'),
                    'contract_version' => (string) config('contracts.version'),
                    'contract_signed_pdf_path' => $contractPath,
                    'contract_signature_path' => $signaturePath,
                    // O signatário é sempre o titular do cadastro: o nome
                    // nunca vem do navegador.
                    'contract_signer_name' => $locked->full_name,
                    'contract_signer_ip' => $signerIp ?? app('request')->ip(),
                ])->save();

                $this->audit->log(
                    AuditAction::ContractSigned,
                    [
                        'registration_uuid' => $uuid,
                        'contract_version' => $locked->contract_version,
                    ],
                    $locked,
                );

                return $locked;
            });
        } catch (ContractSignatureNotAllowedException $e) {
            throw $e;
        } catch (RuntimeException $e) {
            // A assinatura desta tentativa é inválida (ou o PDF não pôde ser
            // gerado): remove somente o que foi gravado agora, preservando o
            // contrato preenchido (`contracts/generated/`) para que o cliente
            // possa tentar de novo pelo mesmo link.
            if ($filesTouched) {
                $this->contractStorage->deleteSignedDirectory($uuid);
            }

            throw $e;
        }
    }

    /**
     * Um cadastro só pode receber assinatura quando está aprovado e ainda não
     * assinado.
     *
     * @throws ContractSignatureNotAllowedException
     */
    private function assertSignatureAllowed(ClientRegistration $registration): void
    {
        if (! $registration->isApproved()) {
            throw ContractSignatureNotAllowedException::notApproved();
        }

        if ($registration->hasSignedContract()) {
            throw ContractSignatureNotAllowedException::alreadySigned();
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
            $data['contract_accepted'],
        );

        foreach (['vehicle_id', 'quota_type_id', 'start_date', 'end_date', 'quota_days'] as $field) {
            if (array_key_exists($field, $data)) {
                $data[$field] = $data[$field] ?? null;
            }
        }

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
