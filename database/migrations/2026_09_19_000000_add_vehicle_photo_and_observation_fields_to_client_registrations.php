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
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->string('vehicle_pickup_photo_path')->nullable()->after('selfie_path');
            $table->string('vehicle_delivery_photo_path')->nullable()->after('vehicle_pickup_photo_path');
            $table->text('vehicle_observation')->nullable()->after('vehicle_delivery_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->dropColumn(['vehicle_pickup_photo_path', 'vehicle_delivery_photo_path', 'vehicle_observation']);
        });
    }
};
