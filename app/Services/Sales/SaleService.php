<?php

namespace App\Services\Sales;

use App\Models\Product\Product;
use App\Models\Sales\PaymentMethod;
use App\Repositories\Sales\SaleRepository;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Sales\Sale;
use App\Services\Cashier\CashierService;
use App\Models\Clients\ClientEmail;
use App\Jobs\ProcessSriInvoiceJob;
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
        $this->sales = $sales;
        $this->inventory = $inventory;
    }


    public function crearVenta(array $data): Sale
    {
        return DB::transaction(function () use ($data) {

            $cajaId = (int) ($data['caja_id'] ?? 0);

            if ($cajaId <= 0) {
                throw ValidationException::withMessages([
                    'caja_id' => 'Debes indicar el número de caja (caja_id).',
                ]);
            }

            $this->cashier->getOpenSessionOrFail($cajaId);

            $items = $data['items'] ?? [];
            $payments = $this->normalizePayments($data);

            if (empty($items)) {
                throw ValidationException::withMessages([
                    'items' => 'La venta debe tener al menos un ítem.',
                ]);
            }

            if (empty($payments)) {
                throw ValidationException::withMessages([
                    'payments' => 'Debe registrar al menos un pago.',
                ]);
            }

            $ivaEnabled = (bool) ($data['iva_enabled'] ?? true);

            $toCents = function ($n): int {
                $n = $n ?? 0;
                return (int) round(((float) $n) * 100, 0, PHP_ROUND_HALF_UP);
            };

            $fromCents = function (int $cents): float {
                return round($cents / 100, 2);
            };

            $toBp = function ($pct): int {
                $p = (float) ($pct ?? 0);
                if ($p < 0)
                    $p = 0;
                if ($p > 100)
                    $p = 100;
                return (int) round($p * 100, 0, PHP_ROUND_HALF_UP);
            };

            $subtotalCents = 0;
            $descuentoCents = 0;
            $baseImponibleCents = 0;
            $ivaCents = 0;
            $impuestoCents = 0;

            foreach ($items as $idx => &$item) {

                $productoId = (int) ($item['producto_id'] ?? 0);
                $cantidad = (int) ($item['cantidad'] ?? 0);

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
                $precioUnitario = (float) ($pricing['effective_unit_price'] ?? 0);

                if (!is_finite($precioUnitario) || $precioUnitario < 0) {
                    throw ValidationException::withMessages([
                        "items.$idx.precio_unitario" => 'Precio unitario inválido.',
                    ]);
                }

                $descCts = $toCents($item['descuento'] ?? 0);
                if ($descCts < 0)
                    $descCts = 0;

                $lineSubtotalCts = (int) ($pricing['line_subtotal_cents'] ?? 0);

                if ($descCts > $lineSubtotalCts) {
                    throw ValidationException::withMessages([
                        "items.$idx.descuento" => 'El descuento no puede superar el valor de la línea.',
                    ]);
                }

                $lineTotalWithIvaCts = $lineSubtotalCts - $descCts;

                $ivaPctProducto = $product->iva_porcentaje;
                if ($ivaPctProducto === null || $ivaPctProducto === '') {
                    $ivaPctProducto = 15;
                }

                $ivaPctFinal = $ivaEnabled ? (float) $ivaPctProducto : 0.0;
                $bp = $toBp($ivaPctFinal);
                $divisor = 10000 + $bp;
                $lineBaseCts = $divisor > 0
                    ? (int) floor(($lineTotalWithIvaCts * 10000 + intdiv($divisor, 2)) / $divisor)
                    : $lineTotalWithIvaCts;
                $lineIvaCts = $lineTotalWithIvaCts - $lineBaseCts;

                $item['precio_unitario'] = $precioUnitario;
                $item['iva_porcentaje'] = $ivaPctFinal;

                $item['pricing_rule'] = $pricing['rule'] ?? null;
                $item['pricing_price_id'] = $pricing['price_id'] ?? null;

                $item['total'] = $fromCents($lineBaseCts);

                $subtotalCents += $lineSubtotalCts;
                $descuentoCents += $descCts;
                $baseImponibleCents += $lineBaseCts;
                $ivaCents += $lineIvaCts;
            }
            unset($item);

            $totalCents = $baseImponibleCents + $impuestoCents + $ivaCents;

            $subtotal = $fromCents($baseImponibleCents);
            $descuentoTotal = $fromCents($descuentoCents);
            $impuesto = $fromCents($impuestoCents);
            $iva = $fromCents($ivaCents);
            $total = $fromCents($totalCents);

            $clientId = $data['client_id'] ?? null;
            $clientEmailId = $data['client_email_id'] ?? null;
            $emailDestino = $data['email_destino'] ?? null;

            // VALIDACIÓN: Si el total es >= 50, no puede ser Consumidor Final
            if ($total >= 50) {
                // Caso 1: No hay cliente seleccionado (array key vacío o null) -> Es consumidor final implícito
                if (!$clientId) {
                    throw ValidationException::withMessages([
                        'client_id' => 'Para facturas de $50 o más, debe seleccionar un cliente válido (no Consumidor Final).',
                    ]);
                }

                // Caso 2: Se envió un ID, verificar si es el CF de la base de datos
                // (Por si acaso alguien creó un cliente llamado "Consumidor Final" manualmente y lo seleccionó)
                $clientCheck = \App\Models\Clients\Client::find($clientId);
                if ($clientCheck) {
                    // Normalizamos comparación
                    $esConsumidorFinal =
                        (trim($clientCheck->identificacion) === '9999999999999') ||
                        (strtoupper(trim($clientCheck->business)) === 'CONSUMIDOR FINAL');

                    if ($esConsumidorFinal) {
                        throw ValidationException::withMessages([
                            'client_id' => 'Para facturas de $50 o más, NO puede ser Consumidor Final.',
                        ]);
                    }
                }
            }

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
                'client_id' => $clientId,
                'user_id' => $data['user_id'],
                'client_email_id' => $clientEmailId,
                'email_destino' => $emailDestino,
                'bodega_id' => $data['bodega_id'],
                'fecha_venta' => $data['fecha_venta'],
                'tipo_documento' => $data['tipo_documento'] ?? 'FACTURA',
                'num_factura' => $data['num_factura'] ?? null, // normalmente null
                'subtotal' => $subtotal,
                'descuento' => $descuentoTotal,
                'impuesto' => $impuesto,
                'iva' => $iva,
                'total' => $total,
                'estado' => 'pendiente',
                'observaciones' => $data['observaciones'] ?? null,
            ];

            // 1) Creo la venta
            $sale = $this->sales->createSale($saleData);

            // 2) Guardo items (AÚN SIN descontar stock)
            foreach ($items as $item) {
                $this->sales->addItem($sale, [
                    'producto_id' => $item['producto_id'],
                    'descripcion' => $item['descripcion'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'descuento' => $item['descuento'] ?? 0,
                    'iva_porcentaje' => $item['iva_porcentaje'] ?? 0,
                    'total' => $item['total'], // sin IVA
                ]);
            }

            // 3) Pagos
            $resolvedPayments = $this->resolvePayments(
                $payments,
                $totalCents,
                $data['user_id'],
                $data['fecha_venta'] ?? null,
                $toCents,
                $fromCents
            );

            foreach ($resolvedPayments['records'] as $paymentRecord) {
                $this->sales->addPayment($sale, $paymentRecord);
            }

            // 4) Marco como pagada
            $this->sales->updateEstado($sale, 'pagada');

            // 5) ✅ Genero el num_factura (aquí se setea en sales.num_factura)
            if (($sale->tipo_documento ?? 'FACTURA') === 'FACTURA') {
                $this->sriInvoiceService->generateXmlForSale($sale->id);
                $sale->refresh(); // ✅ ahora $sale->num_factura ya existe
            }

            // 6) ✅ Recién AHORA descuento stock (ya con num_factura real)
            $vendioSinStock = false;

            foreach ($items as $item) {
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

            // 7) Caja (solo por la porcion en efectivo)
            $cashPortion = (float) ($resolvedPayments['cash_amount'] ?? 0);

            if ($cashPortion > 0) {
                $this->cashier->registerSaleIncome(
                    $cajaId,
                    (int) $data['user_id'],
                    (int) $sale->id,
                    $sale->num_factura,
                    $cashPortion
                );
            }

            // 8) Job SRI completo (firma + envío + autorización + correo)
            if (($sale->tipo_documento ?? 'FACTURA') === 'FACTURA') {
                ProcessSriInvoiceJob::dispatch($sale->id)->afterCommit();
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

    private function normalizePayments(array $data): array
    {
        $payments = $data['payments'] ?? null;
        if (is_array($payments) && !empty($payments)) {
            return array_values($payments);
        }

        $payment = $data['payment'] ?? null;
        if (is_array($payment) && !empty($payment)) {
            return [$payment];
        }

        return [];
    }

    private function resolvePayments(
        array $payments,
        int $totalCents,
        int|string $userId,
        mixed $fechaVenta,
        callable $toCents,
        callable $fromCents
    ): array {
        $paymentMethodIds = collect($payments)
            ->pluck('payment_method_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $paymentMethods = PaymentMethod::query()
            ->whereIn('id', $paymentMethodIds)
            ->get()
            ->keyBy('id');

        $resolved = [];
        $seenMethods = [];
        $declaredCents = 0;
        $cashAmountCents = 0;
        $cashRows = 0;

        foreach ($payments as $idx => $payment) {
            $paymentMethodId = (int) ($payment['payment_method_id'] ?? 0);
            $paymentMethod = $paymentMethodIds !== []
                ? $paymentMethods->get($paymentMethodId)
                : null;

            if (!$paymentMethod) {
                throw ValidationException::withMessages([
                    "payments.{$idx}.payment_method_id" => 'Debes seleccionar un método de pago válido.',
                ]);
            }

            $metodo = trim((string) ($paymentMethod->nombre ?? $payment['metodo'] ?? ''));
            if ($metodo === '') {
                throw ValidationException::withMessages([
                    "payments.{$idx}.metodo" => 'El método de pago es obligatorio.',
                ]);
            }

            $metodoKey = strtoupper($metodo);
            if (isset($seenMethods[$metodoKey])) {
                throw ValidationException::withMessages([
                    "payments.{$idx}.metodo" => 'No puedes repetir el mismo método de pago en la factura.',
                ]);
            }
            $seenMethods[$metodoKey] = true;

            $rawMonto = $payment['monto'] ?? null;
            if (($rawMonto === null || $rawMonto === '') && count($payments) === 1) {
                $rawMonto = $fromCents($totalCents);
            }

            $montoCents = $toCents($rawMonto);
            if ($montoCents <= 0) {
                throw ValidationException::withMessages([
                    "payments.{$idx}.monto" => 'El monto del pago debe ser mayor a 0.',
                ]);
            }

            $isCash = $this->isCashMethod($metodo);
            $montoRecibido = null;
            $cambio = null;

            if ($isCash) {
                $cashRows++;
                if ($cashRows > 1) {
                    throw ValidationException::withMessages([
                        "payments.{$idx}.metodo" => 'Solo se permite una línea de pago en efectivo por factura.',
                    ]);
                }

                $montoRecibidoCents = $toCents($payment['monto_recibido'] ?? 0);
                if ($montoRecibidoCents < $montoCents) {
                    throw ValidationException::withMessages([
                        "payments.{$idx}.monto_recibido" => 'El monto recibido en efectivo no puede ser menor al monto declarado para efectivo.',
                    ]);
                }

                $montoRecibido = $fromCents($montoRecibidoCents);
                $cambio = $fromCents($montoRecibidoCents - $montoCents);
                $cashAmountCents += $montoCents;
            } else {
                $rawRecibido = $payment['monto_recibido'] ?? null;
                if ($rawRecibido !== null && trim((string) $rawRecibido) !== '') {
                    throw ValidationException::withMessages([
                        "payments.{$idx}.monto_recibido" => 'Solo el pago en efectivo puede tener monto recibido.',
                    ]);
                }
            }

            $declaredCents += $montoCents;

            $resolved[] = [
                'fecha_pago' => $payment['fecha_pago'] ?? ($fechaVenta ?: now()),
                'monto' => $fromCents($montoCents),
                'metodo' => $metodo,
                'payment_method_id' => $paymentMethodId,
                'referencia' => $this->nullableString($payment['referencia'] ?? null),
                'observaciones' => $this->nullableString($payment['observaciones'] ?? null),
                'monto_recibido' => $montoRecibido,
                'cambio' => $cambio,
                'usuario_id' => (int) $userId,
            ];
        }

        if ($declaredCents !== $totalCents) {
            $expected = number_format($fromCents($totalCents), 2, '.', '');
            $declared = number_format($fromCents($declaredCents), 2, '.', '');

            throw ValidationException::withMessages([
                'payments' => "La suma de los pagos debe completar exactamente el total de la factura. Total: {$expected}. Declarado: {$declared}.",
            ]);
        }

        return [
            'records' => $resolved,
            'cash_amount' => $fromCents($cashAmountCents),
        ];
    }

    private function isCashMethod(string $metodo): bool
    {
        return in_array(strtoupper(trim($metodo)), ['EFECTIVO', 'CASH'], true);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
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
                    $min = (int) ($pp->cantidad_min ?? 0);
                    $max = $pp->cantidad_max !== null ? (int) $pp->cantidad_max : null;
                    $pQ = $pp->precio_por_cantidad;

                    if ($min <= 0)
                        return false;
                    if ($pQ === null || $pQ === '')
                        return false;
                    if ($qty < $min)
                        return false;
                    if ($max !== null && $qty > $max)
                        return false;
                    return true;
                })
                ->sortByDesc(fn($pp) => (int) ($pp->cantidad_min ?? 0))
                ->first();

            if ($tier)
                return $tier;

            return $prices
                ->filter(function ($pp) use ($qty) {
                    $min = (int) ($pp->cantidad_min ?? 0);
                    $pQ = $pp->precio_por_cantidad;
                    if ($min <= 0)
                        return false;
                    if ($pQ === null || $pQ === '')
                        return false;
                    return $qty >= $min;
                })
                ->sortByDesc(fn($pp) => (int) ($pp->cantidad_min ?? 0))
                ->first();
        };

        $tier = $pickTier();
        $tierUnit = $tier ? (float) $tier->precio_por_cantidad : $baseUnit;

        // ===== caja aplicable (mayor unidades_por_caja) =====
        $box = $prices
            ->filter(function ($pp) use ($qty) {
                $upc = (int) ($pp->unidades_por_caja ?? 0);
                $pBox = $pp->precio_por_caja;
                if ($upc <= 0)
                    return false;
                if ($pBox === null || $pBox === '')
                    return false;
                return $qty >= $upc;
            })
            ->sortByDesc(fn($pp) => (int) ($pp->unidades_por_caja ?? 0))
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
                'line_subtotal_cents' => $lineSubtotalCts,
                'effective_unit_price' => $fromCents($effectiveUnitCts),
                'rule' => 'caja',
                'price_id' => $box->id ?? null,
            ];
        }

        // ===== si NO aplica caja: qty * tierUnit =====
        $tierUnitCts = $toCents($tierUnit);
        $lineSubtotalCts = $qty * $tierUnitCts;

        return [
            'line_subtotal_cents' => $lineSubtotalCts,
            'effective_unit_price' => $tierUnit,
            'rule' => $tier ? 'cantidad' : 'base',
            'price_id' => $tier->id ?? null,
        ];
    }

}
