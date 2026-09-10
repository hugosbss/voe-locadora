<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * Detecção de contexto para as páginas de erro: fluxo público × administrativo.
 *
 * A decisão é baseada apenas no caminho da requisição — nunca em sessão/auth,
 * pois páginas de erro podem renderizar antes do middleware de sessão rodar
 * (ex.: rota inexistente). Assim nenhuma informação interna depende de estado.
 */
final class ErrorContext
{
    public static function isAdmin(?Request $request = null): bool
    {
        $request ??= request();

        if (! $request) {
            return false;
        }

        $path = $request->decodedPath();

        return $path === 'admin' || Str::startsWith($path, 'admin/');
    }

    /**
     * Página inicial do contexto (destino seguro para "Voltar para o início").
     */
    public static function homeUrl(bool $isAdmin): string
    {
        if ($isAdmin && Route::has('admin.dashboard')) {
            return route('admin.dashboard');
        }

        return route('client-registrations.create');
    }
}
