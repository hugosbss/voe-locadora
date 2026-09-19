<?php

namespace Tests\Feature;

use App\Enums\AdminRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_page_requires_authentication(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_the_users_page(): void
    {
        $admin = User::factory()->create(['name' => 'Ana Admin']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Usuários')
            ->assertSee('Ana Admin')
            ->assertSee($admin->email);
    }

    public function test_admin_can_create_a_new_admin_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.users.store'), [
                'name' => 'João Operador',
                'email' => 'JOAO@OPERADOR.COM',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role' => 'superadmin',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->firstWhere('email', 'joao@operador.com');

        $this->assertNotNull($user);
        $this->assertSame('João Operador', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertSame(AdminRole::Admin->value, $user->role);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_cannot_create_user_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'novo@locadora.com.br']);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.users.store'), [
                'name' => 'Novo Operador',
                'email' => 'novo@locadora.com.br',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_cannot_create_user_with_unconfirmed_short_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.users.store'), [
                'name' => 'Novo Operador',
                'email' => 'novo@locadora.com.br',
                'password' => 'curta',
                'password_confirmation' => '',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_guest_cannot_create_a_user(): void
    {
        $this->post(route('admin.users.store'), [
            'name' => 'Novo Operador',
            'email' => 'novo@locadora.com.br',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertRedirect(route('admin.login'));

        $this->assertDatabaseCount('users', 0);
    }
}
