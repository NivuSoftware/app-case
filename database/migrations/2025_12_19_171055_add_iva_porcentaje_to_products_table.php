<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Porcentaje de IVA que graba este producto: 0.00, 15.00, etc.
            $table->decimal('iva_porcentaje', 5, 2)
                ->default(15.00)
                ->after('stock_minimo');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('iva_porcentaje');
        });
    }
};
