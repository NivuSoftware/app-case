<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sri_configs', function (Blueprint $table) {
            $table->id();

            $table->string('ruc', 13);
            $table->string('razon_social', 200);
            $table->string('nombre_comercial', 200)->nullable();

            $table->string('direccion_matriz', 255);
            $table->string('direccion_establecimiento', 255)->nullable();

            $table->string('codigo_establecimiento', 3)->default('001');
            $table->string('codigo_punto_emision', 3)->default('001');

            $table->unsignedBigInteger('secuencial_factura_actual')->default(1);

            $table->enum('ambiente', ['PRUEBAS', 'PRODUCCION'])->default('PRUEBAS');
            $table->enum('emision', ['NORMAL', 'CONTINGENCIA'])->default('NORMAL');

            $table->string('ruta_certificado', 255)->nullable();
            $table->string('clave_certificado', 255)->nullable();

            $table->boolean('obligado_contabilidad')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sri_configs');
    }
};
