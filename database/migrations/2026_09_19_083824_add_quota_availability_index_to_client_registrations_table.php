<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índice de apoio para a consulta de disponibilidade por período:
     * filtra por status consumidor e sobreposição de start_date/end_date.
     */
    public function up(): void
    {
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->index(
                ['vehicle_id', 'quota_type_id', 'status', 'start_date', 'end_date'],
                'client_registrations_quota_availability_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->dropIndex('client_registrations_quota_availability_index');
        });
    }
};
