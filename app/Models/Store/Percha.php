<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;

class Percha extends Model
{
    protected $table = 'perchas';

    protected $fillable = [
        'bodega_id',
        'codigo',
        'descripcion',
    ];

    // Relaciones
    public function bodega()
    {
        return $this->belongsTo(Bodega::class, 'bodega_id');
    }

    public function inventarios()
    {
        return $this->hasMany(Inventory::class, 'percha_id');
    }
}
