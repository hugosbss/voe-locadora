<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Política de retenção de dados pessoais
    |--------------------------------------------------------------------------
    |
    | Define por quanto tempo cada cadastro (e seus documentos) permanece
    | armazenado após a criação, baseado no status. Os valores são lidos do
    | ambiente para permitir ajuste sem deploy. A exclusão é executada
    | somente pelo comando `php artisan cadastros:expurgo` (idempotente).
    |
    */

    'statuses' => [
        'novo' => (int) env('RETENTION_NOVO_EM_ANALISE_DAYS', 180),
        'em_analise' => (int) env('RETENTION_NOVO_EM_ANALISE_DAYS', 180),
        'aprovado' => (int) env('RETENTION_APROVADO_DAYS', 1825),
        'reprovado' => (int) env('RETENTION_REPROVADO_DAYS', 183),
    ],

    // Cadastros com retenção já expirada podem ser excluídos quando a
    // consulta de purga for executada.
];
