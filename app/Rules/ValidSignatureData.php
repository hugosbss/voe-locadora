<?php

namespace App\Rules;

use App\Services\ContractStorageService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validação no FormRequest do payload da assinatura (data URL PNG).
 *
 * Confere formato, decodificação e tamanho do payload bruto. A validação
 * profunda (decodificação GD, dimensões, conteúdo, tinta) é feita pelo
 * ContractStorageService no momento do armazenamento, sob a transação.
 */
class ValidSignatureData implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            $fail('Desenhe sua assinatura antes de concluir o cadastro.');

            return;
        }

        $prefix = 'data:image/png;base64,';

        if (! str_starts_with($value, $prefix)) {
            $fail('A assinatura está em formato inválido.');

            return;
        }

        $binary = base64_decode(substr($value, strlen($prefix)), true);

        if ($binary === false || $binary === '') {
            $fail('A assinatura enviada é inválida.');

            return;
        }

        if (strlen($binary) > ContractStorageService::MAX_SIGNATURE_PAYLOAD_BYTES) {
            $fail('A assinatura excede o tamanho permitido.');
        }
    }
}
