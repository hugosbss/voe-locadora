<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Define a nova senha após o clique no link de recuperação.
 *
 * A validação do token (existência, expiração e uso único) é feita pelo
 * broker padrão do Laravel. Tokens inválidos/expirados produzem mensagem
 * amigável e a senha antiga permanece válida.
 */
class NewPasswordController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Exibe o formulário "Definir nova senha".
     */
    public function create(Request $request, string $token): View
    {
        return view('admin.auth.password.reset', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Valida o token e redefine a senha.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                ])->setRememberToken(Str::random(60));

                $user->save();

                $this->audit->log(AuditAction::PasswordReset, [], null, $user);

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withErrors(['email' => 'Link de recuperação inválido, expirado ou já utilizado. Solicite um novo link.'])
                ->withInput(['email' => $request->input('email')]);
        }

        return redirect()
            ->route('admin.login')
            ->with('success', 'Sua senha foi redefinida com sucesso. Faça login com a nova senha.');
    }
}
