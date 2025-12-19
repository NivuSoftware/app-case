<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Sales\SalePrintController;


Route::middleware(['auth'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | VISTAS DEL MÓDULO DE VENTAS
    |--------------------------------------------------------------------------
    */

    // Vista principal del módulo de ventas / POS
    Route::get('/facturar/{bodega}', [SaleController::class, 'viewIndex'])
        ->name('ventas.index');

    Route::get('/bodega', [SaleController::class, 'viewSelectBodega'])
        ->name('ventas.select_bodega');

    Route::get('/ventas/{id}/ticket', [SalePrintController::class, 'ticket'])
        ->name('sales.ticket');

    /*
    |--------------------------------------------------------------------------
    | ENDPOINTS JSON (API VENTAS)
    |--------------------------------------------------------------------------
    */

    Route::prefix('/api/ventas')->name('api.ventas.')->group(function () {
        // Crear una venta (venta completa: cabecera + items + pago)
        Route::post('/', [SaleController::class, 'store'])
            ->name('store');

        // Ver una venta específica (para detalle / reimpresión)
        Route::get('/{id}', [SaleController::class, 'show'])
            ->name('show');

        

        // Más adelante podemos añadir:
        // Route::get('/', [SaleController::class, 'index'])->name('index');
        // Route::post('/{id}/anular', [SaleController::class, 'anular'])->name('anular');
    });
});
