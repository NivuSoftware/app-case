<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete()
                ->unique(); 

            $table->string('clave_acceso', 49);

            $table->string('xml_generado_path', 255)->nullable();
            $table->string('xml_firmado_path', 255)->nullable();
            $table->string('xml_autorizado_path', 255)->nullable();

            $table->enum('estado_sri', [
                'PENDIENTE_ENVIO',
                'ENVIADO',
                'AUTORIZADO',
                'RECHAZADO',
            ])->default('PENDIENTE_ENVIO');

            $table->string('numero_autorizacion', 60)->nullable();
            $table->dateTime('fecha_autorizacion')->nullable();

            $table->text('mensaje_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_invoices');
    }
};
