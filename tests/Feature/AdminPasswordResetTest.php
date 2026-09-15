<?php

namespace Tests\Feature;

use App\Mail\AdminResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_is_available_to_guests(): void
    {
        $this->get(route('admin.password.request'))
            ->assertOk()
            ->assertSee('Recuperar senha')
            ->assertSee('Enviar link de recuperação');
    }

    public function test_forgot_password_page_redirects_authenticated_users_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.password.request'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_reset_password_page_is_available_to_guests(): void
    {
        $token = Str::random(64);

        $this->get(route('admin.password.reset', ['token' => $token, 'email' => 'admin@locadora.com.br']))
            ->assertOk()
            ->assertSee('Definir nova senha')
            ->assertSee('Salvar nova senha')
            ->assertSee($token);
    }

    public function test_reset_link_is_sent_for_registered_email(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'admin@locadora.com.br']);

        $this->post(route('admin.password.email'), ['email' => 'admin@locadora.com.br'])
            ->assertSessionHas('status', 'Se o e-mail estiver cadastrado, enviaremos um link para recuperação de senha.');

        Mail::assertSent(AdminResetPasswordMail::class, 1);
    }

    public function test_reset_link_email_uses_the_matching_token(): void
    {
        Mail::fake();

        $user = User::factory()->create(['email' => 'admin@locadora.com.br']);

        $this->post(route('admin.password.email'), ['email' => 'admin@locadora.com.br']);

        Mail::assertSent(AdminResetPasswordMail::class, function (AdminResetPasswordMail $mail) use ($user): bool {
            $this->assertSame($user->email, $mail->user->email);

            return is_string($mail->token) && strlen($mail->token) >= 40;
        });
    }

    public function test_forgot_password_never_reveals_whether_the_email_exists(): void
    {
        Mail::fake();

        // E-mail não cadastrado: resposta idêntica ao caso de sucesso e
        // NENHUM e-mail é enviado.
        $this->post(route('admin.password.email'), ['email' => 'inexistente@example.com'])
            ->assertSessionHas('status', 'Se o e-mail estiver cadastrado, enviaremos um link para recuperação de senha.');

        Mail::assertNothingSent();
    }

    public function test_reset_password_email_has_required_content_and_embedded_logo(): void
    {
        $user = User::factory()->create(['email' => 'admin@locadora.com.br']);

        $html = (new AdminResetPasswordMail($user, Str::uuid()->toString()))->render();

        $this->assertStringContainsString('Recuperação de senha', $html);
        $this->assertStringContainsString('Recebemos uma solicitação para redefinir a senha', $html);
        $this->assertStringContainsString('Redefinir senha', $html);
        $this->assertStringContainsString('válido por 60 minutos', $html);
        $this->assertStringContainsString('ignore este e-mail', $html);

        // Logo embutida (CID/data URI), nunca como URL absoluta no host.
        $this->assertMatchesRegularExpression('/<img[^>]*src="(cid:|data:)/', $html);
        $this->assertStringNotContainsString('/images/brand/vca-logo.jpeg', $html);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'senha-antiga',
        ]);

        $token = Password::broker()->createToken($user);

        $this->post(route('admin.password.update'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'nova-senha-segura-1',
            'password_confirmation' => 'nova-senha-segura-1',
        ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHas('success', 'Sua senha foi redefinida com sucesso. Faça login com a nova senha.');

        $fresh = $user->fresh();

        $this->assertTrue(Hash::check('nova-senha-segura-1', $fresh->password));
        $this->assertFalse(Password::broker()->tokenExists($fresh, $token));

        // A nova senha autentica no painel.
        $this->post(route('admin.login.attempt'), [
            'email' => 'reset@example.com',
            'password' => 'nova-senha-segura-1',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_invalid_token_is_rejected_with_friendly_message(): void
    {
        $user = User::factory()->create([
            'email' => 'reset@example.com',
            'password' => 'senha-antiga',
        ]);

        $this->post(route('admin.password.update'), [
            'token' => 'token-invalido',
            'email' => 'reset@example.com',
            'password' => 'nova-senha-segura-1',
            'password_confirmation' => 'nova-senha-segura-1',
        ])
            ->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('senha-antiga', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_reset_password_requires_minimum_length_and_confirmation(): void
    {
        $user = User::factory()->create(['email' => 'reset@example.com']);

        $token = Password::broker()->createToken($user);

        $this->post(route('admin.password.update'), [
            'token' => $token,
            'email' => 'reset@example.com',
            'password' => 'curta1',
            'password_confirmation' => 'diferente',
        ])
            ->assertSessionHasErrors(['password']);
    }

    public function test_login_page_has_forgot_password_link(): void
    {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Esqueci minha senha');
    }
}
