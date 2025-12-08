<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\Inventory;

class InventoryRepository
{
    public function all()
    {
        return Inventory::with(['producto', 'bodega', 'percha'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function find($id)
    {
        return Inventory::with(['producto', 'bodega', 'percha'])
            ->findOrFail($id);
    }

    public function getByProduct($productoId)
    {
        return Inventory::with(['bodega', 'percha'])
            ->where('producto_id', $productoId)
            ->get();
    }

    public function getByBodega($bodegaId)
    {
        return Inventory::with(['producto', 'percha'])
            ->where('bodega_id', $bodegaId)
            ->orderBy('producto_id')
            ->get();
    }

    public function getByLocation($productoId, $bodegaId, $perchaId)
    {
        return Inventory::where([
            'producto_id' => $productoId,
            'bodega_id'   => $bodegaId,
            'percha_id'   => $perchaId,
        ])->first();
    }

    public function create(array $data)
    {
        return Inventory::create($data);
    }

    public function update(Inventory $inventory, array $data)
    {
        $inventory->update($data);
        return $inventory;
    }

    public function delete(Inventory $inventory)
    {
        return $inventory->delete();
    }

 

    public function increaseStock(Inventory $inventory, int $cantidad): Inventory
    {
        $inventory->stock_actual += $cantidad;
        $inventory->save();

        return $inventory;
    }

    public function decreaseStock(Inventory $inventory, int $cantidad): Inventory
    {
        $inventory->stock_actual -= $cantidad;
        $inventory->save();

        return $inventory;
    }

   
    public function adjustStock(Inventory $inventory, int $nuevoStock): Inventory
    {
        $inventory->stock_actual = $nuevoStock;
        $inventory->save();

        return $inventory;
    }
}
