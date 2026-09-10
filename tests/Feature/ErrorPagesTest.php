<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public static function errorCodes(): array
    {
        return [
            '400' => ['400', 'Solicitação inválida'],
            '401' => ['401', 'Acesso necessário'],
            '403' => ['403', 'Acesso negado'],
            '404' => ['404', 'Página não encontrada'],
            '419' => ['419', 'Sessão expirada'],
            '429' => ['429', 'Muitas solicitações'],
            '500' => ['500', 'Algo deu errado'],
            '503' => ['503', 'Sistema temporariamente indisponível'],
        ];
    }

    public function test_unmatched_route_returns_real_404_with_custom_page(): void
    {
        $response = $this->get('/pagina-que-nao-existe');

        $response->assertStatus(404);

        $response->assertSee('Página não encontrada');
        $response->assertSee('Voltar para o início');

        $html = $response->getContent();
        $this->assertStringContainsString('<html lang="pt-BR">', $html);
        $this->assertStringContainsString('name="robots" content="noindex, nofollow"', $html);
        $this->assertStringContainsString('error-code', $html);
    }

    public function test_unmatched_route_404_carries_security_headers(): void
    {
        $this->get('/pagina-que-nao-existe')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeaderContains('Content-Security-Policy', "default-src 'self'");
    }

    public function test_404_detects_admin_context(): void
    {
        $html = $this->get('/admin/pagina-que-nao-existe')
            ->assertStatus(404)
            ->getContent();

        $this->assertStringContainsString('Painel da Locadora', $html);
        $this->assertStringNotContainsString('Cadastro de Clientes', $html);
        $this->assertStringNotContainsString('Locadora</span>', $html);
    }

    public function test_public_404_does_not_expose_admin_brand(): void
    {
        $html = $this->get('/pagina-que-nao-existe')->assertStatus(404)->getContent();

        $this->assertStringNotContainsString('Painel da Locadora', $html);
        $this->assertStringContainsString('Cadastro de Clientes', $html);
    }

    public function test_419_surfaces_session_expiry_copy_and_reload_action(): void
    {
        foreach (['GET', 'POST'] as $method) {
            $html = $this->renderError($method, code: '419');

            $this->assertStringContainsString('Sessão expirada', $html);
            $this->assertStringContainsString('Atualizar página', $html);
            $this->assertStringContainsString('data-error-reload', $html);
        }
    }

    public function test_reload_button_is_offered_only_for_get_requests(): void
    {
        foreach (['429', '500', '503'] as $code) {
            $get = $this->renderError('GET', code: $code);
            $post = $this->renderError('POST', code: $code);

            $this->assertStringContainsString('Tentar novamente', $get, "[{$code}] GET deve oferecer reload");
            $this->assertStringContainsString('data-error-reload', $get, "[{$code}] GET deve marcar o reload");

            $this->assertStringContainsString('Voltar para o início', $post, "[{$code}] POST não deve sugerir reload");
            $this->assertStringNotContainsString('data-error-reload', $post, "[{$code}] POST não deve ter botão de reload");
        }
    }

    public function test_403_and_404_offer_history_back_fallback_action(): void
    {
        foreach (['403', '404'] as $code) {
            $html = $this->renderError(code: $code);

            $this->assertStringContainsString('Voltar', $html, "[{$code}] deve oferecer ação voltar");
            $this->assertStringContainsString('data-error-back', $html, "[{$code}] deve usar o botão voltar do JS");
        }
    }

    #[DataProvider('errorCodes')]
    public function test_error_page_renders_expected_copy_for_each_code(string $code, string $title): void
    {
        $html = $this->renderError(code: $code);

        $this->assertStringContainsString("<title>{$code}", $html);
        $this->assertStringContainsString($title, $html);
    }

    #[DataProvider('errorCodes')]
    public function test_error_pages_never_leak_internal_details(string $code): void
    {
        $html = $this->renderError(code: $code);

        foreach (['Whoops', 'Exception', 'Stack trace', 'vendor/', 'laravel/framework', 'storage/', 'setlocale', 'SQLSTATE', 'refresh', 'DB_DATABASE'] as $needle) {
            $this->assertStringNotContainsString($needle, $html, "[{$code}] vazou detalhe interno: {$needle}");
        }
    }

    public function test_error_pages_contain_no_inline_scripts(): void
    {
        $html = $this->get('/pagina-que-nao-existe')->assertStatus(404)->getContent();

        $this->assertStringContainsString('type="module"', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_page_not_found_also_applies_to_admin_api_and_stays_html(): void
    {
        $response = $this->get('/admin/relatorios/999999');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Renderiza uma view de erro com método/path controlados, de modo a
     * testar o comportamento dependente do contexto sem depender de uma
     * exceção real ter acontecido antes.
     */
    private function renderError(string $method = 'GET', string $path = '/', ?string $code = null): string
    {
        $code ??= '404';
        $this->app->instance('request', Request::create($path, $method));

        return view("errors.{$code}")->render();
    }
}
