<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Limite agregado para a requisição de upload: soma o tamanho de todos os
 * arquivos dos campos informados e rejeita quando o total excede o limite.
 */
class UploadBatchMax implements ValidationRule
{
    /**
     * @param  array<int, string>  $fields
     */
    public function __construct(
        private readonly array $fields,
        private readonly int $maxKilobytes,
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $totalKilobytes = array_reduce(
            $this->fields,
            function (int $carry, string $field): int {
                $file = request()->file($field);

                return $carry + (int) (($file instanceof UploadedFile ? $file->getSize() : 0) / 1024);
            },
            0,
        );

        if ($totalKilobytes > $this->maxKilobytes) {
            $fail('O tamanho total dos arquivos enviados excede o limite permitido.');
        }
    }
}
