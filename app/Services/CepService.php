<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Busca o endereço automaticamente a partir do CEP,
 * usando a API pública ViaCEP.
 */
class CepService
{
    public function find(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        $response = Http::timeout(5)
            ->get("https://viacep.com.br/ws/{$cep}/json/");

        if ($response->failed() || $response->json('erro') === true) {
            return null;
        }

        return [
            'cep' => $response->json('cep'),
            'address' => $response->json('logradouro'),
            'neighborhood' => $response->json('bairro'),
            'city' => $response->json('localidade'),
            'state' => $response->json('uf'),
        ];
    }
}
