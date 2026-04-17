<?php

use Illuminate\Support\Facades\Route;

// Health check
Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
});

// All other routes are handled by Nginx serving static HTML files.
// The catch-all below is only reached if Nginx passes unknown paths to PHP.
Route::get('/{any?}', function () {
    $path = public_path('index.html');
    if (file_exists($path)) {
        return response()->file($path);
    }
    return response('Not Found', 404);
})->where('any', '.*');
