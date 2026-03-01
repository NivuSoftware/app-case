<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();

            // Identificación
            $table->string('tipo_identificacion', 20); // CEDULA, RUC, PASAPORTE
            $table->string('identificacion', 20);

            // Nombre comercial / nombres+apellidos / razón social
            $table->string('business', 191);

            // Tipo de cliente: natural / juridico
            $table->enum('tipo', ['natural', 'juridico']);

            // Contacto y ubicación
            $table->string('telefono', 50)->nullable();
            $table->string('direccion', 255)->nullable();
            $table->string('ciudad', 100)->nullable();

            // Estado del cliente
            $table->string('estado', 20)->default('activo'); // activo / inactivo

            $table->timestamps();

            // Evitar clientes duplicados por tipo + identificación
            $table->unique(
                ['tipo_identificacion', 'identificacion'],
                'clients_identificacion_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
