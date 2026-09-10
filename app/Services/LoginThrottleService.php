<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Bloqueio progressivo de tentativas de login administrativo.
 *
 * Complementa o rate limiter de janela fixa com um bloqueio acumulativo:
 * após um número de falhas no período de observação, a credencial + IP
 * ficam bloqueadas por um intervalo crescente. Nunca gera lockout
 * permanente e nunca revela a existência da conta.
 */
class LoginThrottleService
{
    private function failureKey(string $key): string
    {
        return 'login:'.hash('sha256', $key).':failures';
    }

    private function lockKey(string $key): string
    {
        return 'login:'.hash('sha256', $key).':lock';
    }

    /**
     * Segundos restantes de bloqueio (0 = liberado).
     */
    public function remainingLock(string $key): int
    {
        $until = Cache::get($this->lockKey($key));

        if (! $until instanceof \DateTimeInterface) {
            return 0;
        }

        return max(0, (int) abs($until->diffInSeconds(now())));
    }

    /**
     * Registra uma falha. Quando o limiar é atingido, inicia o bloqueio.
     */
    public function recordFailure(string $key): void
    {
        $failures = (int) Cache::increment($this->failureKey($key));

        $threshold = (int) config('rate.limits.login_lock_threshold', 10);
        $minutes = (int) config('rate.limits.login_lock_minutes', 30);

        if ($failures >= $threshold) {
            Cache::put($this->lockKey($key), now()->addMinutes($minutes), now()->addMinutes($minutes + 1));

            return;
        }

        Cache::put($this->failureKey($key), $failures, now()->addMinutes($minutes));
    }

    /**
     * Zera as contagens após autenticação bem-sucedida.
     */
    public function clear(string $key): void
    {
        Cache::forget($this->failureKey($key));
        Cache::forget($this->lockKey($key));
    }
}
