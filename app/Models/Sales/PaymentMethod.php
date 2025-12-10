<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'payment_methods';

    protected $fillable = [
        'nombre',
        'codigo_sri',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /* ==========================
       RELACIONES
    ========================== */

    public function salePayments()
    {
        return $this->hasMany(SalePayment::class, 'payment_method_id');
    }
}
