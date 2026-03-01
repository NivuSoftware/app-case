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
        Schema::table('electronic_invoices', function (Blueprint $table) {
            $table->jsonb('mensajes_sri_json')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('electronic_invoices', function (Blueprint $table) {
            $table->dropColumn('mensajes_sri_json');
        });
    }

};
