<?php

namespace App\Http\Requests;

use App\Rules\UploadBatchMax;
use App\Rules\ValidCpf;
use App\Rules\ValidSignatureData;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRegistrationRequest extends FormRequest
{
    /**
     * Permite o envio público do formulário.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza o CPF (apenas dígitos) antes de validar para que a regra de
     * formato compare sempre o mesmo padrão (11122233344).
     */
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->has('cpf')) {
            $this->merge(['cpf' => preg_replace('/\D/', '', (string) $this->input('cpf')) ?? '']);
        }
    }

    /**
     * Regras de validação do cadastro.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $fileFields = ['cnh_front_file', 'cnh_back_file', 'proof_of_residence_file', 'selfie_file'];

        $imageRules = [
            'required',
            'image',
            'mimes:jpg,jpeg,png,webp',
            'max:5120',
            'dimensions:min_width=200,min_height=200,max_width=8000,max_height=8000',
            // Limite total da requisição de upload (soma dos arquivos).
            new UploadBatchMax($fileFields, 12288),
        ];

        $fileRules = [];

        foreach ($fileFields as $field) {
            $fileRules[$field] = $imageRules;
        }

        return [
            'full_name' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\.\'\-]+$/u'],
            'cpf' => ['required', new ValidCpf],
            'birth_date' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->startOfDay()->format('Y-m-d')],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'whatsapp' => ['required', 'string', 'max:20', 'regex:/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/'],
            'email' => ['required', 'email', 'max:255'],

            'cep' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'address' => ['required', 'string', 'max:255'],
            'address_number' => ['required', 'string', 'max:20'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2', Rule::in(array_keys(config('locations.states', [])))],

            'cnh_number' => ['required', 'string', 'max:20'],
            'cnh_category' => ['required', 'string', 'max:2', Rule::in(config('locations.cnh_categories', []))],
            'cnh_expiry_date' => ['required', 'date', 'after:today'],

            ...$fileRules,

            'veracity_declaration_accepted' => ['required', 'accepted'],
            'privacy_policy_accepted' => ['required', 'accepted'],

            'contract_signature' => ['required', new ValidSignatureData],
            'contract_signer_name' => ['required', 'string', 'max:255', 'same:full_name'],
            'contract_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * Mensagens de erro em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Informe o nome completo.',
            'full_name.regex' => 'O nome completo contém caracteres inválidos.',
            'cpf.required' => 'Informe o CPF.',
            'cpf.cpf' => 'Informe um CPF válido.',
            'birth_date.required' => 'Informe a data de nascimento.',
            'birth_date.date' => 'Informe uma data de nascimento válida.',
            'birth_date.before_or_equal' => 'É necessário ter pelo menos 18 anos para se cadastrar.',
            'phone.required' => 'Informe o telefone.',
            'phone.regex' => 'Informe um telefone válido.',
            'whatsapp.required' => 'Informe o número de WhatsApp.',
            'whatsapp.regex' => 'Informe um WhatsApp válido.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',

            'cep.required' => 'Informe o CEP.',
            'cep.regex' => 'Informe um CEP válido.',
            'address.required' => 'Informe a rua.',
            'address_number.required' => 'Informe o número.',
            'neighborhood.required' => 'Informe o bairro.',
            'city.required' => 'Informe a cidade.',
            'state.required' => 'Informe o estado.',
            'state.in' => 'Selecione um estado válido.',

            'cnh_number.required' => 'Informe o número da CNH.',
            'cnh_category.required' => 'Informe a categoria da CNH.',
            'cnh_category.in' => 'Selecione uma categoria válida.',
            'cnh_expiry_date.required' => 'Informe a data de validade da CNH.',
            'cnh_expiry_date.date' => 'Informe uma data válida.',
            'cnh_expiry_date.after' => 'Informe uma data de validade futura.',

            'cnh_front_file.required' => 'Envie a foto da CNH (frente).',
            'cnh_front_file.image' => 'A foto da CNH (frente) deve ser uma imagem.',
            'cnh_front_file.mimes' => 'A foto da CNH (frente) deve ser JPG, JPEG, PNG ou WEBP.',
            'cnh_front_file.max' => 'A foto da CNH (frente) deve ter no máximo 5 MB.',
            'cnh_front_file.dimensions' => 'A foto da CNH (frente) possui resolução inválida.',
            'cnh_front_file.upload_batch_max' => 'O total dos arquivos enviados excede o limite permitido.',

            'cnh_back_file.required' => 'Envie a foto da CNH (verso).',
            'cnh_back_file.image' => 'A foto da CNH (verso) deve ser uma imagem.',
            'cnh_back_file.mimes' => 'A foto da CNH (verso) deve ser JPG, JPEG, PNG ou WEBP.',
            'cnh_back_file.max' => 'A foto da CNH (verso) deve ter no máximo 5 MB.',
            'cnh_back_file.dimensions' => 'A foto da CNH (verso) possui resolução inválida.',
            'cnh_back_file.upload_batch_max' => 'O total dos arquivos enviados excede o limite permitido.',

            'proof_of_residence_file.required' => 'Envie o comprovante de residência.',
            'proof_of_residence_file.image' => 'O comprovante deve ser uma imagem.',
            'proof_of_residence_file.mimes' => 'O comprovante deve ser JPG, JPEG, PNG ou WEBP.',
            'proof_of_residence_file.max' => 'O comprovante deve ter no máximo 5 MB.',
            'proof_of_residence_file.dimensions' => 'O comprovante possui resolução inválida.',
            'proof_of_residence_file.upload_batch_max' => 'O total dos arquivos enviados excede o limite permitido.',

            'selfie_file.required' => 'Envie a selfie para validação facial.',
            'selfie_file.image' => 'A selfie deve ser uma imagem.',
            'selfie_file.mimes' => 'A selfie deve ser JPG, JPEG, PNG ou WEBP.',
            'selfie_file.max' => 'A selfie deve ter no máximo 5 MB.',
            'selfie_file.dimensions' => 'A selfie possui resolução inválida.',
            'selfie_file.upload_batch_max' => 'O total dos arquivos enviados excede o limite permitido.',

            'veracity_declaration_accepted.required' => 'Você deve aceitar a declaração de veracidade.',
            'veracity_declaration_accepted.accepted' => 'Você deve aceitar a declaração de veracidade.',
            'privacy_policy_accepted.required' => 'Você deve aceitar a Política de Privacidade.',
            'privacy_policy_accepted.accepted' => 'Você deve aceitar a Política de Privacidade.',

            'contract_signature.required' => 'Desenhe sua assinatura antes de concluir o cadastro.',
            'contract_signer_name.required' => 'Informe o nome do signatário.',
            'contract_signer_name.same' => 'O nome do signatário deve ser igual ao informado no cadastro.',
            'contract_accepted.required' => 'Você deve aceitar os termos do contrato.',
            'contract_accepted.accepted' => 'Você deve aceitar os termos do contrato.',
        ];
    }
}
