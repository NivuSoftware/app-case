<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Store\PerchaController;

Route::prefix('perchas')->group(function () {

    Route::get('/', [PerchaController::class, 'index']);                 // listar todas las perchas
    Route::get('/{id}', [PerchaController::class, 'show']);              // obtener percha por ID

    Route::get('/bodega/{bodegaId}', [PerchaController::class, 'getByBodega']); // listar perchas por bodega

    Route::post('/', [PerchaController::class, 'store']);                // crear percha
    Route::put('/{id}', [PerchaController::class, 'update']);            // actualizar percha
    Route::delete('/{id}', [PerchaController::class, 'destroy']);        // eliminar percha
});
