<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate limiting
    |--------------------------------------------------------------------------
    |
    | Limites aplicados às rotas públicas e de autenticação. Valores lidos
    | do ambiente permitem ajuste sem deploy. As janelas são razoáveis para
    | uso legítimo em celular e restritivas o suficiente para dificultar
    | automação.
    |
    */

    'limits' => [
        // Envio do cadastro (ação que persiste dados e arquivos).
        'cadastro_max_attempts' => (int) env('RATE_LIMIT_CADASTRO_PER_MINUTE', 8),
        'cadastro_decay_minutes' => (int) env('RATE_LIMIT_CADASTRO_DECAY_MINUTES', 15),

        // Consulta de CEP (proxy para API externa).
        'cep_per_minute' => (int) env('RATE_LIMIT_CEP_PER_MINUTE', 30),

        // Autenticação administrativa (janela curta).
        'login_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_MINUTE', 5),

        // Bloqueio progressivo após falhas repetidas.
        'login_lock_threshold' => (int) env('LOGIN_LOCK_THRESHOLD', 10),
        'login_lock_minutes' => (int) env('LOGIN_LOCK_MINUTES', 30),
    ],
];
