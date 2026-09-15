<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_requires_authentication(): void
    {
        $this->get(route('admin.registrations.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@locadora.com.br',
            'password' => Hash::make('secret-password'),
        ]);

        $this->post(route('admin.login.attempt'), [
            'email' => 'admin@locadora.com.br',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated();
    }

    public function test_admin_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@locadora.com.br',
        ]);

        $this->post(route('admin.login.attempt'), [
            'email' => 'admin@locadora.com.br',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_admin_login_redirects_to_intended_admin_page(): void
    {
        User::factory()->create([
            'email' => 'admin@locadora.com.br',
            'password' => Hash::make('secret-password'),
        ]);

        $this->get(route('admin.registrations.index'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.login.attempt'), [
            'email' => 'admin@locadora.com.br',
            'password' => 'secret-password',
        ])->assertRedirect(route('admin.registrations.index'));

        $this->assertAuthenticated();
    }
}
