<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('electronic_invoices', function (Blueprint $table) {

            // ✅ clave_acceso: asegurar longitud y unique
            if (Schema::hasColumn('electronic_invoices', 'clave_acceso')) {
                // Si ya existe como string, esto solo aplica si quieres forzar 49:
                // $table->string('clave_acceso', 49)->change();
                // Pero change() requiere doctrine/dbal.
            } else {
                $table->string('clave_acceso', 49)->nullable();
            }

            // ✅ Estado e índice
            if (!Schema::hasColumn('electronic_invoices', 'estado_sri')) {
                $table->string('estado_sri', 30)->default('PENDIENTE')->after('xml_autorizado_path');
            }

            // ✅ mensaje_error como text si no existe
            if (!Schema::hasColumn('electronic_invoices', 'mensaje_error')) {
                $table->text('mensaje_error')->nullable();
            }
        });

        // Índices y unique en un bloque separado para evitar errores en algunos motores
        Schema::table('electronic_invoices', function (Blueprint $table) {
            // UNIQUE sale_id (1 factura electrónica por venta)
            // Si ya hay duplicados en BD, esto fallará: hay que limpiar antes.
            $table->unique('sale_id', 'ux_ei_sale_id');

            // UNIQUE clave_acceso
            $table->unique('clave_acceso', 'ux_ei_clave_acceso');

            // INDEX estado_sri
            $table->index('estado_sri', 'ix_ei_estado_sri');
        });
    }

    public function down(): void
    {
        Schema::table('electronic_invoices', function (Blueprint $table) {
            $table->dropUnique('ux_ei_sale_id');
            $table->dropUnique('ux_ei_clave_acceso');
            $table->dropIndex('ix_ei_estado_sri');
        });

        Schema::table('electronic_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('electronic_invoices', 'estado_sri')) {
                $table->dropColumn('estado_sri');
            }
        });

        Schema::table('sri_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('sri_configs', 'clave_certificado')) {
                $table->text('clave_certificado')->nullable()->after('ruta_certificado');
            }
        });
    }
};
