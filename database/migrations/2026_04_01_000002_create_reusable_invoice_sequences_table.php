<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reusable_invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sequence')->unique();
            $table->string('num_factura', 50)->unique();
            $table->foreignId('released_from_queue_id')->nullable()->constrained('queued_sales')->nullOnDelete();
            $table->dateTime('reused_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reusable_invoice_sequences');
    }
};
