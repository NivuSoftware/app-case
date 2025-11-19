<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PasswordController;

Route::middleware('auth')->group(function () {
    Route::put('/password', [PasswordController::class, 'update'])
        ->name('password.update');
});
