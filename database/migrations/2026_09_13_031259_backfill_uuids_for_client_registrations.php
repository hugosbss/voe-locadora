<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Preenche UUIDs para cadastros históricos que ainda não possuem um
     * (coluna adicionada depois da criação da tabela).
     *
     * Migração não destrutiva: registros existentes com uuid já preenchido
     * não são alterados; nenhum dado é removido ou sobrescrito.
     */
    public function up(): void
    {
        DB::table('client_registrations')
            ->whereNull('uuid')
            ->orderBy('id')
            ->chunkById(100, function ($registrations): void {
                foreach ($registrations as $registration) {
                    DB::table('client_registrations')
                        ->where('id', $registration->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
    }

    /**
     * Não desfaz nada: os UUIDs gerados são mantidos (reversão de UUIDs
     * já em uso nas URLs do painel seria destrutiva).
     */
    public function down(): void
    {
        // Intencionalmente vazio.
    }
};
