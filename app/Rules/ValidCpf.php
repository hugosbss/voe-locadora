<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/\D/', '', $value);

        if (strlen($cpf) !== 11) {
            $fail('O CPF deve conter 11 dígitos.');

            return;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            $fail('O CPF informado é inválido.');

            return;
        }

        for ($checkDigit = 9; $checkDigit <= 10; $checkDigit++) {
            $sum = 0;

            for ($position = 0; $position < $checkDigit; $position++) {
                $sum += ((int) $cpf[$position]) * (($checkDigit + 1) - $position);
            }

            $remainder = ($sum * 10) % 11;
            $expected = ($remainder === 10 || $remainder === 11) ? 0 : $remainder;

            if ((int) $cpf[$checkDigit] !== $expected) {
                $fail('O CPF informado é inválido.');

                return;
            }
        }
    }
}
