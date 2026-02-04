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

        Route::get('/ventas-diarias-forma-pago', [ReportingController::class, 'dailySalesByPaymentMethod'])
            ->name('reporteria.sales.daily.by-payment');

        Route::get('/ventas-diarias-forma-pago/export', [ReportingController::class, 'exportDailySalesByPaymentMethod'])
            ->name('reporteria.sales.daily.by-payment.export');

        Route::get('/cierres-caja-diarios', [ReportingController::class, 'cashClosuresDaily'])
            ->name('reporteria.cashier.closures.daily');

        Route::get('/cierres-caja-diarios/export', [ReportingController::class, 'exportCashClosuresDaily'])
            ->name('reporteria.cashier.closures.daily.export');
    });
