<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_registrations', function (Blueprint $table) {
            // Identificador público não previsível, independente do id numérico interno.
            $table->uuid('uuid')->nullable()->unique()->after('id');

            // Consentimento LGPD: versão vigente no aceite e data/hora.
            $table->string('privacy_policy_version', 50)->default('1.0')->after('privacy_policy_accepted');
            $table->timestamp('privacy_policy_accepted_at')->nullable()->after('privacy_policy_version');
            $table->timestamp('veracity_declaration_accepted_at')->nullable()->after('privacy_policy_accepted_at');

            // Índices para consulta e retenção.
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['status']);
            $table->dropColumn(['veracity_declaration_accepted_at', 'privacy_policy_accepted_at', 'privacy_policy_version', 'uuid']);
        });
    }
};
