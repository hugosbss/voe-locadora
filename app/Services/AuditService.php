<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AdminAuditLog;
use App\Models\ClientRegistration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Registra ações administrativas na tabela imutável `admin_audit_logs`.
 *
 * A auditoria é sempre escrita pelo servidor. Nenhum valor controlado pelo
 * cliente chega aqui diretamente e metadados sensíveis (CPF, arquivos,
 * segredos) são redigidos antes da persistência.
 */
class AuditService
{
    private const SENSITIVE_KEYS = '/password|secret|token|authori[sz]ation|cookie|cpf|cnh_number|biometric|birth|phone|whatsapp|mail|image|photo|file|path/i';

    public function log(
        AuditAction $action,
        array $metadata = [],
        ?ClientRegistration $registration = null,
        ?User $user = null,
        ?Request $request = null,
    ): AdminAuditLog {
        $user ??= Auth::user();
        $request ??= app(Request::class);

        return AdminAuditLog::query()->forceCreate([
            'user_id' => $user?->id,
            'registration_id' => $registration?->id,
            'action' => $action->value,
            'metadata' => $this->sanitize($metadata),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 512),
            'created_at' => now(),
        ]);
    }

    /**
     * Redige metadados potencialmente sensíveis para evitar vazamento
     * de dados pessoais nos registros de auditoria.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitize(array $metadata): array
    {
        $result = [];

        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->sanitize($value);

                continue;
            }

            $result[$key] = preg_match(self::SENSITIVE_KEYS, (string) $key)
                ? '[redacted]'
                : $value;
        }

        return $result;
    }
}
