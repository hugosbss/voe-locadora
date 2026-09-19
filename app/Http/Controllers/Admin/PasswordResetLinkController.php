<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

/**
 * Fluxo "Esqueci minha senha" do painel administrativo.
 *
 * Usa o broker de redefinição de senha padrão do Laravel (token seguro com
 * expiração e throttle de geração). A resposta é sempre genérica: o endpoint
 * nunca revela se o e-mail está (ou não) cadastrado, evitando enumeração.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Exibe o formulário "Recuperar senha" (informar o e-mail).
     */
    public function create(): View
    {
        return view('admin.auth.password.request');
    }

    /**
     * Envia o link de recuperação para o e-mail, se ele existir.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        // O resultado é ignorado de propósito: a mensagem exibida é única,
        // independentemente de o e-mail estar cadastrado.
        Password::broker()->sendResetLink([
            'email' => strtolower(trim($request->string('email'))),
        ]);

        return back()->with('status', 'Se o e-mail estiver cadastrado, enviaremos um link para recuperação de senha.');
    }
}
