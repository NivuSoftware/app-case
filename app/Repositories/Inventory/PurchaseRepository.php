<?php

namespace App\Repositories\Inventory;

use App\Models\Inventory\Purchase;

class PurchaseRepository
{
    /**
     * Listar todas las compras con proveedor y totales calculados
     * (total_pagado, saldo).
     */
    public function all()
    {
        $purchases = Purchase::with(['supplier'])
            ->withSum('payments as total_pagado', 'monto')
            ->orderBy('fecha_compra', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return $purchases->map(function (Purchase $purchase) {
            $totalPagado = (float) ($purchase->total_pagado ?? 0);
            $saldo       = max(round($purchase->total - $totalPagado, 2), 0);

            $purchase->saldo = $saldo; // atributo dinámico para el JSON

            return $purchase;
        });
    }

    /**
     * Buscar una compra por ID con todas sus relaciones.
     */
    public function find(int $id): Purchase
    {
        $purchase = Purchase::with([
                'supplier',
                'items.producto',
                'items.bodega',
                'items.percha',
                'payments.usuario',
            ])
            ->withSum('payments as total_pagado', 'monto')
            ->findOrFail($id);

        $totalPagado      = (float) ($purchase->total_pagado ?? 0);
        $purchase->saldo  = max(round($purchase->total - $totalPagado, 2), 0);

        return $purchase;
    }

    public function create(array $data): Purchase
    {
        return Purchase::create($data);
    }

    public function addItem(Purchase $purchase, array $itemData)
    {
        return $purchase->items()->create($itemData);
    }

    public function addPayment(Purchase $purchase, array $paymentData)
    {
        return $purchase->payments()->create($paymentData);
    }
}
