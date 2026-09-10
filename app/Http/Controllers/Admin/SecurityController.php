<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\ClientRegistration;
use App\Services\AuditService;
use App\Support\Totp;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerenciamento de verificação em duas etapas (TOTP) para administradores.
 */
class SecurityController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly Totp $totp,
    ) {}

    /**
     * Painel de segurança: estado do 2FA, códigos de recuperação e o
     * resumo crítico para a locadora.
     */
    public function index(): ViewContract
    {
        $user = Auth::user();
        $secretAvailable = (bool) $user->two_factor_secret;
        $enabled = $user->hasTwoFactorEnabled();

        $enrollment = null;

        // Início / continuação da ativação: mantém o segredo pendente e os
        // códigos de recuperação exibidos uma única vez (na sessão).
        if ($secretAvailable && ! $enabled) {
            $enrollment = [
                'secret' => $user->two_factor_secret,
                'uri' => $this->totp->provisioningUri($user->two_factor_secret, $user->email, config('app.name')),
                'recovery_codes' => request()->session()->get('recovery_codes', []),
            ];
        }

        $summary = [
            'total' => ClientRegistration::count(),
            'pending' => ClientRegistration::query()->where('status', RegistrationStatus::Novo->value)->count(),
            'approved' => ClientRegistration::query()->where('status', RegistrationStatus::Aprovado->value)->count(),
            'rejected' => ClientRegistration::query()->where('status', RegistrationStatus::Reprovado->value)->count(),
            'under_review' => ClientRegistration::query()->where('status', RegistrationStatus::EmAnalise->value)->count(),
            'audit_entries' => AdminAuditLog::count(),
        ];

        return view('admin.security.index', [
            'user' => $user,
            'secretAvailable' => $secretAvailable,
            'enabled' => $enabled,
            'enrollment' => $enrollment,
            'summary' => $summary,
        ]);
    }

    /**
     * Etapa 1 da ativação: valida a senha e prepara o segredo (a ativação
     * só ocorre após o primeiro código TOTP ser confirmado).
     */
    public function enable2FA(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $user = Auth::user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'A senha informada está incorreta.']);
        }

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.security.index');
        }

        $secret = $this->totp->generateSecret();

        // Códigos de recuperação: a versão em claro é exibida uma única
        // vez (flash); o banco guarda apenas o hash.
        $plainCodes = $this->generateRecoveryCodes();
        $recoveryCodes = array_map(fn (string $code) => Hash::make($code), $plainCodes);

        $user->two_factor_secret = $secret;
        $user->two_factor_recovery_codes = $recoveryCodes;

        // two_factor_enabled_at permanece nulo até a confirmação.
        $user->save();

        request()->session()->flash('recovery_codes', $plainCodes);

        return redirect()->route('admin.security.index');
    }

    /**
     * Etapa 2: confirma o primeiro código TOTP e ativa o 2FA.
     */
    public function confirm2FA(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:16']]);

        $user = Auth::user();

        if (! $user->two_factor_secret || $user->hasTwoFactorEnabled()) {
            return redirect()->route('admin.security.index');
        }

        if (! $this->totp->verify($request->input('code'), $user->two_factor_secret)) {
            throw ValidationException::withMessages(['code' => 'Código inválido.']);
        }

        $user->two_factor_enabled_at = now();
        $user->save();

        request()->session()->forget('recovery_codes');

        $this->audit->log(AuditAction::TwoFactorEnabled, [], null, $user, $request);

        return redirect()->route('admin.security.index')->with('success', 'Verificação em duas etapas ativada.');
    }

    /**
     * Desativa o 2FA exigindo confirmação forte (senha + código atual).
     */
    public function disable2FA(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:16'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages(['password' => 'A senha informada está incorreta.']);
        }

        if (! $user->two_factor_secret || ! $this->totp->verify($request->input('code'), $user->two_factor_secret)) {
            throw ValidationException::withMessages(['code' => 'Código inválido.']);
        }

        $user->two_factor_secret = null;
        $user->two_factor_recovery_codes = [];
        $user->two_factor_enabled_at = null;
        $user->save();

        $this->audit->log(AuditAction::TwoFactorDisabled, [], null, $user, $request);

        return redirect()->route('admin.security.index')->with('success', 'Verificação em duas etapas desativada.');
    }

    /**
     * @return array<int, string> códigos guardados como hash
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < config('rate.limits.two_factor_recovery_codes', 8); $i++) {
            $codes[] = Hash::make(strtoupper(bin2hex(random_bytes(5))).'-'.strtoupper(bin2hex(random_bytes(5))));
        }

        return $codes;
    }

    /**
     * Gera o QR Code SVG para cadastro no aplicativo autenticador.
     */
    public function qrCode(): Response
    {
        $user = Auth::user();

        if (! $user->two_factor_secret || $user->hasTwoFactorEnabled()) {
            abort(404);
        }

        $uri = $this->totp->provisioningUri($user->two_factor_secret, $user->email, config('app.name'));

        $result = (new Builder(
            writer: new SvgWriter,
            data: $uri,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 220,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
        ))->build();

        return response($result->getString(), 200)
            ->header('Content-Type', $result->getMimeType())
            ->header('Cache-Control', 'no-store');
    }
}
