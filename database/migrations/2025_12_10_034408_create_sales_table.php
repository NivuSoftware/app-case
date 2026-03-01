<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->nullable()
                ->constrained('clients')
                ->nullOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->restrictOnDelete();

            $table->dateTime('fecha_venta');

            $table->string('tipo_documento', 20)->default('FACTURA');
            $table->string('num_factura', 50)->nullable(); 

            // Totales globales (IVA e impuestos aplican al total, no por ítem)
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('impuesto', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);

            $table->string('estado', 20)->default('pendiente'); // pendiente | pagada | anulada
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
