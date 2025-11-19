<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Store\BodegaController;

Route::prefix('bodegas')->group(function () {

    Route::get('/', [BodegaController::class, 'index']);     // listar todas
    Route::get('/{id}', [BodegaController::class, 'show']);  // obtener por ID

    Route::post('/', [BodegaController::class, 'store']);    // crear
    Route::put('/{id}', [BodegaController::class, 'update']); // actualizar

    Route::delete('/{id}', [BodegaController::class, 'destroy']); // eliminar
});
