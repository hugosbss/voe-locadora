<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Busca o endereço automaticamente a partir do CEP usando a API pública
 * ViaCEP.
 *
 * O endpoint unicamente expõe resposta controlada pela aplicação: nomes de
 * rua/bairro/cidade/UF validados ou um erro genérico. Timeout, indisponibi-
 * lidade e respostas malformadas da API externa nunca são repassados ao
 * cliente e não registram o CEP consultado nos logs.
 */
class CepService
{
    private const TIMEOUT_SECONDS = 5;

    public function find(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->retry(1, 250)
                ->acceptJson()
                ->get("https://viacep.com.br/ws/{$cep}/json/");
        } catch (ConnectionException|RequestException $e) {
            Log::warning('CEP lookup failed (network/upstream)', ['kind' => get_class($e)]);

            return null;
        }

        if ($response->failed()) {
            return null;
        }

        $body = $response->json();

        if (! is_array($body) || ($body['erro'] ?? false) === true) {
            return null;
        }

        $cepReturned = is_string($body['cep'] ?? null) ? $body['cep'] : null;

        if (! $cepReturned) {
            return null;
        }

        return [
            'cep' => $cepReturned,
            'address' => $this->stringOrNull($body['logradouro'] ?? null),
            'neighborhood' => $this->stringOrNull($body['bairro'] ?? null),
            'city' => $this->stringOrNull($body['localidade'] ?? null),
            'state' => $this->stringOrNull($body['uf'] ?? null),
        ];
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
