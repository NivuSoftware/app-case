<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();

            // Relación con la cabecera de compra
            $table->foreignId('purchase_id')
                ->constrained('purchases')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Datos del pago
            $table->date('fecha_pago');
            $table->decimal('monto', 12, 2);
            $table->string('metodo', 50);           // efectivo, transferencia, etc.
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();

            // Usuario que registró el pago
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_payments');
    }
};
