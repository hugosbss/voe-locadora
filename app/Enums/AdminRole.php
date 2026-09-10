<?php

namespace App\Enums;

/**
 * Papéis administrativos.
 *
 * Hoje existe apenas o papel `admin`. A estrutura já permite evoluir para
 * novos papéis (ex.: analista sem permissão de exclusão) sem reescrever a
 * autorização: basta adicionar novos cases e ajustar as abilities das
 * Policies.
 */
enum AdminRole: string
{
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
        };
    }
}
