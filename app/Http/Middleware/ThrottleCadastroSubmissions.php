<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Rate limit específico para o POST de cadastro.
 *
 * A política tem dois contadores independentes por IP:
 *  - total: conta todas as submissões (inclusive as rejeitadas por
 *    validação). Protege contra flood automatizado de requisições
 *    (CPU/upload/banco) sem penalizar quem corrige e reenvia algumas vezes;
 *  - created: conta apenas cadastros realmente criados. O incremento acontece
 *    no controlador, somente quando a criação é concluída com sucesso; erros de
 *    validação jamais consomem este limite. Aqui (middleware) o contador só é
 *    verificado, impedindo spam de cadastros reais.
 *
 * Erros de validação são renderizados como redirecionamento de volta ao
 * formulário, então não é possível distinguir sucesso de validação falha
 * apenas pelo status da resposta — por isso o contador de criação fica no
 * controlador.
 *
 * O GET /cadastro não passa por este middleware: renderizar o formulário
 * nunca é contabilizado como tentativa de envio.
 */
class ThrottleCadastroSubmissions
{
    public function __construct(private readonly RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $config = config('rate.limits.cadastro');

        $totalMaxAttempts = (int) ($config['max_attempts'] ?? 60);
        $totalDecay = (int) ($config['decay_minutes'] ?? 15) * 60;
        $createdMaxAttempts = (int) ($config['success_max_attempts'] ?? 10);
        $createdDecay = (int) ($config['success_decay_minutes'] ?? 15) * 60;

        $ip = (string) $request->ip();
        $totalKey = $this->key('cadastro|total', $ip);
        $createdKey = static::createdKey($ip);

        if ($this->limiter->tooManyAttempts($totalKey, $totalMaxAttempts)) {
            throw new TooManyRequestsHttpException(Response::HTTP_TOO_MANY_REQUESTS, 'Too Many Attempts.');
        }

        if ($this->limiter->tooManyAttempts($createdKey, $createdMaxAttempts)) {
            throw new TooManyRequestsHttpException(Response::HTTP_TOO_MANY_REQUESTS, 'Too Many Attempts.');
        }

        // Conta a tentativa (qualquer resposta) antes de processar.
        $this->limiter->hit($totalKey, $totalDecay);

        return $next($request);
    }

    /**
     * Chave do contador de cadastros criados. Usada também pelo controlador
     * para incrementar quando a criação é concluída com sucesso.
     */
    public static function createdKey(string $ip): string
    {
        return sha1('cadastro|created|'.$ip);
    }

    public static function createdDecaySeconds(): int
    {
        $config = config('rate.limits.cadastro');

        return (int) ($config['success_decay_minutes'] ?? 15) * 60;
    }

    private function key(string $prefix, string $ip): string
    {
        return sha1($prefix.'|'.$ip);
    }
}
