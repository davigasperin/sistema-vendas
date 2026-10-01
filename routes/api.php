<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:api', 'auth:sanctum'])->group(function () {
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/customers/search', [CustomerController::class, 'search']);
});

Route::middleware(['throttle:api'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});
