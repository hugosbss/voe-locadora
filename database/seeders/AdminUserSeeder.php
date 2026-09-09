<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Cria o usuário administrativo usado no painel da locadora.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => config('admin.email')],
            [
                'name' => config('admin.name'),
                'password' => config('admin.password'),
            ]
        );
    }
}
