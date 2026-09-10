<?php

namespace Tests\Feature;

use App\Models\ClientRegistration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEntryPointTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_at_root_is_sent_to_admin_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_user_at_root_goes_to_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_public_form_is_no_longer_the_root_page(): void
    {
        $this->get('/')
            ->assertRedirect();
    }

    public function test_public_form_lives_at_cadastro_and_needs_no_authentication(): void
    {
        $this->get('/cadastro')
            ->assertOk()
            ->assertSee('Cadastro de Cliente')
            ->assertSee('Enviar cadastro');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_shows_real_stats_to_admin(): void
    {
        ClientRegistration::factory()->count(4)->create(['status' => 'novo']);
        ClientRegistration::factory()->count(2)->create(['status' => 'aprovado']);
        ClientRegistration::factory()->count(1)->create(['status' => 'reprovado']);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Visão geral')
            ->assertSee('7');
    }

    public function test_dashboard_offers_link_access_quickcard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Link de cadastro')
            ->assertSee(url('/cadastro'))
            ->assertSee('data-js-copy');
    }

    public function test_registration_link_page_is_protected(): void
    {
        $this->get(route('admin.registration-link'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_registration_link_page_shows_public_url_to_admin(): void
    {
        $publicUrl = url('/cadastro');

        $this->actingAs(User::factory()->create())
            ->get(route('admin.registration-link'))
            ->assertOk()
            ->assertSee('Link de cadastro')
            ->assertSee('Copie e envie ao cliente.')
            ->assertSee($publicUrl)
            ->assertSee('data-js-copy')
            ->assertSee('data-js-share')
            ->assertSee('não dá acesso ao painel', false);
    }

    public function test_side_nav_marks_current_section_on_registrations(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.registrations.index'))
            ->assertOk()
            ->assertSee('aria-current="page"', false)
            ->assertSee('Cadastros');
    }

    public function test_side_nav_marks_current_section_on_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }
}
