<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garante que a aplicação seja sempre acessada via HTTPS fora
 * do ambiente local de desenvolvimento.
 */
class ForceHttps
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isSecure() && ! app()->isLocal() && ! app()->environment('testing')) {
            return redirect()->secure($request->getRequestUri());
        }

        return $next($request);
    }
}
