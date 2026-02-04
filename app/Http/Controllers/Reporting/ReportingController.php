<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportingService;
use Illuminate\Http\Request;

class ReportingController extends Controller
{
    public function __construct(private ReportingService $reporting)
    {
    }

    public function menu()
    {
        return view('reporting.menu');
    }

    public function invoiceStatuses(Request $request)
    {
        [$invoices, $estado, $q] = $this->reporting->getInvoiceStatuses($request);

        return view('reporting.invoices.statuses', [
            'invoices' => $invoices,
            'estado' => $estado,
            'q' => $q,
        ]);
    }

    public function dailySalesByPaymentMethod(Request $request)
    {
        $payload = $this->reporting->getDailySalesByPaymentMethod($request);

        return view('reporting.sales.daily-by-payment', $payload);
    }

    public function exportDailySalesByPaymentMethod(Request $request)
    {
        return $this->reporting->exportDailySalesByPaymentMethod($request);
    }

    public function cashClosuresDaily(Request $request)
    {
        $payload = $this->reporting->getCashClosuresDaily($request);

        return view('reporting.cashier.closures-daily', $payload);
    }

    public function exportCashClosuresDaily(Request $request)
    {
        return $this->reporting->exportCashClosuresDaily($request);
    }
}
