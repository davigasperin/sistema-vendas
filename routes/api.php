<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\ExpenseController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SaleController;
use App\Http\Controllers\Api\V1\SaleInstallmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API V1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->middleware(['throttle:api'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me'])->name('api.v1.me');
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');

        Route::get('/products/search', [ProductController::class, 'search'])->name('api.v1.products.search');
        Route::patch('/products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('api.v1.products.adjust-stock');
        Route::apiResource('products', ProductController::class)->names('api.v1.products');

        Route::get('/customers/search', [CustomerController::class, 'search'])->name('api.v1.customers.search');
        Route::apiResource('customers', CustomerController::class)->names('api.v1.customers');

        Route::patch('/installments/{saleInstallment}/mark-paid', [SaleInstallmentController::class, 'markPaid'])->name('api.v1.installments.mark-paid');
        Route::post('/sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('api.v1.sales.cancel');
        Route::apiResource('sales', SaleController::class)->only(['index', 'show', 'store'])->names('api.v1.sales');

        Route::patch('/expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('api.v1.expenses.mark-paid');
        Route::apiResource('expenses', ExpenseController::class)->names('api.v1.expenses');
    });
});

/*
|--------------------------------------------------------------------------
| Legacy API Routes (Backwards Compatibility)
|--------------------------------------------------------------------------
*/
Route::middleware(['throttle:api'])->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware(['throttle:api', 'auth:sanctum'])->group(function () {
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{product}', [ProductController::class, 'show']);
    Route::get('/customers/search', [CustomerController::class, 'search']);
});
