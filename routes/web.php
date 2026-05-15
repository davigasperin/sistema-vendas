<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect()->route('dashboard'))->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('customers', CustomerController::class)->middleware('can:viewAny,App\Models\Customer');
    Route::resource('products', ProductController::class)->middleware('can:viewAny,App\Models\Product');
    Route::patch('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock')->middleware('can:adjustStock,product');
    Route::patch('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active')->middleware('can:toggleActive,product');

    Route::resource('sales', SaleController::class)->middleware('can:viewAny,App\Models\Sale');
    Route::get('sales/{sale}/pdf', [SaleController::class, 'downloadPdf'])->name('sales.pdf')->middleware('can:downloadPdf,sale');
    Route::post('sales/{id}/restore', [SaleController::class, 'restore'])->name('sales.restore')->middleware('can:restore,sale');

    Route::resource('expenses', ExpenseController::class)->middleware('can:viewAny,App\Models\Expense');
    Route::patch('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.markPaid')->middleware('can:markPaid,expense');
    Route::get('expenses/report', [ExpenseController::class, 'report'])->name('expenses.report')->middleware('can:report,App\Models\Expense');

    Route::get('/api/products/search', [ProductController::class, 'searchApi']);
    Route::get('/api/customers/search', [CustomerController::class, 'searchApi']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

require __DIR__.'/auth.php';