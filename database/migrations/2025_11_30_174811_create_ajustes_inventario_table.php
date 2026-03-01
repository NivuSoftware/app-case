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
        Schema::create('ajustes_inventario', function (Blueprint $table) {
            $table->id();

            // Usuario que realizó el ajuste
            $table->foreignId('usuario_id')
                ->constrained('users');

            // Ubicación del producto
            $table->foreignId('bodega_id')
                ->constrained('bodegas');

            $table->foreignId('percha_id')
                ->nullable()
                ->constrained('perchas');

            // Producto ajustado
            $table->foreignId('producto_id')
                ->constrained('products');

            $table->integer('stock_inicial');
            $table->integer('stock_final');
            $table->integer('diferencia'); 

            $table->enum('tipo', ['positivo', 'negativo']);

            $table->text('motivo')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ajustes_inventario');
    }
};
