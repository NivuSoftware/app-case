<?php

namespace App\Services\Sales;

use App\Models\Product\Product;
use App\Repositories\Sales\SaleRepository;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Sales\Sale;
use App\Services\Cashier\CashierService;
use App\Models\Clients\ClientEmail;
use App\Services\Sri\SriInvoiceService;



class SaleService
{
    protected SaleRepository $sales;
    protected InventoryService $inventory;

    public function __construct(
        SaleRepository $sales,
        InventoryService $inventory,
        private CashierService $cashier,
        private SriInvoiceService $sriInvoiceService,
    ) {
        $this->sales     = $sales;
        $this->inventory = $inventory;
    }

  
    public function crearVenta(array $data): Sale
    {
        return DB::transaction(function () use ($data) {

            $cajaId = (int)($data['caja_id'] ?? 0);

            if ($cajaId <= 0) {
                throw ValidationException::withMessages([
                    'caja_id' => 'Debes indicar el número de caja (caja_id).',
                ]);
            }

            $this->cashier->getOpenSessionOrFail($cajaId);

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

        
            $ivaEnabled = (bool)($data['iva_enabled'] ?? true);

        
            $toCents = function ($n): int {
                $n = $n ?? 0;
                return (int) round(((float) $n) * 100, 0, PHP_ROUND_HALF_UP);
            };

            $fromCents = function (int $cents): float {
                return round($cents / 100, 2);
            };

            $toBp = function ($pct): int {
                $p = (float)($pct ?? 0);
                if ($p < 0) $p = 0;
                if ($p > 100) $p = 100;
                return (int) round($p * 100, 0, PHP_ROUND_HALF_UP);
            };

        
            $subtotalCents  = 0;
            $descuentoCents = 0;
            $ivaCents       = 0;
            $impuestoCents  = 0;

            foreach ($items as $idx => &$item) {

                $productoId = (int)($item['producto_id'] ?? 0);
                $cantidad   = (int)($item['cantidad'] ?? 0);

                if ($productoId <= 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.producto_id" => 'Producto inválido.',
                    ]);
                }

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.cantidad" => 'Cantidad debe ser válida.',
                    ]);
                }

                $product = Product::with(['price', 'product_prices'])->find($productoId);
                if (!$product) {
                    throw ValidationException::withMessages([
                        "items.$idx.producto_id" => 'El producto no existe.',
                    ]);
                }

                $pricing = $this->resolveLinePricingForQuantity($product, $cantidad, $toCents, $fromCents);
                $precioUnitario = (float)($pricing['effective_unit_price'] ?? 0);

                if (!is_finite($precioUnitario) || $precioUnitario < 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.precio_unitario" => 'Precio unitario inválido.',
                    ]);
                }

                $descCts = $toCents($item['descuento'] ?? 0);
                if ($descCts < 0) $descCts = 0;

                $lineSubtotalCts = (int)($pricing['line_subtotal_cents'] ?? 0);

                if ($descCts > $lineSubtotalCts) {
                    throw ValidationException::withMessages([
                        "items.$idx.descuento" => 'El descuento no puede superar el valor de la línea.',
                    ]);
                }

                $lineBaseCts = $lineSubtotalCts - $descCts;

                $ivaPctProducto = $product->iva_porcentaje;
                if ($ivaPctProducto === null || $ivaPctProducto === '') {
                    $ivaPctProducto = 15;
                }

                $ivaPctFinal = $ivaEnabled ? (float) $ivaPctProducto : 0.0;

                $bp = $toBp($ivaPctFinal);

                $lineIvaCts = (int) floor(($lineBaseCts * $bp + 5000) / 10000);

                $item['precio_unitario'] = $precioUnitario;
                $item['iva_porcentaje']  = $ivaPctFinal;

                $item['pricing_rule']     = $pricing['rule'] ?? null;
                $item['pricing_price_id'] = $pricing['price_id'] ?? null;

                $item['total'] = $fromCents($lineBaseCts);


                $subtotalCents  += $lineSubtotalCts;
                $descuentoCents += $descCts;
                $ivaCents       += $lineIvaCts;
            }
            unset($item);

            $baseImponibleCents = $subtotalCents - $descuentoCents;
            $totalCents = $baseImponibleCents + $impuestoCents + $ivaCents;

            $subtotal       = $fromCents($subtotalCents);
            $descuentoTotal = $fromCents($descuentoCents);
            $impuesto       = $fromCents($impuestoCents);
            $iva            = $fromCents($ivaCents);
            $total          = $fromCents($totalCents);

            $clientId      = $data['client_id'] ?? null;
            $clientEmailId = $data['client_email_id'] ?? null;
            $emailDestino  = $data['email_destino'] ?? null;

            if ($clientId && $clientEmailId) {
                $ok = ClientEmail::where('id', $clientEmailId)
                    ->where('client_id', $clientId)
                    ->exists();

                if (!$ok) {
                    throw ValidationException::withMessages([
                        'client_email_id' => 'El correo seleccionado no pertenece al cliente.',
                    ]);
                }

                if (!$emailDestino) {
                    $emailDestino = ClientEmail::where('id', $clientEmailId)->value('email');
                }
            }        

        
            $saleData = [
                'client_id'       => $clientId,
                'user_id'         => $data['user_id'],
                'client_email_id' => $clientEmailId,
                'email_destino'   => $emailDestino,
                'bodega_id'       => $data['bodega_id'],
                'fecha_venta'     => $data['fecha_venta'],
                'tipo_documento'  => $data['tipo_documento'] ?? 'FACTURA',
                'num_factura'     => $data['num_factura'] ?? null,
                'subtotal'        => $subtotal,
                'descuento'       => $descuentoTotal,
                'impuesto'        => $impuesto,
                'iva'             => $iva,
                'total'           => $total,
                'estado'          => 'pendiente',
                'observaciones'   => $data['observaciones'] ?? null,
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
                    'iva_porcentaje'  => $item['iva_porcentaje'] ?? 0,
                    'total'           => $item['total'], // sin IVA
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

                if (!$teniaStock) {
                    $vendioSinStock = true;
                }
            }

        
            $montoRecibido = (float)($payment['monto_recibido'] ?? $total);
            $cambio        = $montoRecibido - $total;

            if ($montoRecibido < $total) {
                throw ValidationException::withMessages([
                    'payment.monto_recibido' => 'El monto recibido no puede ser menor al total de la venta.',
                ]);
            }

            $metodoPago     = (string)($payment['metodo'] ?? '');
            $metodoPagoNorm = strtoupper(trim($metodoPago));

            $this->sales->addPayment($sale, [
                'fecha_pago'        => $payment['fecha_pago'] ?? now(),
                'monto'             => $total, // incluye IVA
                'metodo'            => $metodoPago,
                'payment_method_id' => $payment['payment_method_id'] ?? null,
                'referencia'        => $payment['referencia'] ?? null,
                'observaciones'     => $payment['observaciones'] ?? null,
                'monto_recibido'    => $montoRecibido,
                'cambio'            => $cambio,
                'usuario_id'        => $data['user_id'],
            ]);

        
            $this->sales->updateEstado($sale, 'pagada');


            $isCash = in_array($metodoPagoNorm, ['EFECTIVO', 'CASH'], true);

            if ($isCash) {
                $this->cashier->registerSaleIncome(
                    $cajaId,
                    (int) $data['user_id'],
                    (int) $sale->id,
                    $sale->num_factura,
                    (float) $total,
                    $metodoPagoNorm
                );
            }

            // ==========================
            //  SRI: XML + (FIRMADO) + ENVÍO/AUTORIZACIÓN
            // ==========================
            try {
                if (($sale->tipo_documento ?? 'FACTURA') === 'FACTURA') {

                    $inv = $this->sriInvoiceService->generateXmlForSale((int) $sale->id);

                    $this->sriInvoiceService->signXmlForSale((int) $sale->id);

                    $inv = $this->sriInvoiceService->sendAndAuthorizeForSale((int) $sale->id);



                    $sale->setAttribute('sri_estado', $inv->estado_sri ?? null);
                    $sale->setAttribute('sri_clave_acceso', $inv->clave_acceso ?? null);
                    $sale->setAttribute('sri_numero_autorizacion', $inv->numero_autorizacion ?? null);
                    $sale->setAttribute('sri_fecha_autorizacion', $inv->fecha_autorizacion ?? null);
                }
            } catch (\Throwable $e) {
                \Log::error('SRI flow failed', [
                    'sale_id' => $sale->id ?? null,
                    'error'   => $e->getMessage(),
                ]);

                $sale->setAttribute('sri_estado', 'ERROR_SRI');
                $sale->setAttribute('sri_error', 'No se pudo completar el flujo SRI (XML/Firma/Envío). Revisa logs/config.');
            }


            $sale = $this->sales->findById($sale->id);
            $sale->setAttribute('vendio_sin_stock', $vendioSinStock);

            return $sale;

        });
    }

    public function getById(int $id): ?Sale
    {
        return $this->sales->findById($id);
    }

    private function resolveLinePricingForQuantity(
        Product $product,
        int $qty,
        callable $toCents,
        callable $fromCents
    ): array {
        
        $product->loadMissing(['price', 'product_prices']);

        $prices = $product->product_prices ?? collect();

        $base = null;
        if ($product->relationLoaded('price') && $product->price) {
            $base = $product->price->precio_unitario ?? null;
        }
        if ($base === null || $base === '') {
            $base = $product->precio_unitario ?? 0;
        }
        $baseUnit = (float) $base;

        $pickTier = function () use ($prices, $qty) {
            $tier = $prices
                ->filter(function ($pp) use ($qty) {
                    $min = (int)($pp->cantidad_min ?? 0);
                    $max = $pp->cantidad_max !== null ? (int)$pp->cantidad_max : null;
                    $pQ  = $pp->precio_por_cantidad;

                    if ($min <= 0) return false;
                    if ($pQ === null || $pQ === '') return false;
                    if ($qty < $min) return false;
                    if ($max !== null && $qty > $max) return false;
                    return true;
                })
                ->sortByDesc(fn ($pp) => (int)($pp->cantidad_min ?? 0))
                ->first();

            if ($tier) return $tier;

            return $prices
                ->filter(function ($pp) use ($qty) {
                    $min = (int)($pp->cantidad_min ?? 0);
                    $pQ  = $pp->precio_por_cantidad;
                    if ($min <= 0) return false;
                    if ($pQ === null || $pQ === '') return false;
                    return $qty >= $min;
                })
                ->sortByDesc(fn ($pp) => (int)($pp->cantidad_min ?? 0))
                ->first();
        };

        $tier = $pickTier();
        $tierUnit = $tier ? (float) $tier->precio_por_cantidad : $baseUnit;

        // ===== caja aplicable (mayor unidades_por_caja) =====
        $box = $prices
            ->filter(function ($pp) use ($qty) {
                $upc  = (int)($pp->unidades_por_caja ?? 0);
                $pBox = $pp->precio_por_caja;
                if ($upc <= 0) return false;
                if ($pBox === null || $pBox === '') return false;
                return $qty >= $upc;
            })
            ->sortByDesc(fn ($pp) => (int)($pp->unidades_por_caja ?? 0))
            ->first();

        // ===== si aplica caja: boxes*precioCaja + remainder*precioTier =====
        if ($box) {
            $upc = (int) $box->unidades_por_caja;
            $boxPriceCts = $toCents((float) $box->precio_por_caja);
            $tierUnitCts = $toCents($tierUnit);

            $boxes = intdiv($qty, $upc);
            $remainder = $qty % $upc;

            $lineSubtotalCts = ($boxes * $boxPriceCts) + ($remainder * $tierUnitCts);

            // unitario referencial para guardar
            $effectiveUnitCts = $qty > 0 ? (int) round($lineSubtotalCts / $qty) : $tierUnitCts;

            return [
                'line_subtotal_cents'   => $lineSubtotalCts,
                'effective_unit_price'  => $fromCents($effectiveUnitCts),
                'rule'                  => 'caja',
                'price_id'              => $box->id ?? null,
            ];
        }

        // ===== si NO aplica caja: qty * tierUnit =====
        $tierUnitCts = $toCents($tierUnit);
        $lineSubtotalCts = $qty * $tierUnitCts;

        return [
            'line_subtotal_cents'  => $lineSubtotalCts,
            'effective_unit_price' => $tierUnit,
            'rule'                 => $tier ? 'cantidad' : 'base',
            'price_id'             => $tier->id ?? null,
        ];
    }

}
