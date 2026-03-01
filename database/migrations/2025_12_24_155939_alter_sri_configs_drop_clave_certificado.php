<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sri_configs', function (Blueprint $table) {
            if (Schema::hasColumn('sri_configs', 'clave_certificado')) {
                $table->dropColumn('clave_certificado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sri_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('sri_configs', 'clave_certificado')) {
                $table->text('clave_certificado')->nullable()->after('ruta_certificado');
            }
        });
    }
};
