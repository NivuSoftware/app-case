<?php

use App\Http\Controllers\Users\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])->group(function () {

    Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');

    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');

    Route::put('/usuarios/{id}', [UserController::class, 'update'])->name('usuarios.update');

    Route::delete('/usuarios/{id}', [UserController::class, 'destroy'])->name('usuarios.destroy');

});
