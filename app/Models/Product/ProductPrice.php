<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    protected $table = 'product_prices';

    protected $fillable = [
        'producto_id',
        'precio_unitario',
        'precio_por_cantidad',
        'cantidad_min',
        'cantidad_max',
        'precio_por_caja',
        'unidades_por_caja',
        'moneda',
    ];

    // RELACIONES
    public function product()
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }
}
