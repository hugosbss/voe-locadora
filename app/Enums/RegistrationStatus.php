<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Novo = 'novo';
    case EmAnalise = 'em_analise';
    case Aprovado = 'aprovado';
    case Reprovado = 'reprovado';

    public function label(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::EmAnalise => 'Em análise',
            self::Aprovado => 'Aprovado',
            self::Reprovado => 'Reprovado',
        };
    }

    /**
     * Indica se o status consome (ocupa) uma vaga de cota no período.
     * A lista é configurável em config/quotas.php.
     */
    public function consumesQuota(): bool
    {
        return in_array($this, self::consuming(), true);
    }

    /**
     * @return array<int, self>
     */
    public static function consuming(): array
    {
        $values = config('quotas.consuming_statuses', [
            self::Novo->value,
            self::EmAnalise->value,
            self::Aprovado->value,
        ]);

        return array_values(array_filter(array_map(
            static fn (string $value): ?self => self::tryFrom($value),
            $values,
        )));
    }
}
