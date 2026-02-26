<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Reporting\ReportingController;

/*
|--------------------------------------------------------------------------
| REPORTERIA (solo admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('reporteria')
    ->group(function () {
        Route::get('/', [ReportingController::class, 'menu'])
            ->name('reporteria.menu');

        Route::get('/estados-facturas', [ReportingController::class, 'invoiceStatuses'])
            ->name('reporteria.invoices.statuses');
        Route::get('/estados-facturas/xml-zip', [ReportingController::class, 'downloadFilteredInvoiceXmlZip'])
            ->name('reporteria.invoices.statuses.xml-zip');

        Route::get('/ventas-diarias-forma-pago', [ReportingController::class, 'dailySalesByPaymentMethod'])
            ->name('reporteria.sales.daily.by-payment');

        Route::get('/ventas-diarias-forma-pago/export', [ReportingController::class, 'exportDailySalesByPaymentMethod'])
            ->name('reporteria.sales.daily.by-payment.export');

        Route::get('/ventas-diarias-forma-pago/print', [ReportingController::class, 'printDailySalesByPaymentMethod'])
            ->name('reporteria.sales.daily.by-payment.print');

        Route::get('/cierres-caja-diarios', [ReportingController::class, 'cashClosuresDaily'])
            ->name('reporteria.cashier.closures.daily');

        Route::get('/cierres-caja-diarios/export', [ReportingController::class, 'exportCashClosuresDaily'])
            ->name('reporteria.cashier.closures.daily.export');

        Route::get('/cierres-caja-diarios/{id}/detalle', [ReportingController::class, 'cashClosureDetail'])
            ->whereNumber('id')
            ->name('reporteria.cashier.closures.detail');

        Route::get('/cierres-caja-diarios/{id}/detalle/print', [ReportingController::class, 'printCashClosureDetail'])
            ->whereNumber('id')
            ->name('reporteria.cashier.closures.detail.print');

        Route::get('/ventas-mensuales', [ReportingController::class, 'monthlySalesReport'])
            ->name('reporteria.sales.monthly');

        Route::get('/ventas-por-rango', [ReportingController::class, 'salesRangeReport'])
            ->name('reporteria.sales.range');

        Route::get('/ventas-mensuales/export', [ReportingController::class, 'exportMonthlySalesReport'])
            ->name('reporteria.sales.monthly.export');

        Route::get('/ventas-por-rango/export', [ReportingController::class, 'exportSalesRangeReport'])
            ->name('reporteria.sales.range.export');

        Route::get('/top-productos', [ReportingController::class, 'topProductsReport'])
            ->name('reporteria.sales.top-products');

        Route::get('/top-productos/export', [ReportingController::class, 'exportTopProductsReport'])
            ->name('reporteria.sales.top-products.export');

        Route::get('/inventario-productos', [ReportingController::class, 'inventoryByProductReport'])
            ->name('reporteria.inventory.products');

        Route::get('/inventario-productos/export', [ReportingController::class, 'exportInventoryByProductReport'])
            ->name('reporteria.inventory.products.export');
    });
