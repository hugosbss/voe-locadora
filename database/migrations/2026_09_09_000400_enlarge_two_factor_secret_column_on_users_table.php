<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O valor criptografado (cast 'encrypted') do segredo TOTP excede 255
        // caracteres; armazenar como TEXT garante compatibilidade sem perder
        // a proteção em repouso.
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('two_factor_secret', 255)->nullable()->change();
        });
    }
};
