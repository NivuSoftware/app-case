<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    use HasFactory;

    protected $table = 'purchase_payments';

    protected $fillable = [
        'purchase_id',
        'fecha_pago',
        'monto',
        'metodo',
        'referencia',
        'observaciones',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto'      => 'decimal:2',
    ];

    /* ==========================
        RELACIONES
    ========================== */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function usuario()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_id');
    }
}
