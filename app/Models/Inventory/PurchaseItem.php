<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product\Product;
use App\Models\Store\Bodega;
use App\Models\Store\Percha;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $table = 'purchase_items';

    protected $fillable = [
        'purchase_id',
        'producto_id',
        'bodega_id',
        'percha_id',
        'cantidad',
        'costo_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad'       => 'integer',
        'costo_unitario' => 'decimal:4',
        'subtotal'       => 'decimal:2',
    ];

    /* ==========================
        RELACIONES
    ========================== */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function producto()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function percha()
    {
        return $this->belongsTo(Percha::class, 'percha_id');
    }
}
