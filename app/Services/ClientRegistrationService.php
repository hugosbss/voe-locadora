<?php

namespace App\Services;

use App\Models\ClientRegistration;

/**
 * Orquestra a criação de um cadastro de cliente, incluindo a
 * movimentação dos arquivos enviados para o armazenamento privado.
 */
class ClientRegistrationService
{
    public function __construct(
        private readonly DocumentStorageService $documentStorage,
        private readonly FacialValidationService $facialValidation,
    ) {}

    /**
     * Cria o cadastro do cliente com os dados validados e armazena
     * as fotos/documentos em área privada.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ClientRegistration
    {
        $uuid = $this->documentStorage->newRegistrationUuid();

        $registration = new ClientRegistration;

        foreach (ClientRegistration::DOCUMENTS as $document => $label) {
            $field = "{$document}_path";
            $file = $data["{$document}_file"] ?? null;

            $registration->{$field} = $file
                ? $this->documentStorage->store($file, $uuid, $document)
                : null;
        }

        $registration->fill($this->mapFormData($data));
        $registration->save();

        $this->facialValidation->markAsPending($registration);

        return $registration;
    }

    /**
     * Remove dados temporários de arquivos que não devem ir para o banco.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function mapFormData(array $data): array
    {
        unset($data['cnh_front_file'], $data['cnh_back_file'], $data['proof_of_residence_file'], $data['selfie_file']);

        return $data;
    }
}
