<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'nombre',
        'descripcion',
        'codigo_barras',
        'codigo_interno',
        'categoria',
        'foto_url',
        'unidad_medida',
        'stock_minimo',
        'iva_porcentaje',
        'estado',
    ];

    public function price()
    {
        // OJO: si tienes múltiples filas en product_prices, este hasOne puede traer "cualquiera".
        // Si tu "price" es el precio base, abajo te dejo una mejora opcional.
        return $this->hasOne(\App\Models\Product\ProductPrice::class, 'producto_id');
    }

    // ✅ RELACIÓN CORRECTA
    public function productPrices()
    {
        return $this->hasMany(\App\Models\Product\ProductPrice::class, 'producto_id');
    }

    // ✅ (Opcional) Alias por si en algún lado viejo llamas product_prices()
    public function product_prices()
    {
        return $this->productPrices();
    }

    protected $casts = [
        'iva_porcentaje' => 'float',
    ];
}
