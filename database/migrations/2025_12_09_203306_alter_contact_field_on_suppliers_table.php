<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contacto', 255)->nullable()->after('ruc');

            if (Schema::hasColumn('suppliers', 'contacto_nombre')) {
                $table->dropColumn('contacto_nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            $table->string('contacto_nombre', 255)->nullable()->after('ruc');

            if (Schema::hasColumn('suppliers', 'contacto')) {
                $table->dropColumn('contacto');
            }
        });
    }
};
