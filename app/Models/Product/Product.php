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
        'estado',
    ];

    public function price()
    {
        return $this->hasOne(\App\Models\Product\ProductPrice::class, 'producto_id');
    }

}
