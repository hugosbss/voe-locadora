<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use App\Services\LoginThrottleService;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    private const GENERIC_AUTH_ERROR = 'Credenciais inválidas ou conta temporariamente bloqueada.';

    public function __construct(
        private readonly AuditService $audit,
        private readonly LoginThrottleService $throttle,
        private readonly Totp $totp,
    ) {}

    /**
     * Exibe o formulário de login administrativo.
     */
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Etapa 1: valida e-mail + senha sem autenticar a sessão. Não revela se
     * a conta existe nem qual campo está incorreto (mensagem genérica).
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $throttleKey = $this->throttleKey($request, $credentials['email']);

        if ($this->throttle->remainingLock($throttleKey) > 0) {
            $this->audit->log(AuditAction::LoginFailed, [], null, null, $request);

            return back()
                ->withErrors(['email' => self::GENERIC_AUTH_ERROR])
                ->onlyInput('email');
        }

        $user = User::query()->where('email', strtolower($credentials['email']))->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            $this->throttle->recordFailure($throttleKey);
            $this->audit->log(AuditAction::LoginFailed, [], null, null, $request);

            return back()
                ->withErrors(['email' => self::GENERIC_AUTH_ERROR])
                ->onlyInput('email');
        }

        $this->throttle->clear($throttleKey);

        // MFA habilitada: exige o segundo fator antes de autenticar a sessão.
        if ($user->hasTwoFactorEnabled()) {
            // Remember persiste o utilizador entre o form de 2FA e a confirmação
            // (o flash expiraria na requisição do formulário).
            $request->session()->put('2fa.user_id', $user->id);
            $request->session()->put('2fa.remember', $request->boolean('remember'));

            return redirect()->route('admin.login.two-factor');
        }

        $this->completeAuthentication($user, $request, $request->boolean('remember'));

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Exibe o formulário do segundo fator (TOTP ou código de recuperação).
     */
    public function showTwoFactor(Request $request): View|RedirectResponse
    {
        $userId = $request->session()->get('2fa.user_id');

        if (! $userId || ! User::query()->whereKey($userId)->exists()) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.two-factor');
    }

    /**
     * Etapa 2: confere o TOTP (ou código de recuperação) e só então inicia
     * a sessão administrativa, com regeneração do ID de sessão.
     */
    public function verifyTwoFactor(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $userId = $request->session()->get('2fa.user_id');

        if (! $userId) {
            return redirect()->route('admin.login');
        }

        $user = User::query()->find($userId);
        $code = $validated['code'];

        $usedRecovery = false;

        if ($user && $user->two_factor_secret && $this->totp->verify($code, $user->two_factor_secret)) {
            // ok (TOTP)
        } elseif ($user && $this->verifyRecoveryCode($user, $code)) {
            $usedRecovery = true;
        } else {
            return back()
                ->withErrors(['code' => 'Código inválido.'])
                ->withInput($request->except('code'));
        }

        $remember = (bool) $request->session()->pull('2fa.remember', false);
        $request->session()->forget('2fa.user_id');

        $this->completeAuthentication($user, $request, $remember);

        if ($usedRecovery) {
            $this->audit->log(AuditAction::TwoFactorRecoveryUsed, [], null, $user, $request);
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Encerra a sessão administrativa.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $this->audit->log(AuditAction::Logout, [], null, $user, $request);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Verifica um código de recuperação (armazenados apenas como hash).
     */
    private function verifyRecoveryCode(User $user, string $code): bool
    {
        $codes = $user->two_factor_recovery_codes ?? [];

        foreach ($codes as $index => $hash) {
            if (Hash::check($code, $hash)) {
                unset($codes[$index]);
                $user->two_factor_recovery_codes = array_values($codes);
                $user->save();

                return true;
            }
        }

        return false;
    }

    private function completeAuthentication(User $user, Request $request, bool $remember): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();

        $this->audit->log(AuditAction::LoginSuccess, [], null, $user, $request);
    }

    private function throttleKey(Request $request, string $email): string
    {
        return $request->ip().'|'.strtolower($email);
    }
}
