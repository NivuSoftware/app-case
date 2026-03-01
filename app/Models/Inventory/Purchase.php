<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $table = 'purchases';

    protected $fillable = [
        'supplier_id',
        'user_id',
        'bodega_id',
        'fecha_compra',
        'numero_documento',
        'tipo_documento',
        'num_factura',
        'subtotal',
        'descuento',
        'impuesto',
        'iva',
        'total',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'subtotal'     => 'decimal:2',
        'descuento'    => 'decimal:2',
        'impuesto'     => 'decimal:2',
        'iva'          => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    /* ==========================
        RELACIONES
    ========================== */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class, 'purchase_id');
    }

    public function payments()
    {
        return $this->hasMany(PurchasePayment::class, 'purchase_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function bodega()
    {
        return $this->belongsTo(\App\Models\Store\Bodega::class, 'bodega_id');
    }

    /* ==========================
        HELPERS
    ========================== */

    // Suma de lo pagado
    public function getTotalPagadoAttribute()
    {
        return $this->payments->sum('monto');
    }

    // Saldo pendiente = total - pagado
    public function getSaldoPendienteAttribute()
    {
        return $this->total - $this->total_pagado;
    }
}
