<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('producto_id')
                ->constrained('products')
                ->onDelete('cascade');

            $table->decimal('precio_unitario', 10, 2)->default(0);

            $table->decimal('precio_por_cantidad', 10, 2)->nullable();
            $table->integer('cantidad_min')->nullable();
            $table->integer('cantidad_max')->nullable();

            $table->decimal('precio_por_caja', 10, 2)->nullable();
            $table->integer('unidades_por_caja')->nullable();

            $table->string('moneda', 10)->default('USD');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
