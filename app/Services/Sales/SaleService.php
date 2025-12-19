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

            if (! $payment) {
                throw ValidationException::withMessages([
                    'payment' => 'Debe registrar al menos un pago.',
                ]);
            }

            
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

                $item['total']   = $lineTotal;
                $subtotal        += $lineSubtotal;
                $descuentoTotal  += $descuento;
            }
            unset($item);

            $impuesto = 0; 
            $iva      = 0; 

            $total = $subtotal - $descuentoTotal + $impuesto + $iva;

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

          
            $sale = $this->sales->createSale($saleData);

           
            $vendioSinStock = false;  

            foreach ($items as $item) {
                $this->sales->addItem($sale, [
                    'producto_id'     => $item['producto_id'],
                    'descripcion'     => $item['descripcion'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'descuento'       => $item['descuento'] ?? 0,
                    'total'           => $item['total'],
                ]);

                $teniaStock = $this->inventory->decreaseStockForSale(
                    $item['producto_id'],
                    $data['bodega_id'],
                    $item['percha_id'] ?? null,
                    $item['cantidad'],
                    $data['user_id'],         
                    $sale->id,              
                    $sale->num_factura        
                );


                if (! $teniaStock) {
                    $vendioSinStock = true;
                }
            }

    
            $montoRecibido = (float) ($payment['monto_recibido'] ?? $total);
            $cambio        = $montoRecibido - $total;

            if ($montoRecibido < $total) {
                throw ValidationException::withMessages([
                    'payment.monto_recibido' => 'El monto recibido no puede ser menor al total de la venta.',
                ]);
            }

            $this->sales->addPayment($sale, [
                'fecha_pago'        => $payment['fecha_pago'] ?? now(),
                'monto'             => $total,
                'metodo'            => $payment['metodo'],
                'payment_method_id' => $payment['payment_method_id'] ?? null,
                'referencia'        => $payment['referencia'] ?? null,
                'observaciones'     => $payment['observaciones'] ?? null,
                'monto_recibido'    => $montoRecibido,
                'cambio'            => $cambio,
                'usuario_id'        => $data['user_id'],
            ]);

            
            $this->sales->updateEstado($sale, 'pagada');

            $sale = $this->sales->findById($sale->id);
            $sale->setAttribute('vendio_sin_stock', $vendioSinStock);

            return $sale;
        });
    }

    public function getById(int $id): ?Sale
    {
        return $this->sales->findById($id);
    }

}
