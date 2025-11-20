<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Clients\ClientController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::prefix('clients')->name('clients.')->group(function () {

        Route::get('/', [ClientController::class, 'index'])->name('index');

        Route::post('/', [ClientController::class, 'store'])->name('store');

        Route::get('/{id}', [ClientController::class, 'show'])->name('show');

        Route::put('/{id}', [ClientController::class, 'update'])->name('update');
        Route::patch('/{id}', [ClientController::class, 'update'])->name('update.partial');

        Route::delete('/{id}', [ClientController::class, 'destroy'])->name('destroy');

        Route::get('/search-by-identificacion', [ClientController::class, 'findByIdentificacion'])
            ->name('searchByIdentificacion');
    });
});
