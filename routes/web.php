<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login'); // ⬅ login como pantalla principal
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth','verified'])
    ->name('dashboard');


/**
 * CARGA AUTOMÁTICA DE RUTAS EN /resource
 */
if (! function_exists('require_route_dir')) {
    function require_route_dir(string $path): void
    {
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') continue;

            $full = $path . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($full)) {
                require_route_dir($full);
            } elseif (is_file($full) && pathinfo($full, PATHINFO_EXTENSION) === 'php') {
                require $full;
            }
        }
    }
}

require_route_dir(__DIR__.'/resource');

// Rutas de autenticación Breeze
require __DIR__.'/auth.php';
