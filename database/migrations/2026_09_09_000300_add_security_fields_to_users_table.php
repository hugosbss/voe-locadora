<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Papel administrativo (base para evolução de permissões).
            $table->string('role', 30)->default('admin')->after('remember_token');

            // MFA via TOTP (RFC 6238). O segredo é criptografado pelo model
            // (cast 'encrypted'); recovery codes são armazenados como hash.
            // TEXT evita truncamento do valor criptografado.
            $table->text('two_factor_secret')->nullable()->after('role');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_enabled_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_enabled_at', 'two_factor_recovery_codes', 'two_factor_secret', 'role']);
        });
    }
};
