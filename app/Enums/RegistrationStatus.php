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
}
