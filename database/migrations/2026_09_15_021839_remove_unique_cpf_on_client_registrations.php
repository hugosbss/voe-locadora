<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // O mesmo CPF pode ser usado em vários cadastros. A unicidade de CPF
        // (que relacionava-se apenas à dimensão de negócio "cliente já inscrito")
        // é removida; a coluna continua normalizada e validada por formato.
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->dropUnique('client_registrations_cpf_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->unique('cpf');
        });
    }
};
