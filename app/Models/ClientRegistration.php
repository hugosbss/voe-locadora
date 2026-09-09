<?php

namespace App\Models;

use App\Enums\FacialStatus;
use App\Enums\RegistrationStatus;
use Database\Factories\ClientRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientRegistration extends Model
{
    /** @use HasFactory<ClientRegistrationFactory> */
    use HasFactory;

    public const DOCUMENTS = [
        'cnh_front' => 'Foto da CNH (frente)',
        'cnh_back' => 'Foto da CNH (verso)',
        'proof_of_residence' => 'Comprovante de residência',
        'selfie' => 'Selfie do cliente',
    ];

    protected $fillable = [
        'full_name',
        'cpf',
        'birth_date',
        'phone',
        'whatsapp',
        'email',
        'cep',
        'address',
        'address_number',
        'neighborhood',
        'city',
        'state',
        'cnh_number',
        'cnh_category',
        'cnh_expiry_date',
        'cnh_front_path',
        'cnh_back_path',
        'proof_of_residence_path',
        'selfie_path',
        'facial_status',
        'status',
        'veracity_declaration_accepted',
        'privacy_policy_accepted',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'datetime',
            'cnh_expiry_date' => 'datetime',
            'status' => RegistrationStatus::class,
            'facial_status' => FacialStatus::class,
            'veracity_declaration_accepted' => 'boolean',
            'privacy_policy_accepted' => 'boolean',
        ];
    }

    public function maskedCpf(): string
    {
        $digits = preg_replace('/\D/', '', $this->cpf);

        return sprintf(
            '%s.%s.%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3),
            substr($digits, 9, 2)
        );
    }

    /**
     * Normaliza o CPF para apenas dígitos.
     */
    public function setCpfAttribute(?string $value): void
    {
        $this->attributes['cpf'] = $value ? preg_replace('/\D/', '', $value) : null;
    }
}
