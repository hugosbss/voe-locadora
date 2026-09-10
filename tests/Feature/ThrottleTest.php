<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake(['https://viacep.com.br/*' => Http::response([], 404)]);
    }

    public function test_registration_endpoint_is_rate_limited(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post('/cadastro', ['full_name' => 'a']);
        }

        $this->post('/cadastro', ['full_name' => 'a'])
            ->assertStatus(429);
    }

    public function test_cep_lookup_is_rate_limited(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->get('/cep?cep=01310-100');
        }

        $this->get('/cep?cep=01310-100')->assertStatus(429);
    }

    public function test_admin_login_is_rate_limited_per_credential(): void
    {
        User::factory()->create(['email' => 'limitada@example.com', 'password' => 'password']);

        $email = 'limitada@example.com';
        $payload = fn () => ['email' => $email, 'password' => 'password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', $payload());
        }

        $this->post('/admin/login', $payload())->assertStatus(429);
    }

    public function test_admin_login_limit_is_scoped_per_email(): void
    {
        User::factory()->create(['email' => 'outra@example.com', 'password' => 'password']);

        $payload = ['email' => 'outra@example.com', 'password' => 'password'];

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', $payload);
        }

        // Email diferente não sofre bloqueio em cascata.
        $this->post('/admin/login', ['email' => 'diferente@example.com', 'password' => 'password'])
            ->assertStatus(302);
    }
}
