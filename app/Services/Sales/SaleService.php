<?php

namespace App\Services\Sales;

use App\Repositories\Sales\SaleRepository;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Sales\Sale;

class SaleService
{
    protected SaleRepository $sales;
    protected InventoryService $inventory;

    public function __construct(SaleRepository $sales, InventoryService $inventory)
    {
        $this->sales     = $sales;
        $this->inventory = $inventory;
    }

    /**
     * Crea una venta completa (cabecera, ítems, pago, stock)
     *
     * @param array $data Estructura:
     *  [
     *      'client_id', 'user_id', 'bodega_id', 'fecha_venta', 'tipo_documento', 'observaciones',
     *      'items' => [
     *          [
     *              'producto_id', 'descripcion', 'cantidad',
     *              'precio_unitario', 'descuento' (opcional), 'percha_id' (opcional)
     *          ], ...
     *      ],
     *      'payment' => [
     *          'metodo',
     *          'payment_method_id' (opcional),
     *          'monto_recibido',
     *          'referencia' (opcional),
     *          'observaciones' (opcional),
     *          'fecha_pago' (opcional)
     *      ]
     *  ]
     */
    public function crearVenta(array $data): Sale
    {
        return DB::transaction(function () use ($data) {

            $items   = $data['items']   ?? [];
            $payment = $data['payment'] ?? null;

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'La venta debe tener al menos un ítem.',
                ]);
            }

            if (!$payment) {
                throw ValidationException::withMessages([
                    'payment' => 'Debe registrar al menos un pago.',
                ]);
            }

            // =========================
            // 1) Calcular totales
            // =========================
            $subtotal       = 0;
            $descuentoTotal = 0;

            foreach ($items as $idx => &$item) {
                $cantidad  = (int) ($item['cantidad'] ?? 0);
                $precio    = (float) ($item['precio_unitario'] ?? 0);
                $descuento = (float) ($item['descuento'] ?? 0);

                if ($cantidad <= 0 || $precio < 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.cantidad" => 'Cantidad y precio deben ser válidos.',
                    ]);
                }

                $lineSubtotal = $cantidad * $precio;
                $lineTotal    = $lineSubtotal - $descuento;

                if ($lineTotal < 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.descuento" => 'El descuento no puede superar el valor de la línea.',
                    ]);
                }

                $item['total']      = $lineTotal;
                $subtotal          += $lineSubtotal;
                $descuentoTotal    += $descuento;
            }
            unset($item);

            $impuesto = 0; // ICE u otros si luego los usas
            $iva      = 0; // Por ahora 0, luego metemos lógica de IVA por tipo de producto

            $total = $subtotal - $descuentoTotal + $impuesto + $iva;

            // Montar datos para la cabecera
            $saleData = [
                'client_id'      => $data['client_id'] ?? null,
                'user_id'        => $data['user_id'],
                'bodega_id'      => $data['bodega_id'],
                'fecha_venta'    => $data['fecha_venta'],
                'tipo_documento' => $data['tipo_documento'] ?? 'FACTURA',
                'num_factura'    => $data['num_factura'] ?? null,
                'subtotal'       => $subtotal,
                'descuento'      => $descuentoTotal,
                'impuesto'       => $impuesto,
                'iva'            => $iva,
                'total'          => $total,
                'estado'         => 'pendiente',
                'observaciones'  => $data['observaciones'] ?? null,
            ];

            // =========================
            // 2) Crear venta (cabecera)
            // =========================
            $sale = $this->sales->createSale($saleData);

            // =========================
            // 3) Crear ítems + descontar stock
            // =========================
            foreach ($items as $item) {
                $this->sales->addItem($sale, [
                    'producto_id'    => $item['producto_id'],
                    'descripcion'    => $item['descripcion'],
                    'cantidad'       => $item['cantidad'],
                    'precio_unitario'=> $item['precio_unitario'],
                    'descuento'      => $item['descuento'] ?? 0,
                    'total'          => $item['total'],
                ]);

                // Descontar stock usando tu InventoryService
                $this->inventory->decreaseStock(
                    $item['producto_id'],
                    $data['bodega_id'],
                    $item['percha_id'] ?? null,
                    $item['cantidad']
                );
            }

            // =========================
            // 4) Registrar pago + cambio
            // =========================
            $montoRecibido = (float) ($payment['monto_recibido'] ?? $total);
            $cambio        = $montoRecibido - $total;

            if ($montoRecibido < $total) {
                // Si quieres permitir pagos parciales, aquí lo cambiamos.
                // Por ahora obligamos a que cubra el total.
                throw ValidationException::withMessages([
                    'payment.monto_recibido' => 'El monto recibido no puede ser menor al total de la venta.',
                ]);
            }

            $this->sales->addPayment($sale, [
                'fecha_pago'       => $payment['fecha_pago'] ?? now(),
                'monto'            => $total,
                'metodo'           => $payment['metodo'],
                'payment_method_id'=> $payment['payment_method_id'] ?? null,
                'referencia'       => $payment['referencia'] ?? null,
                'observaciones'    => $payment['observaciones'] ?? null,
                'monto_recibido'   => $montoRecibido,
                'cambio'           => $cambio,
                'usuario_id'       => $data['user_id'],
            ]);

            // =========================
            // 5) Actualizar estado a pagada
            // =========================
            $this->sales->updateEstado($sale, 'pagada');

            // Recargar con relaciones para que el controller lo devuelva listo para imprimir
            return $this->sales->findById($sale->id);
        });
    }
}
