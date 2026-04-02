<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sales\QueuedSaleController;
use App\Http\Controllers\Sales\QueuedSalePrintController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SalePrintController;
use App\Http\Controllers\Sri\SriInvoiceController;

/*
|--------------------------------------------------------------------------
| VENTAS / FACTURACIÓN (cashier|admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:cashier|admin|supervisor'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | VISTAS DEL MÓDULO DE VENTAS
    |--------------------------------------------------------------------------
    */

    // Selección de bodega
    Route::get('/bodega', [SaleController::class, 'viewSelectBodega'])
        ->name('ventas.select_bodega');

    // Vista principal POS
    Route::get('/facturar/{bodega}', [SaleController::class, 'viewIndex'])
        ->name('ventas.index');

    // Ticket / impresión
    Route::get('/ventas/{id}/ticket', [SalePrintController::class, 'ticket'])
        ->name('sales.ticket');
    Route::get('/ventas/queue/{id}/ticket', [QueuedSalePrintController::class, 'ticket'])
        ->name('sales.queue.ticket');

    // Consultar autorizacion SRI (1 vez)
    Route::post('/sales/{saleId}/sri/consult-authorization', [SriInvoiceController::class, 'consultAuthorization'])
        ->name('sales.sri.consult_authorization');

    /*
    |--------------------------------------------------------------------------
    | ENDPOINTS JSON (API VENTAS)
    |--------------------------------------------------------------------------
    */

    Route::prefix('/api/ventas')->name('api.ventas.')->group(function () {
        Route::get('/queue', [QueuedSaleController::class, 'index'])
            ->name('queue.index');
        Route::post('/queue', [QueuedSaleController::class, 'store'])
            ->name('queue.store');
        Route::post('/queue/{id}/requeue', [QueuedSaleController::class, 'requeue'])
            ->name('queue.requeue');
        Route::post('/queue/{id}/pause', [QueuedSaleController::class, 'pause'])
            ->name('queue.pause');
        Route::post('/queue/{id}/resume', [QueuedSaleController::class, 'resume'])
            ->name('queue.resume');
        Route::post('/queue/{id}/edit', [QueuedSaleController::class, 'edit'])
            ->name('queue.edit');
        Route::delete('/queue/{id}', [QueuedSaleController::class, 'destroy'])
            ->name('queue.destroy');

        // Crear una venta (cabecera + items + pago)
        Route::post('/', [SaleController::class, 'store'])
            ->name('store');

        // Ver una venta específica (detalle / reimpresión)
        Route::get('/{id}', [SaleController::class, 'show'])
            ->name('show');

        // Futuro:
        // Route::get('/', [SaleController::class, 'index'])->name('index');
        // Route::post('/{id}/anular', [SaleController::class, 'anular'])->name('anular');
    });
});
