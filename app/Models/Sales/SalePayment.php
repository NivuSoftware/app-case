<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SalePayment extends Model
{
    use HasFactory;

    protected $table = 'sale_payments';

    protected $fillable = [
        'sale_id',
        'fecha_pago',
        'monto',
        'metodo',
        'payment_method_id',
        'referencia',
        'observaciones',
        'monto_recibido',
        'cambio',
        'usuario_id',
    ];

    protected $casts = [
        'fecha_pago'     => 'datetime',
        'monto'          => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'cambio'         => 'decimal:2',
    ];

    /* ==========================
       RELACIONES
    ========================== */

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
