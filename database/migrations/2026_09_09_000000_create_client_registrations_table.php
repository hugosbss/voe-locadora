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
        Schema::create('client_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('cpf', 14)->unique();
            $table->date('birth_date');
            $table->string('phone', 20);
            $table->string('whatsapp', 20);
            $table->string('email');

            $table->string('cep', 9);
            $table->string('address');
            $table->string('address_number', 20);
            $table->string('neighborhood');
            $table->string('city');
            $table->string('state', 2);

            $table->string('cnh_number');
            $table->string('cnh_category', 2);
            $table->date('cnh_expiry_date');

            $table->string('cnh_front_path')->nullable();
            $table->string('cnh_back_path')->nullable();
            $table->string('proof_of_residence_path')->nullable();
            $table->string('selfie_path')->nullable();

            $table->string('facial_status', 30)->default('pending');
            $table->string('status', 20)->default('novo');

            $table->boolean('veracity_declaration_accepted')->default(false);
            $table->boolean('privacy_policy_accepted')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_registrations');
    }
};
