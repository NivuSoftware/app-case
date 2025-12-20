<?php

namespace App\Services\Sales;

use App\Models\Product\Product;
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
     * ✅ Recalcula precios por cantidad/caja en backend usando product_prices
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

            // ==========================
            // IVA ON/OFF (GLOBAL)
            // ==========================
            $ivaEnabled = (bool)($data['iva_enabled'] ?? true);

            // ==========================
            // HELPERS EN CENTAVOS
            // ==========================
            $toCents = function ($n): int {
                $n = $n ?? 0;
                return (int) round(((float) $n) * 100, 0, PHP_ROUND_HALF_UP);
            };

            $fromCents = function (int $cents): float {
                return round($cents / 100, 2);
            };

            // Clamp % a 0..100 y pasar a basis points (2 decimales) => 15.00% => 1500
            $toBp = function ($pct): int {
                $p = (float)($pct ?? 0);
                if ($p < 0) $p = 0;
                if ($p > 100) $p = 100;
                return (int) round($p * 100, 0, PHP_ROUND_HALF_UP);
            };

            // ==========================
            // CALCULOS (CENTAVOS)
            // ==========================
            $subtotalCents  = 0; // suma antes de descuentos
            $descuentoCents = 0; // descuento total en $
            $ivaCents       = 0; // iva total
            $impuestoCents  = 0; // por ahora 0

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

                // ✅ Cargar producto + precios por reglas
                $product = Product::with(['price', 'productPrices'])->find($productoId);
                if (!$product) {
                    throw ValidationException::withMessages([
                        "items.$idx.producto_id" => 'El producto no existe.',
                    ]);
                }

                // ✅ Precio unitario REAL (por caja / cantidad / base)
                $pricing = $this->resolveUnitPriceForQuantity($product, $cantidad);
                $precioUnitario = (float) $pricing['unit_price'];

                if (!is_finite($precioUnitario) || $precioUnitario < 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.precio_unitario" => 'Precio unitario inválido.',
                    ]);
                }

                $precioCts = $toCents($precioUnitario);

                // Descuento viene como MONTO ($), no %
                $descCts = $toCents($item['descuento'] ?? 0);
                if ($descCts < 0) $descCts = 0;

                $lineSubtotalCts = $cantidad * $precioCts;

                if ($descCts > $lineSubtotalCts) {
                    throw ValidationException::withMessages([
                        "items.$idx.descuento" => 'El descuento no puede superar el valor de la línea.',
                    ]);
                }

                // base imponible de la línea (SIN IVA)
                $lineBaseCts = $lineSubtotalCts - $descCts;

                // ✅ IVA % desde producto (fallback 15)
                $ivaPctProducto = $product->iva_porcentaje;
                if ($ivaPctProducto === null || $ivaPctProducto === '') {
                    $ivaPctProducto = 15;
                }

                $bp = $ivaEnabled ? $toBp($ivaPctProducto) : 0;

                // IVA exacto en centavos con redondeo HALF_UP:
                // (baseCents * bp) / 10000
                $lineIvaCts = (int) floor(($lineBaseCts * $bp + 5000) / 10000);

                // ✅ Guardamos valores recalculados (backend manda)
                $item['precio_unitario']  = $precioUnitario;            // <-- importante (NO confiar en frontend)
                $item['iva_porcentaje']   = (float) $ivaPctProducto;    // <-- para ticket/auditoría si quieres
                $item['pricing_rule']     = $pricing['rule'] ?? null;   // <-- solo informativo (no se guarda a DB)
                $item['pricing_price_id'] = $pricing['price_id'] ?? null;

                // Total SIN IVA (como tu diseño actual)
                $item['total'] = $fromCents($lineBaseCts);

                $subtotalCents  += $lineSubtotalCts;
                $descuentoCents += $descCts;
                $ivaCents       += $lineIvaCts;
            }
            unset($item);

            $baseImponibleCents = $subtotalCents - $descuentoCents;

            // Total FINAL (incluye IVA)
            $totalCents = $baseImponibleCents + $impuestoCents + $ivaCents;

            // Pasamos a decimales 2
            $subtotal       = $fromCents($subtotalCents);
            $descuentoTotal = $fromCents($descuentoCents);
            $impuesto       = $fromCents($impuestoCents);
            $iva            = $fromCents($ivaCents);
            $total          = $fromCents($totalCents);

            // ==========================
            // CREAR CABECERA DE VENTA
            // ==========================
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

            // ==========================
            // ITEMS + STOCK
            // ==========================
            $vendioSinStock = false;

            foreach ($items as $item) {
                $this->sales->addItem($sale, [
                    'producto_id'     => $item['producto_id'],
                    'descripcion'     => $item['descripcion'],
                    'cantidad'        => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'], // ✅ recalculado
                    'descuento'       => $item['descuento'] ?? 0,
                    'total'           => $item['total'],           // ✅ total SIN IVA
                    // si tienes columna en sale_items, puedes guardar esto:
                    // 'iva_porcentaje'  => $item['iva_porcentaje'] ?? 15,
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

            // ==========================
            // PAGO
            // ==========================
            $montoRecibido = (float)($payment['monto_recibido'] ?? $total);
            $cambio        = $montoRecibido - $total;

            if ($montoRecibido < $total) {
                throw ValidationException::withMessages([
                    'payment.monto_recibido' => 'El monto recibido no puede ser menor al total de la venta.',
                ]);
            }

            $this->sales->addPayment($sale, [
                'fecha_pago'        => $payment['fecha_pago'] ?? now(),
                'monto'             => $total, // ✅ incluye IVA
                'metodo'            => $payment['metodo'],
                'payment_method_id' => $payment['payment_method_id'] ?? null,
                'referencia'        => $payment['referencia'] ?? null,
                'observaciones'     => $payment['observaciones'] ?? null,
                'monto_recibido'    => $montoRecibido,
                'cambio'            => $cambio,
                'usuario_id'        => $data['user_id'],
            ]);

            // ==========================
            // ESTADO
            // ==========================
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

    /**
     * ✅ Resuelve el precio unitario real según reglas product_prices:
     * Prioridad:
     * 1) Caja (qty >= unidades_por_caja, precio_por_caja)
     * 2) Rango por cantidad (cantidad_min..cantidad_max, precio_por_cantidad)
     * 3) Base (product->price->precio_unitario o fallback product->precio_unitario)
     */
    private function resolveUnitPriceForQuantity(Product $product, int $qty): array
    {
        $product->loadMissing(['price', 'productPrices']);

        $prices = $product->productPrices ?? collect();

        // 1) CAJA: escoger el match con mayor unidades_por_caja (más específico)
        $box = $prices
            ->filter(function ($pp) use ($qty) {
                $minBox = (int)($pp->unidades_por_caja ?? 0);
                $pBox   = $pp->precio_por_caja;
                return $minBox > 0 && $pBox !== null && $pBox !== '' && $qty >= $minBox;
            })
            ->sortByDesc(fn ($pp) => (int)($pp->unidades_por_caja ?? 0))
            ->first();

        if ($box) {
            return [
                'unit_price' => (float) $box->precio_por_caja,
                'rule'       => 'caja',
                'price_id'   => $box->id ?? null,
            ];
        }

        // 2) RANGO POR CANTIDAD: escoger el match con mayor cantidad_min
        $tier = $prices
            ->filter(function ($pp) use ($qty) {
                $min = (int)($pp->cantidad_min ?? 0);
                $max = $pp->cantidad_max !== null ? (int)$pp->cantidad_max : null;
                $pQ  = $pp->precio_por_cantidad;

                if ($min <= 0) return false;
                if ($pQ === null || $pQ === '') return false;

                $okMin = $qty >= $min;
                $okMax = $max === null ? true : ($qty <= $max);

                return $okMin && $okMax;
            })
            ->sortByDesc(fn ($pp) => (int)($pp->cantidad_min ?? 0))
            ->first();

        if ($tier) {
            return [
                'unit_price' => (float) $tier->precio_por_cantidad,
                'rule'       => 'cantidad',
                'price_id'   => $tier->id ?? null,
            ];
        }

        // 3) BASE
        $base = null;

        if ($product->relationLoaded('price') && $product->price) {
            $base = $product->price->precio_unitario ?? null;
        }

        if ($base === null || $base === '') {
            // fallback si tienes columna directa
            $base = $product->precio_unitario ?? 0;
        }

        return [
            'unit_price' => (float) $base,
            'rule'       => 'base',
            'price_id'   => null,
        ];
    }
}
