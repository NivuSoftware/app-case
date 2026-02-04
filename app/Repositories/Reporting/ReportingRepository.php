<?php

namespace App\Repositories\Reporting;

use App\Models\Sales\Sale;
use App\Models\Sales\SalePayment;
use App\Models\Sri\ElectronicInvoice;
use App\Models\Cashier\CashSession;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ReportingRepository
{
    public function getInvoiceStatuses(string $estado, string $q, int $maxReviewHours): LengthAwarePaginator
    {
        $query = ElectronicInvoice::with('sale')
            ->orderByDesc('updated_at');

        if ($estado !== '') {
            if ($estado === 'PENDIENTE_REVISION') {
                $query->whereNotIn('estado_sri', ['AUTORIZADO', 'RECHAZADO'])
                    ->where('updated_at', '<=', now()->subHours($maxReviewHours));
            } else {
                $query->where('estado_sri', $estado);
            }
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('clave_acceso', 'like', "%{$q}%")
                    ->orWhereHas('sale', function ($saleQ) use ($q) {
                        $saleQ->where('id', $q)
                            ->orWhere('num_factura', 'like', "%{$q}%");
                    });
            });
        }

        return $query->paginate(50)->withQueryString();
    }

    public function getDailySalesByPaymentMethod(string $fechaStr, ?int $bodegaId): Collection
    {
        $query = SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->leftJoin('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereDate('sales.fecha_venta', $fechaStr);

        if ($bodegaId) {
            $query->where('sales.bodega_id', $bodegaId);
        }

        return $query
            ->selectRaw('COALESCE(payment_methods.nombre, sale_payments.metodo) as metodo')
            ->selectRaw('COUNT(sale_payments.id) as pagos')
            ->selectRaw('COUNT(DISTINCT sales.id) as ventas')
            ->selectRaw('SUM(sale_payments.monto) as total_monto')
            ->groupByRaw('COALESCE(payment_methods.nombre, sale_payments.metodo)')
            ->orderByDesc('total_monto')
            ->get();
    }

    public function getTotalVentasByDate(string $fechaStr, ?int $bodegaId): float
    {
        $query = Sale::whereDate('fecha_venta', $fechaStr);

        if ($bodegaId) {
            $query->where('bodega_id', $bodegaId);
        }

        return (float) $query->sum('total');
    }

    public function getTotalsByBodega(string $fechaStr): Collection
    {
        return Sale::query()
            ->leftJoin('bodegas', 'sales.bodega_id', '=', 'bodegas.id')
            ->whereDate('sales.fecha_venta', $fechaStr)
            ->selectRaw('sales.bodega_id as bodega_id')
            ->selectRaw('COALESCE(bodegas.nombre, \'Sin bodega\') as bodega_nombre')
            ->selectRaw('COUNT(sales.id) as ventas')
            ->selectRaw('SUM(sales.total) as total_facturado')
            ->groupByRaw('sales.bodega_id, COALESCE(bodegas.nombre, \'Sin bodega\')')
            ->orderByDesc('total_facturado')
            ->get();
    }

    public function getClosedCashSessionsByDate(string $fechaStr): Collection
    {
        return CashSession::with(['opener', 'closer'])
            ->whereNotNull('closed_at')
            ->whereDate('closed_at', $fechaStr)
            ->orderBy('closed_at', 'asc')
            ->get();
    }

    public function getPaymentTotalsForUserBetween(int $userId, Carbon $from, Carbon $to): Collection
    {
        return SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->leftJoin('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->where('sales.user_id', $userId)
            ->whereBetween('sales.fecha_venta', [$from, $to])
            ->selectRaw('COALESCE(payment_methods.nombre, sale_payments.metodo) as metodo')
            ->selectRaw('COUNT(sale_payments.id) as pagos')
            ->selectRaw('COUNT(DISTINCT sales.id) as ventas')
            ->selectRaw('SUM(sale_payments.monto) as total_monto')
            ->groupByRaw('COALESCE(payment_methods.nombre, sale_payments.metodo)')
            ->orderByDesc('total_monto')
            ->get();
    }

    public function getPaymentMethodsBetween(Carbon $from, Carbon $to): Collection
    {
        return SalePayment::query()
            ->join('sales', 'sale_payments.sale_id', '=', 'sales.id')
            ->leftJoin('payment_methods', 'sale_payments.payment_method_id', '=', 'payment_methods.id')
            ->whereBetween('sales.fecha_venta', [$from, $to])
            ->selectRaw('COALESCE(payment_methods.nombre, sale_payments.metodo) as metodo')
            ->distinct()
            ->orderBy('metodo')
            ->pluck('metodo');
    }
}
