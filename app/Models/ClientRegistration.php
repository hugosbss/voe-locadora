<?php

namespace App\Models;

use App\Enums\FacialStatus;
use App\Enums\RegistrationStatus;
use Database\Factories\ClientRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientRegistration extends Model
{
    /** @use HasFactory<ClientRegistrationFactory> */
    use HasFactory;

    /**
     * Whitelist de documentos aceitos. A chave é o tipo (usado em URLs,
     * colunas *_path e diretórios); o valor é apenas o rótulo exibido.
     */
    public const DOCUMENTS = [
        'cnh_front' => 'Foto da CNH (frente)',
        'cnh_back' => 'Foto da CNH (verso)',
        'proof_of_residence' => 'Comprovante de residência',
        'selfie' => 'Selfie do cliente',
        'vehicle_pickup' => 'Foto da retirada',
        'vehicle_delivery' => 'Foto da entrega',
    ];

    /**
     * Documentos enviados pelo formulário público de cadastro. As fotos de
     * retirada/entrega de veículo (em DOCUMENTS) não têm upload no fluxo
     * público atual.
     */
    public const UPLOAD_DOCUMENTS = [
        'cnh_front' => 'Foto da CNH (frente)',
        'cnh_back' => 'Foto da CNH (verso)',
        'proof_of_residence' => 'Comprovante de residência',
        'selfie' => 'Selfie do cliente',
    ];

    /**
     * Apenas campos fornecidos pelo próprio titular podem ser preenchidos
     * em massa. Campos de status, caminhos de documentos, consentimento e
     * identificadores são sempre definidos explicitamente pelo servidor.
     */
    protected $fillable = [
        'uuid',
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
        'vehicle_id',
        'quota_type_id',
        'start_date',
        'end_date',
        'quota_days',
        'vehicle_pickup_photo_path',
        'vehicle_delivery_photo_path',
        'vehicle_observation',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'datetime',
            'cnh_expiry_date' => 'datetime',
            'status' => RegistrationStatus::class,
            'facial_status' => FacialStatus::class,
            'veracity_declaration_accepted' => 'boolean',
            'veracity_declaration_accepted_at' => 'datetime',
            'privacy_policy_accepted' => 'boolean',
            'privacy_policy_accepted_at' => 'datetime',
            'contract_signed' => 'boolean',
            'contract_signed_at' => 'datetime',
        ];
    }

    /**
     * As URLs administrativas usam o UUID (público e imprevisível) em vez do
     * id numérico interno. O id sequencial permanece apenas no banco.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
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

    /**
     * Retorna o caminho armazenado de um documento, se existir.
     */
    public function documentPath(string $document): ?string
    {
        if (! array_key_exists($document, self::DOCUMENTS)) {
            return null;
        }

        $field = match ($document) {
            'vehicle_pickup' => 'vehicle_pickup_photo_path',
            'vehicle_delivery' => 'vehicle_delivery_photo_path',
            default => $document.'_path',
        };

        return $this->{$field} ?? null;
    }

    /**
     * Confirma que um caminho de documento pertence a ESTE cadastro.
     * Nunca confie no cliente para formar caminhos: o diretório esperado
     * é sempre `cadastros/{uuid deste registro}/{tipo}/...`.
     */
    public function ownsStoredDocument(string $document, string $path): bool
    {
        if (! array_key_exists($document, self::DOCUMENTS) || ! $this->uuid) {
            return false;
        }

        $expected = 'cadastros/'.$this->uuid.'/'.$document.'/';

        return Str::startsWith($path, $expected);
    }

    /**
     * Diretório raiz de armazenamento deste cadastro.
     */
    public function storageDirectory(): string
    {
        return 'cadastros/'.$this->uuid;
    }

    /**
     * Diretório privado do contrato assinado deste cadastro.
     */
    public function contractStorageDirectory(): string
    {
        return 'contracts/signed/'.$this->uuid;
    }

    /**
     * Confirma que um arquivo de contrato (PDF assinado ou assinatura em
     * PNG) pertence a ESTE cadastro. Caminhos nunca são aceitos do cliente:
     * o diretório esperado é sempre `contracts/signed/{uuid deste registro}/`.
     */
    public function ownsStoredContractFile(string $path): bool
    {
        if (! $this->uuid || ! is_string($path) || $path === '') {
            return false;
        }

        $expected = $this->contractStorageDirectory().'/';

        return Str::startsWith($path, $expected);
    }

    /**
     * Indica se este cadastro possui contrato assinado registrado.
     */
    public function hasSignedContract(): bool
    {
        return $this->contract_signed === true
            && $this->contract_signed_pdf_path !== null
            && $this->contract_signature_path !== null;
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function quotaType()
    {
        return $this->belongsTo(QuotaType::class);
    }
}
