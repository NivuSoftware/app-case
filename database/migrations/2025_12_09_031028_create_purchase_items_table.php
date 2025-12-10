<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();

            // Cabecera de compra
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Producto
            $table->foreignId('producto_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Bodega y percha donde se guarda este producto
            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('percha_id')
                ->nullable()
                ->constrained('perchas')
                ->cascadeOnUpdate()
                ->nullOnDelete();

       
            $table->integer('cantidad')->default(0);
            $table->decimal('costo_unitario', 12, 4)->default(0); 
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
    }
};
