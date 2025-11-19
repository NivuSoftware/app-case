<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('products')
                ->onDelete('cascade');

            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->onDelete('cascade');

            $table->foreignId('percha_id')
                ->nullable()
                ->constrained('perchas')
                ->onDelete('set null');

            $table->integer('stock_actual')->default(0);
            $table->integer('stock_reservado')->default(0);

            $table->timestamps();

            // Evitamos duplicar inventario por producto+bodega+percha
            $table->unique(['producto_id', 'bodega_id', 'percha_id'], 'inventario_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario');
    }
};
