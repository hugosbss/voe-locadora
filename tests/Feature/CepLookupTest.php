<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CepLookupTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_controlled_address_for_valid_cep(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/01310100/json/' => Http::response([
                'cep' => '01310-100',
                'logradouro' => 'Avenida Paulista',
                'bairro' => 'Bela Vista',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
            ]),
        ]);

        $this->get('/cep?cep=01310-100')
            ->assertOk()
            ->assertJson([
                'cep' => '01310-100',
                'address' => 'Avenida Paulista',
                'neighborhood' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'SP',
            ]);
    }

    public function test_returns_404_for_unknown_cep(): void
    {
        Http::fake([
            'https://viacep.com.br/ws/99999999/json/' => Http::response(['erro' => true]),
        ]);

        $this->get('/cep?cep=99999-999')
            ->assertNotFound()
            ->assertJson(['error' => 'CEP não encontrado.']);
    }

    public function test_returns_404_without_revealing_upstream_failure(): void
    {
        // API externa fora do ar: resposta genérica, sem detalhes internos.
        Http::fake([
            'https://viacep.com.br/ws/01310100/json/' => fn () => Http::response(status: 500),
        ]);

        $this->get('/cep?cep=01310-100')
            ->assertNotFound()
            ->assertJson(['error' => 'CEP não encontrado.']);
    }

    public function test_rejects_invalid_cep_format(): void
    {
        $this->getJson('/cep?cep=abc')->assertStatus(422);
    }
}
