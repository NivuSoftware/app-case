<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queued_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('caja_id');
            $table->foreignId('bodega_id')->constrained('bodegas')->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('client_email_id')->nullable()->constrained('client_emails')->nullOnDelete();
            $table->string('email_destino', 255)->nullable();
            $table->dateTime('fecha_venta');
            $table->string('tipo_documento', 20)->default('FACTURA');
            $table->text('observaciones')->nullable();
            $table->boolean('iva_enabled')->default(true);
            $table->json('payload_json');
            $table->string('status', 20)->default('QUEUED');
            $table->unsignedInteger('duration_seconds')->default(60);
            $table->unsignedInteger('remaining_seconds')->default(60);
            $table->dateTime('execute_at')->nullable()->index();
            $table->unsignedInteger('schedule_version')->default(1);
            $table->string('reserved_num_factura', 50)->nullable()->index();
            $table->unsignedInteger('reserved_sequence')->nullable()->index();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'caja_id', 'bodega_id', 'status'], 'queued_sales_user_box_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queued_sales');
    }
};
