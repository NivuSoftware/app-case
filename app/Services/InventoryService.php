<?php

namespace App\Services;

use App\Repositories\InventoryRepository;

class InventoryService
{
    protected $repo;

    public function __construct(InventoryRepository $repo)
    {
        $this->repo = $repo;
    }

    // Listar todo el inventario (con relaciones)
    public function getAll()
    {
        return $this->repo->all();
    }

    // Obtener un registro por ID
    public function getById($id)
    {
        return $this->repo->find($id);
    }

    // Obtener inventario por producto
    public function getByProduct($productoId)
    {
        return $this->repo->getByProduct($productoId);
    }

    // Obtener inventario por bodega
    public function getByBodega($bodegaId)
    {
        return $this->repo->getByBodega($bodegaId);
    }

    // Obtener inventario por producto+bodega+percha (combinación única)
    public function getByLocation($productoId, $bodegaId, $perchaId)
    {
        return $this->repo->getByLocation($productoId, $bodegaId, $perchaId);
    }

    // Crear inventario (solo si no existe)
    public function create(array $data)
    {
        return $this->repo->create($data);
    }

    // Actualizar inventario
    public function update($id, array $data)
    {
        $inventory = $this->repo->find($id);
        return $this->repo->update($inventory, $data);
    }

    // Eliminar inventario
    public function delete($id)
    {
        $inventory = $this->repo->find($id);
        return $this->repo->delete($inventory);
    }

    // Aumentar stock
    public function increaseStock($productoId, $bodegaId, $perchaId, int $cantidad)
    {
        $inventory = $this->repo->getByLocation($productoId, $bodegaId, $perchaId);

        if (!$inventory) {
            // Si no existe ese inventario, creamos la fila
            $inventory = $this->repo->create([
                'producto_id' => $productoId,
                'bodega_id'   => $bodegaId,
                'percha_id'   => $perchaId,
                'stock_actual' => 0,
                'stock_reservado' => 0
            ]);
        }

        return $this->repo->increaseStock($inventory, $cantidad);
    }

    // Reducir stock
    public function decreaseStock($productoId, $bodegaId, $perchaId, int $cantidad)
    {
        $inventory = $this->repo->getByLocation($productoId, $bodegaId, $perchaId);

        if (!$inventory) {
            throw new \Exception("No existe inventario para esta ubicación.");
        }

        // Aunque el sistema permita vender con stock 0, el inventario sí debe registrar valores negativos.
        return $this->repo->decreaseStock($inventory, $cantidad);
    }
}
