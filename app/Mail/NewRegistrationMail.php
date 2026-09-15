<?php

namespace App\Mail;

use App\Models\ClientRegistration;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Avisa os administradores sobre um novo cadastro recebido.
 *
 * O e-mail contém apenas dados gerais (nome, data e status): nunca CPF,
 * CNH, fotos ou documentos do cliente.
 */
class NewRegistrationMail extends Mailable
{
    public function __construct(
        public readonly ClientRegistration $registration,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Novo cadastro recebido',
            from: new Address(config('mail.from.address'), config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-registration',
            with: [
                'registration' => $this->registration,
                'name' => $this->registration->full_name,
                'date' => Carbon::parse($this->registration->created_at)
                    ->setTimezone(config('app.timezone', 'UTC'))
                    ->format('d/m/Y H:i'),
                'url' => route('admin.registrations.show', $this->registration),
                'statusLabel' => $this->registration->status->label(),
                'brandName' => 'VCA',
            ],
        );
    }
}
