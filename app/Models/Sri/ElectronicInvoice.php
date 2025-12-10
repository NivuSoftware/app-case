<?php

namespace App\Models\Sri;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Sales\Sale;

class ElectronicInvoice extends Model
{
    use HasFactory;

    protected $table = 'electronic_invoices';

    protected $fillable = [
        'sale_id',
        'clave_acceso',
        'xml_generado_path',
        'xml_firmado_path',
        'xml_autorizado_path',
        'estado_sri',
        'numero_autorizacion',
        'fecha_autorizacion',
        'mensaje_error',
    ];

    protected $casts = [
        'fecha_autorizacion' => 'datetime',
    ];

    /* ==========================
       RELACIONES
    ========================== */

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
