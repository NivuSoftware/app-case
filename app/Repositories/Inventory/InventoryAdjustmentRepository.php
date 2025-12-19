<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\InventoryAdjustment;

class InventoryAdjustmentRepository
{
    /**
     * Crear un ajuste de inventario.
     */
    public function create(array $data): InventoryAdjustment
    {
        return InventoryAdjustment::create($data);
    }

    /**
     * Obtener historial de ajustes para una ubicación específica
     * (producto + bodega + percha opcional).
     */
    public function getByLocation($productoId, $bodegaId, $perchaId = null)
    {
        return InventoryAdjustment::with(['usuario'])
            ->where('producto_id', $productoId)
            ->where('bodega_id', $bodegaId)
            ->when(!is_null($perchaId), function ($q) use ($perchaId) {
                $q->where('percha_id', $perchaId);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

}
