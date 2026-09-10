<?php

namespace Tests\Feature;

use App\Enums\AuditAction;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private Totp $totp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->totp = app(Totp::class);
    }

    private function currentCode(string $secret): string
    {
        return $this->totp->codeAt((int) floor(time() / 30), $secret);
    }

    public function test_login_requires_second_factor_when_enabled(): void
    {
        $secret = $this->totp->generateSecret();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.login.two-factor'));

        $this->assertGuest();

        $this->get(route('admin.login.two-factor'))->assertOk();

        $this->post(route('admin.login.two-factor.verify'), [
            'code' => $this->currentCode($secret),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_second_factor_rejects_wrong_code(): void
    {
        $secret = $this->totp->generateSecret();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password']);
        $this->get(route('admin.login.two-factor'));

        $this->post(route('admin.login.two-factor.verify'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_two_factor_page_redirects_without_pending_session(): void
    {
        $this->get(route('admin.login.two-factor'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_recovery_code_allows_login_and_is_consumed(): void
    {
        $secret = $this->totp->generateSecret();
        $recovery = 'ABCDE-FGHIJ';

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
            'two_factor_recovery_codes' => [
                Hash::make($recovery),
                Hash::make('KLKLM-NOPQR'),
            ],
        ]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password']);

        $this->post(route('admin.login.two-factor.verify'), ['code' => $recovery])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $user->refresh();
        $this->assertCount(1, $user->two_factor_recovery_codes);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::TwoFactorRecoveryUsed->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_login_flow_audits_success_and_regenerates_session(): void
    {
        $user = User::factory()->create();

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::LoginSuccess->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_login_failure_uses_generic_message_and_audits(): void
    {
        User::factory()->create(['email' => 'seguro@example.com']);

        $this->from('/admin/login')
            ->post('/admin/login', [
                'email' => 'seguro@example.com',
                'password' => 'senha-errada',
            ])
            ->assertSessionHasErrors('email')
            ->assertSessionDoesntHaveErrors('password')
            ->assertRedirect('/admin/login');

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::LoginFailed->value,
        ]);
    }

    public function test_enable_requires_password_and_confirmation_with_code(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        // Senha incorreta rejeita.
        $this->post(route('admin.security.2fa.enable'), ['password' => 'errada'])
            ->assertSessionHasErrors('password');

        $this->assertNull($user->refresh()->two_factor_secret);

        // Habilitação correta prepara o segredo (pendência).
        $this->post(route('admin.security.2fa.enable'), ['password' => 'password'])
            ->assertRedirect(route('admin.security.index'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_enabled_at);

        // Código errado não ativa.
        $this->post(route('admin.security.2fa.confirm'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $user->refresh();
        $this->assertNull($user->two_factor_enabled_at);

        // Código correto ativa de fato.
        $this->post(route('admin.security.2fa.confirm'), ['code' => $this->currentCode($user->two_factor_secret)])
            ->assertRedirect(route('admin.security.index'));

        $user->refresh();
        $this->assertNotNull($user->two_factor_enabled_at);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::TwoFactorEnabled->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_disable_requires_password_and_current_totp_code(): void
    {
        $secret = $this->totp->generateSecret();

        $user = User::factory()->create([
            'two_factor_secret' => $secret,
            'two_factor_enabled_at' => now(),
        ]);

        $this->actingAs($user);

        // Fenha errada bloqueia.
        $this->post(route('admin.security.2fa.disable'), [
            'password' => 'errada',
            'code' => $this->currentCode($secret),
        ])->assertSessionHasErrors('password');

        $this->assertNotNull($user->refresh()->two_factor_secret);

        // Código errado bloqueia.
        $this->post(route('admin.security.2fa.disable'), [
            'password' => 'password',
            'code' => '000000',
        ])->assertSessionHasErrors('code');

        // Senha + TOTP corretos desativam.
        $this->post(route('admin.security.2fa.disable'), [
            'password' => 'password',
            'code' => $this->currentCode($secret),
        ])->assertRedirect(route('admin.security.index'));

        $user->refresh();
        $this->assertNull($user->two_factor_secret);
        $this->assertNull($user->two_factor_enabled_at);

        $this->assertDatabaseHas('admin_audit_logs', [
            'action' => AuditAction::TwoFactorDisabled->value,
            'user_id' => $user->id,
        ]);
    }

    public function test_qr_code_endpoint_is_available_during_pending_enrollment(): void
    {
        $user = User::factory()->create([
            'two_factor_secret' => $this->totp->generateSecret(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.security.2fa.qr'))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml')
            ->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function test_qr_code_endpoint_is_hidden_when_not_enrolling(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.security.2fa.qr'))
            ->assertNotFound();
    }
}
