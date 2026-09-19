<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('model');
            $table->string('plate', 20)->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('quota_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedInteger('days');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('vehicle_quota_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quota_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['vehicle_id', 'quota_type_id']);
        });

        Schema::table('client_registrations', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('quota_type_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->date('start_date')->nullable()->after('quota_type_id');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unsignedInteger('quota_days')->nullable()->after('end_date');
        });
    }

    public function down(): void
    {
        Schema::table('client_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropConstrainedForeignId('quota_type_id');
            $table->dropColumn(['start_date', 'end_date', 'quota_days']);
        });

        Schema::dropIfExists('vehicle_quota_configurations');
        Schema::dropIfExists('quota_types');
        Schema::dropIfExists('vehicles');
    }
};
