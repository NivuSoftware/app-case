<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Agregamos la columna IVA si no existe
            if (!Schema::hasColumn('purchases', 'iva')) {
                $table->decimal('iva', 12, 2)
                      ->default(0)
                      ->after('subtotal');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (Schema::hasColumn('purchases', 'iva')) {
                $table->dropColumn('iva');
            }
        });
    }
};
