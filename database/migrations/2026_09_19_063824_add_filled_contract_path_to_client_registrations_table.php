<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_registrations', function (Blueprint $table): void {
            $table->string('filled_contract_path')->nullable()->after('contract_signed_pdf_path');
        });
    }

    public function down(): void
    {
        Schema::table('client_registrations', function (Blueprint $table): void {
            $table->dropColumn('filled_contract_path');
        });
    }
};
