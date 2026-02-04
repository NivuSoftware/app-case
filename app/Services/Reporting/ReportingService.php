<?php

namespace App\Services\Reporting;

use App\Models\Sri\ElectronicInvoice;
use App\Repositories\Reporting\ReportingRepository;
use App\Services\Store\BodegaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ReportingService
{
    public function __construct(
        private ReportingRepository $reporting,
        private BodegaService $bodegas
    )
    {
    }

    public function getInvoiceStatuses(Request $request): array
    {
        $estado = strtoupper(trim((string) $request->query('estado', '')));
        $q = trim((string) $request->query('q', ''));

        $maxReviewHours = (int) config('sri.max_review_hours', 72);

        $invoices = $this->reporting->getInvoiceStatuses($estado, $q, $maxReviewHours);

        $now = now();

        $invoices->getCollection()->transform(function (ElectronicInvoice $inv) use ($now, $maxReviewHours) {
            $estadoInv = strtoupper((string) ($inv->estado_sri ?? ''));

            $inv->pendiente_revision = false;
            if ($estadoInv !== 'AUTORIZADO' && $estadoInv !== 'RECHAZADO' && $inv->updated_at) {
                $inv->pendiente_revision = $inv->updated_at->diffInHours($now) >= $maxReviewHours;
            }

            if ($estadoInv === 'AUTORIZADO' || !$inv->updated_at) {
                $inv->dias_pendiente = null;
                $inv->pendiente_texto = null;
                return $inv;
            }

            $minutes = (int) $inv->updated_at->diffInMinutes($now);
            if ($minutes >= 1440) {
                $days = intdiv($minutes, 1440);
                $inv->pendiente_texto = "hace {$days}d";
                $inv->dias_pendiente = $days;
                return $inv;
            }

            if ($minutes >= 60) {
                $hours = intdiv($minutes, 60);
                $inv->pendiente_texto = "hace {$hours}h";
                $inv->dias_pendiente = 0;
                return $inv;
            }

            $inv->pendiente_texto = "hace {$minutes}m";
            $inv->dias_pendiente = 0;
            return $inv;
        });

        return [$invoices, $estado, $q];
    }

    public function getDailySalesByPaymentMethod(Request $request): array
    {
        $fechaInput = trim((string) $request->query('fecha', ''));
        $bodegaId = (int) $request->query('bodega_id', 0);
        if ($bodegaId <= 0) {
            $bodegaId = null;
        }
        $fecha = null;

        if ($fechaInput !== '') {
            try {
                $fecha = Carbon::parse($fechaInput)->startOfDay();
            } catch (\Throwable $e) {
                $fecha = null;
            }
        }

        if (!$fecha) {
            $fecha = now()->startOfDay();
        }

        $fechaStr = $fecha->toDateString();

        $rows = $this->reporting->getDailySalesByPaymentMethod($fechaStr, $bodegaId);

        $totalCobrado = (float) $rows->sum('total_monto');
        $totalVentas = $this->reporting->getTotalVentasByDate($fechaStr, $bodegaId);
        $totalVentasGeneral = $this->reporting->getTotalVentasByDate($fechaStr, null);
        $totalsByBodega = $this->reporting->getTotalsByBodega($fechaStr);
        if ($bodegaId) {
            $totalsByBodega = $totalsByBodega->filter(function ($row) use ($bodegaId) {
                return (int) ($row->bodega_id ?? 0) === (int) $bodegaId;
            })->values();
        }
        $bodegas = $this->bodegas->getAll()->sortBy('nombre')->values();

        return [
            'rows' => $rows,
            'fecha' => $fechaStr,
            'totalCobrado' => $totalCobrado,
            'totalVentas' => $totalVentas,
            'totalVentasGeneral' => $totalVentasGeneral,
            'totalsByBodega' => $totalsByBodega,
            'bodegas' => $bodegas,
            'bodegaId' => $bodegaId,
        ];
    }

    public function exportDailySalesByPaymentMethod(Request $request): Response
    {
        $payload = $this->getDailySalesByPaymentMethod($request);

        $fecha = (string) ($payload['fecha'] ?? now()->toDateString());
        $filename = "venta_diaria_forma_pago_{$fecha}.xls";

        $html = $this->buildDailySalesByPaymentMethodExcelHtml($payload);

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function buildDailySalesByPaymentMethodExcelHtml(array $payload): string
    {
        $fecha = (string) ($payload['fecha'] ?? '');
        $bodegaId = $payload['bodegaId'] ?? null;
        $bodegas = $payload['bodegas'] ?? collect();

        $bodegaNombre = 'Todas las bodegas';
        if ($bodegaId) {
            $match = $bodegas->firstWhere('id', $bodegaId);
            if ($match) {
                $bodegaNombre = (string) $match->nombre;
            }
        }

        $totalVentasGeneral = (float) ($payload['totalVentasGeneral'] ?? 0);
        $totalVentas = (float) ($payload['totalVentas'] ?? 0);

        $totalsByBodega = $payload['totalsByBodega'] ?? collect();
        $rows = $payload['rows'] ?? collect();

        $money = fn($v) => number_format((float) $v, 2);

        $headerBg = '#1D4ED8';
        $headerText = '#FFFFFF';
        $subHeaderBg = '#DBEAFE';
        $border = '#BFDBFE';
        $text = '#0F172A';

        $html = [];
        $html[] = '<html><head><meta charset="utf-8" />'; 
        $html[] = '<style>';
        $html[] = "body{font-family:Calibri, Arial, sans-serif;color:{$text};}";
        $html[] = "table{border-collapse:collapse;width:100%;}";
        $html[] = "th,td{border:1px solid {$border};padding:6px;font-size:12px;}";
        $html[] = ".title{background:{$headerBg};color:{$headerText};font-weight:bold;font-size:16px;text-align:left;}";
        $html[] = ".section{background:{$subHeaderBg};font-weight:bold;}";
        $html[] = ".right{text-align:right;}";
        $html[] = ".muted{color:#334155;}";
        $html[] = '</style></head><body>';

        $html[] = '<table>';
        $html[] = '<tr><td class="title" colspan="4">Venta diaria por forma de pago</td></tr>';
        $html[] = '<tr><td class="section" colspan="4">Resumen</td></tr>';
        $html[] = '<tr><td class="muted">Fecha</td><td colspan="3">' . e($fecha) . '</td></tr>';
        $html[] = '<tr><td class="muted">Bodega</td><td colspan="3">' . e($bodegaNombre) . '</td></tr>';
        if (!$bodegaId) {
            $html[] = '<tr><td class="muted">Total facturado (todas bodegas)</td><td class="right" colspan="3">$' . $money($totalVentasGeneral) . '</td></tr>';
        }
        $html[] = '<tr><td class="muted">Total facturado (bodega seleccionada)</td><td class="right" colspan="3">$' . $money($totalVentas) . '</td></tr>';

        $html[] = '<tr><td colspan="4"></td></tr>';
        $html[] = '<tr><td class="section" colspan="4">Total facturado por bodega</td></tr>';
        $html[] = '<tr>';
        $html[] = '<th>Bodega</th><th class="right">Ventas</th><th class="right" colspan="2">Total facturado</th>';
        $html[] = '</tr>';

        if ($totalsByBodega->count()) {
            foreach ($totalsByBodega as $row) {
                $html[] = '<tr>';
                $html[] = '<td>' . e($row->bodega_nombre ?? 'N/D') . '</td>';
                $html[] = '<td class="right">' . (int) ($row->ventas ?? 0) . '</td>';
                $html[] = '<td class="right" colspan="2">$' . $money($row->total_facturado ?? 0) . '</td>';
                $html[] = '</tr>';
            }
        } else {
            $html[] = '<tr><td colspan="4">No hay ventas registradas para este dia.</td></tr>';
        }

        $html[] = '<tr><td colspan="4"></td></tr>';
        $html[] = '<tr><td class="section" colspan="4">Consolidado por forma de pago</td></tr>';
        $html[] = '<tr>';
        $html[] = '<th>Forma de pago</th><th class="right">Pagos</th><th class="right">Ventas</th><th class="right">Total</th>';
        $html[] = '</tr>';

        if ($rows->count()) {
            foreach ($rows as $row) {
                $html[] = '<tr>';
                $html[] = '<td>' . e($row->metodo ?? 'N/D') . '</td>';
                $html[] = '<td class="right">' . (int) ($row->pagos ?? 0) . '</td>';
                $html[] = '<td class="right">' . (int) ($row->ventas ?? 0) . '</td>';
                $html[] = '<td class="right">$' . $money($row->total_monto ?? 0) . '</td>';
                $html[] = '</tr>';
            }
        } else {
            $html[] = '<tr><td colspan="4">No hay ventas registradas para este dia.</td></tr>';
        }

        $html[] = '</table>';
        $html[] = '</body></html>';

        return implode('', $html);
    }
}
