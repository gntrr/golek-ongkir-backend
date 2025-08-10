<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RajaOngkirController;

Route::middleware('throttle:60,1')->prefix('')->group(function () {
    // Step-by-step
    Route::get('/provinces',        [RajaOngkirController::class, 'provinces']);
    Route::get('/cities',           [RajaOngkirController::class, 'cities']);     // ?province=ID
    Route::get('/districts',        [RajaOngkirController::class, 'districts']);  // ?city=ID

    // Direct search
    Route::get('/search',     [RajaOngkirController::class, 'search']);     // ?search=... / ?keyword=... / ?q=...

    // Calculate
    Route::post('/cost',            [RajaOngkirController::class, 'cost']);       // origin,destination,weight,couriers

    // Tracking (opsional)
    Route::post('/track',           [RajaOngkirController::class, 'track']);
});
