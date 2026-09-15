<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Models\ClientRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Política de retenção de dados.
 *
 * Define o prazo de armazenamento por status declarado e a lógica de
 * seleção dos cadastros elegíveis à exclusão (executada apenas pelo
 * comando `php artisan cadastros:expurgo`).
 */
class RetentionService
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Prazo máximo de retenção (data a partir da criação) para um status.
     */
    public function retentionDays(RegistrationStatus $status): ?int
    {
        return config("retention.statuses.{$status->value}");
    }

    /**
     * Data limite em que o cadastro deve ser eliminado.
     */
    public function deadlineFor(ClientRegistration $registration): ?CarbonImmutable
    {
        $days = $this->retentionDays($registration->status);

        if (! $days) {
            return null;
        }

        return $registration->created_at->toImmutable()->addDays($days);
    }

    /**
     * Seleciona cadastros cujo prazo de retenção já expirou.
     *
     * @return Collection<int, ClientRegistration>
     */
    public function expired(?CarbonImmutable $reference = null)
    {
        $reference ??= CarbonImmutable::now();

        return ClientRegistration::query()
            ->whereIn('status', array_keys(config('retention.statuses', [])))
            ->get()
            ->filter(fn (ClientRegistration $registration) => $this->deadlineFor($registration)?->lte($reference) ?? false)
            ->values();
    }

    /**
     * Exclui um cadastro e seus documentos, registrando a auditoria.
     * Idempotente: se o registro já não existir, não faz nada.
     */
    public function purge(ClientRegistration $registration, string $reason = 'retenção expirada'): void
    {
        if (! $registration->exists) {
            return;
        }

        $documentNames = array_values(array_filter(collect(ClientRegistration::DOCUMENTS)->keys()->map(
            fn (string $type) => $registration->documentPath($type) ? $type : null
        )->all()));

        // A auditoria é gravada antes da exclusão física para preservar a
        // integridade referencial (registration_id) e mantém a UUID no
        // histórico via coluna associada.
        $this->audit->log(
            AuditAction::DeleteRegistration,
            ['reason' => $reason, 'documents' => $documentNames],
            $registration,
        );

        $registration->delete();

        // O diretório é removido após o banco; se algo falhar, a auditoria
        // já existe e a retomada do expurgo é idempotente.
        $this->deleteStoredDirectory($registration);
    }

    private function deleteStoredDirectory(ClientRegistration $registration): void
    {
        if (! $registration->uuid) {
            return;
        }

        app(DocumentStorageService::class)->deleteRegistrationDirectory($registration->uuid);
        app(ContractStorageService::class)->deleteRegistrationDirectory($registration->uuid);
    }
}
