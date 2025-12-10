<?php

namespace App\Models\Sri;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SriConfig extends Model
{
    use HasFactory;

    protected $table = 'sri_configs';

    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion_matriz',
        'direccion_establecimiento',
        'codigo_establecimiento',
        'codigo_punto_emision',
        'secuencial_factura_actual',
        'ambiente',
        'emision',
        'ruta_certificado',
        'clave_certificado',
        'obligado_contabilidad',
    ];

    protected $casts = [
        'secuencial_factura_actual' => 'integer',
        'obligado_contabilidad'     => 'boolean',
    ];
}
