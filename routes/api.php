<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RajaOngkirController;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/provinces', [RajaOngkirController::class, 'provinces']);
    Route::get('/cities', [RajaOngkirController::class, 'cities']); // ?province=&q=
    Route::post('/cost', [RajaOngkirController::class, 'cost']);
});
