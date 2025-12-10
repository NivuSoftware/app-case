<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sale_id')
                ->constrained('sales')
                ->cascadeOnDelete();

            $table->foreignId('producto_id')
                ->constrained('products')
                ->restrictOnDelete();

            $table->string('descripcion', 255); 

            $table->integer('cantidad'); 
            $table->decimal('precio_unitario', 10, 4);
            $table->decimal('descuento', 10, 2)->default(0);

            // total de la línea = cantidad * precio_unitario - descuento
            $table->decimal('total', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
