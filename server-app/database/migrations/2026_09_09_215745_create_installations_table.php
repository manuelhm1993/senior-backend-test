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

            // FK nullable: una instalación puede llegar "sin asociar" todavía
            $table->foreignId('facility_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->nullOnUpdate();

            // Identidad pública que llega desde el Client (no es secreta)
            $table->string('installation_id', 26)->unique();

            // Nunca se guarda el secreto en texto plano — solo su hash
            $table->string('pairing_secret_hash');

            // Ciclo de vida explícito, requisito del brief (Sección 3.2.5)
            $table->enum('status', ['unpaired', 'pending_activation', 'active'])
                ->default('unpaired');

            $table->string('contract_version')->nullable();

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
