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
        /*
         * Envio do cadastro (ação que persiste dados e arquivos).
         *
         * A política usa dois contadores independentes por IP:
         * - max_attempts: conta TODAS as submissões (inclusive 422 de validação),
         *   protegendo contra flood automatizado de requisições (CPU/upload/banco);
         * - success_max_attempts: conta apenas cadastros efetivamente criados
         *   (POST aceito), permitindo que o usuário corrija e reenvie livremente
         *   sem esgotar o limite e, ao mesmo tempo, impede spam de cadastros reais.
         *
         * O GET /cadastro não é limitado: apenas renderiza o formulário e não é
         * contabilizado como tentativa de envio.
         */
        'cadastro' => [
            'max_attempts' => (int) env('RATE_LIMIT_CADASTRO_MAX_ATTEMPTS', 60),
            'decay_minutes' => (int) env('RATE_LIMIT_CADASTRO_DECAY_MINUTES', 15),
            'success_max_attempts' => (int) env('RATE_LIMIT_CADASTRO_SUCCESS_MAX_ATTEMPTS', 10),
            'success_decay_minutes' => (int) env('RATE_LIMIT_CADASTRO_SUCCESS_DECAY_MINUTES', 15),
        ],

        // Consulta de CEP (proxy para API externa).
        'cep_per_minute' => (int) env('RATE_LIMIT_CEP_PER_MINUTE', 30),

        // Consulta de disponibilidade de cotas do formulário público.
        'quota_availability_per_minute' => (int) env('RATE_LIMIT_QUOTA_AVAILABILITY_PER_MINUTE', 60),

        // Autenticação administrativa (janela curta).
        'login_per_minute' => (int) env('RATE_LIMIT_LOGIN_PER_MINUTE', 5),

        // Solicitação de link de recuperação de senha (por IP).
        'password_reset' => [
            'max_attempts' => (int) env('RATE_LIMIT_PASSWORD_RESET', 6),
            'decay_minutes' => (int) env('RATE_LIMIT_PASSWORD_RESET_DECAY_MINUTES', 10),
        ],

        // Bloqueio progressivo após falhas repetidas.
        'login_lock_threshold' => (int) env('LOGIN_LOCK_THRESHOLD', 10),
        'login_lock_minutes' => (int) env('LOGIN_LOCK_MINUTES', 30),
    ],
];
