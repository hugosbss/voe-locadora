<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * E-mail de recuperação de senha do painel administrativo.
 *
 * Reutiliza a identidade visual do VCA (logo embutida como imagem anexa,
 * sem host absoluto na fonte) e contém apenas o link seguro de redefinição
 * — nunca revela a senha atual ou outros dados.
 */
class AdminResetPasswordMail extends Mailable
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperação de senha',
            from: new Address(config('mail.from.address'), config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-password-reset',
            with: [
                'url' => route('admin.password.reset', [
                    'token' => $this->token,
                    'email' => $this->user->email,
                ]),
                'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
                'brandName' => 'VCA',
                'appUrl' => url('/'),
            ],
        );
    }
}
