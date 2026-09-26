<?php

namespace App\Http\Requests;

use App\Rules\ValidSignatureData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Assinatura do contrato em um cadastro já existente.
 *
 * A elegibilidade do cadastro NÃO é validada aqui: ela depende do status e é
 * verificada no controller e novamente (sob lock) no
 * ClientRegistrationService::signContract. O nome do signatário também não vem
 * do formulário — é sempre o titular do cadastro, gravado pelo servidor.
 */
class StoreContractSignatureRequest extends FormRequest
{
    /**
     * Permite o envio público da assinatura: o acesso é controlado pelo UUID
     * do cadastro na URL e pela elegibilidade verificada no servidor.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contract_signature' => ['required', new ValidSignatureData],
            'contract_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'contract_signature.required' => 'Desenhe sua assinatura antes de continuar.',
            'contract_accepted.required' => 'Confirme que leu e aceita o contrato.',
            'contract_accepted.accepted' => 'Confirme que leu e aceita o contrato.',
        ];
    }
}
