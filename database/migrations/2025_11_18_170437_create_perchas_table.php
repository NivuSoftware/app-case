<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perchas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bodega_id')
                ->constrained('bodegas')
                ->onDelete('cascade');

            $table->string('codigo'); 
            $table->text('descripcion')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perchas');
    }
};
