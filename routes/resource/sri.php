<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Sri\SriConfigController;
use App\Http\Controllers\Sri\SriInvoiceController;

Route::prefix('sri')->name('sri.')->group(function () {
    Route::get('/config',  [SriConfigController::class, 'edit'])->name('config.edit');
    Route::post('/config', [SriConfigController::class, 'store'])->name('config.store'); 
});

Route::middleware(['auth', 'role:admin'])->prefix('sri')->name('sri.')->group(function () {
    Route::post('/invoices/{saleId}/generate', [SriInvoiceController::class, 'generate'])
        ->name('invoices.generate');
});