<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aplica cabeçalhos de segurança em todas as respostas web.
 *
 * A CSP é baseada nos recursos efetivamente utilizados (Vite build, fontes
 * Google, endpoint de fotos do próprio domínio, preview via data:). Em
 * ambiente local o servidor de desenvolvimento do Vite é contemplado; em
 * produção não há 'unsafe-inline' para scripts nem 'unsafe-eval'.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return static::apply($next($request), $request);
    }

    /**
     * Aplica os cabeçalhos de segurança a uma resposta já montada.
     *
     * Além do middleware (respostas normais), é chamado pelo próprio
     * manipulador de exceções via Exceptions::respond(): assim respostas
     * de erro também recebem os cabeçalhos mesmo quando montadas fora do
     * pipeline de middlewares (ex.: 404 de rota inexistente, 429, 419).
     */
    public static function apply(Response $response, Request $request): Response
    {
        $local = app()->isLocal() || app()->environment('testing');

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=(), payment=(), usb=()');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        // HSTS somente em conexão HTTPS fora de ambiente de desenvolvimento.
        if ($request->isSecure() && ! $local) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        $response->headers->set('Content-Security-Policy', static::csp($local));

        return $response;
    }

    private static function csp(bool $local): string
    {
        $dev = $local ? ' http://localhost:5173 ws://localhost:5173' : '';

        $policy = [
            "default-src 'self'",
            "script-src 'self'{$dev}",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob:",
            "connect-src 'self'{$dev}",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
            "frame-src 'none'",
            "frame-ancestors 'self'",
            "media-src 'none'",
            "worker-src 'self'",
        ];

        return implode('; ', $policy);
    }
}
