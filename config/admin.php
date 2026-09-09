<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Usuário administrativo
    |--------------------------------------------------------------------------
    |
    | Credenciais usadas pelo seeder AdminUserSeeder para criar a conta
    | de acesso ao painel da locadora. Devem ser definidas no .env e a
    | senha trocada em produção.
    |
    */
    'name' => env('ADMIN_NAME', 'Administrador'),
    'email' => env('ADMIN_EMAIL', 'admin@locadora.com.br'),
    'password' => env('ADMIN_PASSWORD'),
];
