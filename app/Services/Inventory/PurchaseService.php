<?php

namespace App\Services\Inventory;

use App\Models\Inventory\Purchase;
use App\Repositories\Inventory\PurchaseRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Exception;

class PurchaseService
{
    protected PurchaseRepository $purchaseRepository;
    protected InventoryService $inventoryService;

    public function __construct(
        PurchaseRepository $purchaseRepository,
        InventoryService $inventoryService
    ) {
        $this->purchaseRepository = $purchaseRepository;
        $this->inventoryService   = $inventoryService;
    }

    public function listAll()
    {
        return $this->purchaseRepository->all();
    }

    public function getAll()
    {
        return $this->purchaseRepository->all();
    }

    public function getById(int $id): Purchase
    {
        return $this->purchaseRepository->find($id);
    }

    /**
     * Registra una compra con sus ítems y, opcionalmente,
     * un pago inicial (pago_inicial, metodo_pago, referencia, observaciones).
     */
     public function registerPurchase(array $data): Purchase
    {
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new InvalidArgumentException('Debe ingresar al menos un ítem en la compra.');
        }

        $ivaPorcentaje = isset($data['iva_porcentaje'])
            ? (float) $data['iva_porcentaje']
            : 0.15; // 15% por defecto

        return DB::transaction(function () use ($data, $ivaPorcentaje) {

            $subtotalGlobal = 0.0;
            $ivaGlobal = 0.0;

            // ---- Procesar ítems y calcular subtotal global ----
            $items = array_map(function ($item) use (&$subtotalGlobal, &$ivaGlobal, $ivaPorcentaje) {
                if (
                    empty($item['producto_id']) ||
                    empty($item['bodega_id'])   ||
                    empty($item['cantidad'])    ||
                    empty($item['costo_unitario'])
                ) {
                    throw new InvalidArgumentException(
                        'Cada ítem debe tener producto, bodega, cantidad y costo unitario.'
                    );
                }

                $cantidad      = (int) $item['cantidad'];
                $costoUnitario = (float) $item['costo_unitario'];
                $subtotal      = $cantidad * $costoUnitario;
                $gravaIva = array_key_exists('grava_iva', $item) ? (bool) $item['grava_iva'] : true;

                $subtotalGlobal += $subtotal;
                $ivaGlobal += $gravaIva ? round($subtotal * $ivaPorcentaje, 2) : 0.0;

                return [
                    'producto_id'    => $item['producto_id'],
                    'bodega_id'      => $item['bodega_id'],
                    'percha_id'      => $item['percha_id'] ?? null,
                    'cantidad'       => $cantidad,
                    'costo_unitario' => $costoUnitario,
                    'subtotal'       => $subtotal,
                    'grava_iva'      => $gravaIva,
                ];
            }, $data['items'] ?? []);

            // ==== TOTALES ====
            $iva   = round($ivaGlobal, 2);
            $total = round($subtotalGlobal + $iva, 2);

            // En tu escenario "se paga todo", este pago_inicial normalmente será = total
            $pagoInicial = isset($data['pago_inicial']) ? (float) $data['pago_inicial'] : 0.0;
            if ($pagoInicial < 0) {
                throw new InvalidArgumentException('El pago inicial no puede ser negativo.');
            }
            if ($pagoInicial > $total) {
                throw new InvalidArgumentException('El pago inicial no puede ser mayor al total de la compra.');
            }

            // ==== USER Y BODEGA CABECERA ====
            $userId = Auth::id();

            // Usamos la bodega del PRIMER ítem como bodega principal de la compra
            $bodegaId = $data['bodega_id'] ?? ($items[0]['bodega_id'] ?? null);
            if (!$bodegaId) {
                throw new InvalidArgumentException('No se pudo determinar la bodega de la compra.');
            }

            // ---- Crear compra (cabecera) ----
            $purchaseData = [
                'supplier_id'       => $data['supplier_id'],
                'user_id'           => $userId,
                'bodega_id'         => $bodegaId,
                'fecha_compra'      => $data['fecha_compra'] ?? now()->toDateString(),
                'numero_documento'  => $data['num_factura'] ?? null, // si quieres usarlo
                'num_factura'       => $data['num_factura'] ?? null, // tu campo nuevo
                'subtotal'          => $subtotalGlobal,
                'descuento'         => 0,            // por ahora sin manejo de descuentos
                'impuesto'          => 0,            // no lo usas; el IVA lo guardas en 'iva'
                'iva'               => $iva,
                'total'             => $total,
                'estado'            => 'pendiente',  // luego se marca pagada si corresponde
                'observaciones'     => $data['observaciones'] ?? null,
            ];

            /** @var Purchase $purchase */
            $purchase = $this->purchaseRepository->create($purchaseData);

            // ---- Items + actualización de stock ----
            foreach ($items as $item) {
                $this->purchaseRepository->addItem($purchase, $item);

                // Aquí ya CREAS inventario si no existe y registras ajuste con motivo
                $this->inventoryService->increaseStock(
                    $item['producto_id'],
                    $item['bodega_id'],
                    $item['percha_id'] ?? null,
                    $item['cantidad'],
                    'Ingreso por compra #' . $purchase->id
                );
            }

            // ---- Pago inicial (si lo envías; en tu caso normalmente = total) ----
            if ($pagoInicial > 0) {
                $this->purchaseRepository->addPayment($purchase, [
                    'fecha_pago'    => $data['fecha_pago'] ?? ($data['fecha_compra'] ?? now()->toDateString()),
                    'monto'         => $pagoInicial,
                    'metodo'        => $data['metodo_pago'] ?? 'efectivo',
                    'referencia'    => $data['referencia'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null,
                    'usuario_id'    => $userId,
                ]);

                // Recalcular saldo pendiente y estado
                $purchase->load('payments');
                $saldoPendiente = $purchase->saldo_pendiente;

                if ($saldoPendiente <= 0) {
                    $purchase->estado = 'pagada';
                    $purchase->save();
                }
            }

            $purchase->load(['supplier', 'items']);

            return $purchase;
        });
    }

    public function registerPayment(int $purchaseId, array $data): Purchase
    {
        $purchase = $this->purchaseRepository->find($purchaseId);

        if ($purchase->estado === 'anulada') {
            throw new Exception('No se puede registrar pago sobre una compra anulada.');
        }

        $monto = (float) ($data['monto'] ?? 0);

        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto del pago debe ser mayor a 0.');
        }

        $saldoPendiente = $purchase->saldo_pendiente;

        if ($monto > $saldoPendiente) {
            throw new InvalidArgumentException('El monto supera el saldo pendiente de la compra.');
        }

        return DB::transaction(function () use ($purchase, $data, $monto) {

            $this->purchaseRepository->addPayment($purchase, [
                'fecha_pago'    => $data['fecha_pago'] ?? now()->toDateString(),
                'monto'         => $monto,
                'metodo'        => $data['metodo'] ?? 'efectivo',
                'referencia'    => $data['referencia'] ?? null,
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_id'    => Auth::id(),
            ]);

            $purchase->load('payments');

            $saldoPendiente = $purchase->saldo_pendiente;

            if ($saldoPendiente <= 0) {
                $purchase->estado = 'pagada';
                $purchase->save();
            }

            return $purchase;
        });
    }

    public function cancelPurchase(int $purchaseId, bool $revertirStock = false): Purchase
    {
        $purchase = $this->purchaseRepository->find($purchaseId);

        if ($purchase->estado === 'anulada') {
            return $purchase;
        }

        return DB::transaction(function () use ($purchase, $revertirStock) {

            if ($revertirStock) {
                $purchase->load('items');

                foreach ($purchase->items as $item) {
                    $this->inventoryService->decreaseStock(
                        $item->producto_id,
                        $item->bodega_id,
                        $item->percha_id,
                        $item->cantidad
                    );
                }
            }

            $purchase->estado = 'anulada';
            $purchase->save();

            return $purchase;
        });
    }
}
