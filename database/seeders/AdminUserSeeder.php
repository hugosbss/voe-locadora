<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    /**
     * Cria o usuário administrativo usado no painel da locadora.
     *
     * A senha vem obrigatoriamente do ambiente (ADMIN_PASSWORD): não existe
     * valor padrão em código, para que uma senha fraca/ausente não seja
     * usada por engano em produção.
     */
    public function run(): void
    {
        $password = config('admin.password');

        if (! is_string($password) || $password === '') {
            throw new RuntimeException(
                'Defina a variável de ambiente ADMIN_PASSWORD antes de criar o administrador. '.
                'Veja: php artisan admin:provision --help'
            );
        }

        if (strlen($password) < 12) {
            throw new RuntimeException(
                'ADMIN_PASSWORD deve ter pelo menos 12 caracteres.'
            );
        }

        User::query()->updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => config('admin.name'),
                'password' => $password,
            ]
        );
    }
}
