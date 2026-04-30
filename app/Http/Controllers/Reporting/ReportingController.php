<?php

namespace App\Http\Controllers\Reporting;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportingService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

    public function invoices(Request $request)
    {
        $payload = $this->reporting->getInvoicesReport($request);

        return view('reporting.invoices.index', $payload);
    }

    public function showRide(int $saleId): Response
    {
        return $this->reporting->streamInvoiceRide($saleId);
    }

    public function resendInvoiceMail(Request $request, int $saleId)
    {
        $payload = $this->reporting->resendInvoiceMail($saleId, $request->all());

        return response()->json([
            'ok' => true,
            'message' => 'Correo puesto en cola para reenvio.',
            'data' => $payload,
        ]);
    }

    public function downloadFilteredInvoiceXmlZip(Request $request)
    {
        return $this->reporting->downloadFilteredInvoiceXmlZip($request);
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

    public function printDailySalesByPaymentMethod(Request $request)
    {
        $payload = $this->reporting->getDailySalesByPaymentMethod($request);

        return view('reporting.sales.daily-by-payment-print', $payload);
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

    public function cashClosureDetail(int $id)
    {
        $payload = $this->reporting->getCashClosureDetail($id);

        return view('reporting.cashier.closure-detail', $payload);
    }

    public function printCashClosureDetail(int $id)
    {
        $payload = $this->reporting->getCashClosureDetail($id);

        return view('reporting.cashier.closure-detail-print', $payload);
    }

    public function monthlySalesReport(Request $request)
    {
        $payload = $this->reporting->getMonthlySalesReport($request);

        return view('reporting.sales.monthly', $payload);
    }

    public function salesRangeReport(Request $request)
    {
        $payload = $this->reporting->getSalesRangeReport($request);

        return view('reporting.sales.range', $payload);
    }

    public function exportMonthlySalesReport(Request $request)
    {
        return $this->reporting->exportMonthlySalesReport($request);
    }

    public function exportSalesRangeReport(Request $request)
    {
        return $this->reporting->exportSalesRangeReport($request);
    }

    public function topProductsReport(Request $request)
    {
        $payload = $this->reporting->getTopProductsReport($request);

        return view('reporting.sales.top-products', $payload);
    }

    public function exportTopProductsReport(Request $request)
    {
        return $this->reporting->exportTopProductsReport($request);
    }

    public function inventoryByProductReport(Request $request)
    {
        $payload = $this->reporting->getInventoryByProductReport($request);

        return view('reporting.inventory.products', $payload);
    }

    public function exportInventoryByProductReport(Request $request)
    {
        return $this->reporting->exportInventoryByProductReport($request);
    }
}
