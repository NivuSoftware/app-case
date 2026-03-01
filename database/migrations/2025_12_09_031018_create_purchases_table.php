<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();

            // Proveedor
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Usuario que registra la compra
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Bodega donde entran los productos
            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Info documental
            $table->date('fecha_compra');
            $table->string('numero_documento')->nullable(); 
            $table->string('tipo_documento', 30)->nullable(); 

            // Totales
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('impuesto', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Estado de la compra
            $table->string('estado', 20)->default('registrada'); 

            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
