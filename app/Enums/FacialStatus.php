<?php

namespace App\Enums;

/**
 * Situação da validação facial.
 *
 * A comparação automática da selfie com a foto da CNH é um
 * recurso futuro; hoje a selfie é armazenada para análise manual.
 */
enum FacialStatus: string
{
    case Pending = 'pending';
    case Matched = 'matched';
    case NotMatched = 'not_matched';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando análise',
            self::Matched => 'Compatível',
            self::NotMatched => 'Não compatível',
        };
    }
}
