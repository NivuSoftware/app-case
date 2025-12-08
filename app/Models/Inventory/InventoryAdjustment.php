<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\Product;
use App\Models\Bodega;
use App\Models\Percha;
use App\Models\Inventory\InventoryMovement;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $table = 'ajustes_inventario';

    protected $fillable = [
        'usuario_id',
        'bodega_id',
        'percha_id',
        'producto_id',
        'stock_inicial',
        'stock_final',
        'diferencia',
        'tipo',
        'motivo',
    ];

    /* ===========================
        RELACIONES
    ============================ */

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
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

    public function movimientos()
    {
        return $this->hasMany(InventoryMovement::class, 'ajuste_id');
    }
}
