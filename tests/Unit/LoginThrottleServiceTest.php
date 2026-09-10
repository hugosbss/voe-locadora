<?php

namespace Tests\Unit;

use App\Services\LoginThrottleService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LoginThrottleServiceTest extends TestCase
{
    public function test_unlocks_below_threshold(): void
    {
        Cache::flush();

        $service = app(LoginThrottleService::class);

        $this->assertSame(0, $service->remainingLock('user@example.com|127.0.0.1'));

        for ($i = 0; $i < 4; $i++) {
            $service->recordFailure('user@example.com|127.0.0.1');
        }

        $this->assertSame(0, $service->remainingLock('user@example.com|127.0.0.1'));
    }

    public function test_locks_after_threshold(): void
    {
        Cache::flush();

        config(['rate.limits.login_lock_threshold' => 3]);

        $service = app(LoginThrottleService::class);

        for ($i = 0; $i < 3; $i++) {
            $service->recordFailure('user@example.com|10.0.0.1');
        }

        $this->assertGreaterThan(0, $service->remainingLock('user@example.com|10.0.0.1'));
    }

    public function test_key_is_isolated_by_credential(): void
    {
        Cache::flush();

        config(['rate.limits.login_lock_threshold' => 2]);

        $service = app(LoginThrottleService::class);

        $service->recordFailure('person-a|10.0.0.1');

        // Chave A bloqueia apenas A; B permanece livre.
        $service->recordFailure('person-a|10.0.0.1');
        $this->assertGreaterThan(0, $service->remainingLock('person-a|10.0.0.1'));
        $this->assertSame(0, $service->remainingLock('person-b|10.0.0.1'));
    }

    public function test_clear_resets_lock(): void
    {
        Cache::flush();

        config(['rate.limits.login_lock_threshold' => 2]);

        $service = app(LoginThrottleService::class);

        $service->recordFailure('user@example.com|10.0.0.2');
        $service->recordFailure('user@example.com|10.0.0.2');

        $this->assertGreaterThan(0, $service->remainingLock('user@example.com|10.0.0.2'));

        $service->clear('user@example.com|10.0.0.2');

        $this->assertSame(0, $service->remainingLock('user@example.com|10.0.0.2'));
    }
}
