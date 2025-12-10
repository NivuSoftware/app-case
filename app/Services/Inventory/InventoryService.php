<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Inventory;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Inventory\InventoryAdjustmentRepository;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Exception;

class InventoryService
{
    protected InventoryRepository $repository;
    protected InventoryAdjustmentRepository $adjustmentRepository;

    public function __construct(
        InventoryRepository $repository,
        InventoryAdjustmentRepository $adjustmentRepository
    ) {
        $this->repository           = $repository;
        $this->adjustmentRepository = $adjustmentRepository;
    }

    /* =========================================================
        MÉTODOS DE CONSULTA
       ========================================================= */

    public function getAll()
    {
        return $this->repository->all();
    }

    public function getById($id)
    {
        return $this->repository->find($id);
    }

    public function getByProduct($productoId)
    {
        return $this->repository->getByProduct($productoId);
    }

    public function getByBodega($bodegaId)
    {
        return $this->repository->getByBodega($bodegaId);
    }

    /* =========================================================
        CRUD BÁSICO
       ========================================================= */

    public function create($data)
    {
        return $this->repository->create($data);
    }

    public function update($id, $data)
    {
        $inv = $this->repository->find($id);
        return $this->repository->update($inv, $data);
    }

    public function delete($id)
    {
        $inv = $this->repository->find($id);
        $this->repository->delete($inv);
        return true;
    }

    /* =========================================================
        HELPERS INTERNOS
       ========================================================= */

    /**
     * Obtiene el registro de inventario para la ubicación,
     * y si no existe LO CREA con stock 0.
     */
    protected function getOrCreateLocation($productoId, $bodegaId, $perchaId): Inventory
    {
        /** @var Inventory|null $inv */
        $inv = $this->repository->getByLocation($productoId, $bodegaId, $perchaId);

        if (! $inv) {
            $inv = $this->repository->create([
                'producto_id'     => $productoId,
                'bodega_id'       => $bodegaId,
                'percha_id'       => $perchaId,
                'stock_actual'    => 0,
                'stock_reservado' => 0,
            ]);
        }

        return $inv;
    }

    /* =========================================================
        OPERACIONES DE STOCK
       ========================================================= */

    /**
     * Aumenta stock en una ubicación.
     * - Si no existe inventario para esa ubicación, lo crea.
     * - Registra el movimiento en ajustes_inventario con un motivo.
     *
     * $motivo:
     *   - null  => "Aumento de stock"
     *   - "Compra #ID" => cuando viene desde una compra
     */
    public function increaseStock(
        $productoId,
        $bodegaId,
        $perchaId,
        $cantidad,
        ?string $motivo = null
    ) {
        $cantidad = (int) $cantidad;
        if ($cantidad <= 0) {
            throw new InvalidArgumentException("La cantidad a aumentar debe ser mayor a 0");
        }

        // 👉 Aquí ya se crea el inventario si no existía
        $inv = $this->getOrCreateLocation($productoId, $bodegaId, $perchaId);

        $stockInicial = (int) $inv->stock_actual;

        // Actualizar stock
        $inv = $this->repository->increaseStock($inv, $cantidad);

        // Registrar el movimiento en ajustes_inventario (kardex simple)
        $ajuste = $this->adjustmentRepository->create([
            'usuario_id'    => Auth::id(),
            'bodega_id'     => $inv->bodega_id,
            'percha_id'     => $inv->percha_id,
            'producto_id'   => $inv->producto_id,
            'stock_inicial' => $stockInicial,
            'stock_final'   => $inv->stock_actual,
            'diferencia'    => $cantidad,
            'tipo'          => 'positivo',
            'motivo'        => $motivo ?: 'Aumento de stock',
        ]);

        return [
            'message'    => "Se aumentó el stock en {$cantidad} unidades.",
            'inventory'  => $inv,
            'diferencia' => $cantidad,
            'tipo'       => 'positivo',
            'ajuste'     => $ajuste,
        ];
    }

    public function decreaseStock($productoId, $bodegaId, $perchaId, $cantidad)
    {
        /** @var Inventory|null $inv */
        $inv = $this->repository->getByLocation($productoId, $bodegaId, $perchaId);

        if (!$inv) {
            throw new Exception("No se encontró inventario para esa ubicación");
        }

        $cantidad = (int) $cantidad;

        if ($inv->stock_actual < $cantidad) {
            throw new Exception("Stock insuficiente");
        }

        $stockInicial = (int) $inv->stock_actual;

        $inv = $this->repository->decreaseStock($inv, $cantidad);

        $diferencia = -$cantidad;

        $ajuste = $this->adjustmentRepository->create([
            'usuario_id'    => Auth::id(),
            'bodega_id'     => $inv->bodega_id,
            'percha_id'     => $inv->percha_id,
            'producto_id'   => $inv->producto_id,
            'stock_inicial' => $stockInicial,
            'stock_final'   => $inv->stock_actual,
            'diferencia'    => $diferencia,
            'tipo'          => 'negativo',
            'motivo'        => 'Disminución manual de stock',
        ]);

        return [
            'message'    => "Se disminuyó el stock en {$cantidad} unidades.",
            'inventory'  => $inv,
            'diferencia' => $diferencia,
            'tipo'       => 'negativo',
            'ajuste'     => $ajuste,
        ];
    }

    /**
     * Ajusta el stock a un valor absoluto
     * y registra el ajuste en la tabla ajustes_inventario.
     */
    public function adjustStock($productoId, $bodegaId, $perchaId, int $nuevoStock, ?string $motivo = null)
    {
        /** @var Inventory|null $inv */
        $inv = $this->repository->getByLocation($productoId, $bodegaId, $perchaId);

        if (!$inv) {
            throw new Exception("No se encontró inventario para esa ubicación");
        }

        if ($nuevoStock < 0) {
            throw new InvalidArgumentException("El stock no puede ser negativo");
        }

        $stockInicial = (int) $inv->stock_actual;

        if ($nuevoStock === $stockInicial) {
            return [
                'message'    => 'El stock ya tiene ese valor. No se realizaron cambios.',
                'inventory'  => $inv,
                'diferencia' => 0,
                'tipo'       => 'sin_cambios',
            ];
        }

        $diferencia = $nuevoStock - $stockInicial;
        $tipo = $diferencia > 0 ? 'positivo' : 'negativo';

        $inv = $this->repository->adjustStock($inv, $nuevoStock);

        $ajuste = $this->adjustmentRepository->create([
            'usuario_id'    => Auth::id(),
            'bodega_id'     => $inv->bodega_id,
            'percha_id'     => $inv->percha_id,
            'producto_id'   => $inv->producto_id,
            'stock_inicial' => $stockInicial,
            'stock_final'   => $nuevoStock,
            'diferencia'    => $diferencia,
            'tipo'          => $tipo,
            'motivo'        => $motivo,
        ]);

        return [
            'message'    => $tipo === 'positivo'
                ? "Se aumentó el stock en {$diferencia} unidades."
                : "Se disminuyó el stock en " . abs($diferencia) . " unidades.",
            'inventory'  => $inv,
            'diferencia' => $diferencia,
            'tipo'       => $tipo,
            'ajuste'     => $ajuste,
        ];
    }

    /**
     * Historial de ajustes de stock por producto/bodega/percha.
     */
    public function getAdjustmentsHistory($productoId, $bodegaId, $perchaId = null)
    {
        return $this->adjustmentRepository->getByLocation($productoId, $bodegaId, $perchaId);
    }
}
