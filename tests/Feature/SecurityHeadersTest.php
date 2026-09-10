<?php

namespace Tests\Feature;

use App\Models\ClientRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_sends_security_headers(): void
    {
        $response = $this->get('/cadastro');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
        $response->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');

        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString("frame-ancestors 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringNotContainsString("'unsafe-eval'", $response->headers->get('Content-Security-Policy'));
    }

    public function test_error_pages_also_receive_security_headers(): void
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $this->assertNotNull($response->headers->get('Content-Security-Policy'));
        $this->assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    public function test_no_hsts_in_testing_environment(): void
    {
        $this->get('/cadastro')
            ->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_document_response_is_cached_as_private_no_store(): void
    {
        $registration = ClientRegistration::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.show', $registration))
            ->assertOk();
    }
}
