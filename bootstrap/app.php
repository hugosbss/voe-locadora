<?php

use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Segurança cabeçalhos primeiro: assim também cobrem respostas
        // geradas por middlewares posteriores (ex.: 429 do throttle).
        $middleware->prependToGroup('web', SecurityHeaders::class);

        $middleware->web(append: [ForceHttps::class]);

        // Proxies confiáveis explícitos (nunca '*'): os cabeçalhos
        // X-Forwarded-* só são considerados quando vindos de balanceador/
        // CDN realmente configurado no ambiente. O acesso ao config é
        // adiado para quando o repositório já estiver carregado (no
        // bootstrap mínimo de comandos ele ainda não existe).
        $trustedProxies = app()->bound('config') ? config('app.trusted_proxies', []) : [];

        if ($trustedProxies !== []) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        // Redireciona visitantes não autenticados para o login administrativo.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));

        // Usuários já autenticados nunca devem ver o login novamente:
        // vão direto para o painel.
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // EmbeddedExceptions: nunca expor detalhes internos em JSON.
        $exceptions->dontReport([
            AuthorizationException::class,
            ValidationException::class,
        ]);

        // Respostas de erro também recebem os cabeçalhos de segurança,
        // inclusive quando montadas fora do pipeline de middlewares
        // (ex.: 404 de rota inexistente, 419, 429 do throttle).
        $exceptions->respond(
            fn (Response $response, Throwable $e, Request $request): Response => SecurityHeaders::apply($response, $request),
        );
    })->create();
