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
        Schema::table('client_registrations', function (Blueprint $table): void {
            $table->boolean('contract_signed')->default(false)->after('privacy_policy_version');
            $table->timestamp('contract_signed_at')->nullable()->after('contract_signed');
            $table->string('contract_version', 20)->nullable()->after('contract_signed_at');
            $table->string('contract_signed_pdf_path')->nullable()->after('contract_version');
            $table->string('contract_signature_path')->nullable()->after('contract_signed_pdf_path');
            $table->string('contract_signer_name')->nullable()->after('contract_signature_path');
            $table->string('contract_signer_ip', 45)->nullable()->after('contract_signer_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_registrations', function (Blueprint $table): void {
            $table->dropColumn([
                'contract_signed',
                'contract_signed_at',
                'contract_version',
                'contract_signed_pdf_path',
                'contract_signature_path',
                'contract_signer_name',
                'contract_signer_ip',
            ]);
        });
    }
};
