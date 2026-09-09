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
        Schema::create('installations', function (Blueprint $table) {
            $table->id();

            // Identidad pública y credencial secreta
            $table->string('installation_id', 26)->unique(); // ULID público
            $table->string('pairing_secret', 64);            // Secreto criptográfico
            $table->string('status')->default('unpaired');   // unpaired, pending_activation, active

            // Datos inyectados durante la activación
            $table->string('workspace')->nullable();
            $table->string('facility')->nullable();
            $table->json('owner_info')->nullable();
            $table->json('devices')->nullable();
            $table->string('contract_version')->nullable();

            // Configuración local sincronizable
            $table->json('settings')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('installations');
    }
};
