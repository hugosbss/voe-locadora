<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup
    |--------------------------------------------------------------------------
    |
    | Configuração do comando `php artisan backup:concluir` (mysqldump +
    | cópia do armazenamento privado). Nenhuma credencial é armazenada aqui:
    | o comando lê as credenciais do MySQL a partir das variáveis de
    | ambiente DB_* utilizadas pela própria aplicação.
    |
    */

    'enabled' => env('BACKUP_ENABLED', false),

    'path' => env('BACKUP_PATH', storage_path('backups')),

    // Dias de retenção dos backups antes da limpeza automática.
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
];
