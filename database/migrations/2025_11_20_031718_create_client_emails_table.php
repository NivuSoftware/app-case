<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_emails', function (Blueprint $table) {
            $table->id();

            // Relación al cliente
            $table->foreignId('client_id')
                  ->constrained('clients')
                  ->onDelete('cascade');

            // Email del cliente
            $table->string('email', 191);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_emails');
    }
};
