<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;

class Bodega extends Model
{
    protected $table = 'bodegas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ubicacion',
        'tipo',
    ];

    // Relaciones
    public function perchas()
    {
        return $this->hasMany(Percha::class, 'bodega_id');
    }

    public function inventarios()
    {
        return $this->hasMany(Inventory::class, 'bodega_id');
    }

    public function lotes()
    {
        return $this->hasMany(Lote::class, 'bodega_id');
    }
}
