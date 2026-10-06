<?php

use App\Http\Controllers\CashShiftController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SaleController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'))->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('customers', CustomerController::class);

    Route::resource('products', ProductController::class)->except(['show']);
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show')->middleware('can:view,product');
    Route::patch('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock')->middleware('can:adjustStock,product');
    Route::patch('products/{product}/toggle-active', [ProductController::class, 'toggleActive'])->name('products.toggle-active')->middleware('can:toggleActive,product');

    Route::get('expenses/report', [ExpenseController::class, 'report'])->name('expenses.report')->middleware('can:report,App\Models\Expense');
    Route::patch('expenses/{expense}/mark-paid', [ExpenseController::class, 'markPaid'])->name('expenses.markPaid')->middleware('can:markPaid,expense');
    Route::post('expense-categories', [ExpenseCategoryController::class, 'store'])->name('expense-categories.store');
    Route::resource('expenses', ExpenseController::class);

    Route::get('cashier', [CashShiftController::class, 'index'])->name('cashier.index');
    Route::post('cashier/open', [CashShiftController::class, 'store'])->name('cashier.open');
    Route::post('cashier/movement', [CashShiftController::class, 'movement'])->name('cashier.movement');
    Route::post('cashier/close', [CashShiftController::class, 'close'])->name('cashier.close');
    Route::get('cashier/{cashShift}', [CashShiftController::class, 'show'])->name('cashier.show');

    Route::get('sales/report', [SaleController::class, 'report'])->name('sales.report')->middleware('can:report,App\Models\Sale');
    Route::get('sales/{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt')->middleware('can:view,sale');
    Route::get('sales/{sale}/pdf', [SaleController::class, 'downloadPdf'])->name('sales.pdf')->middleware('can:downloadPdf,sale');
    Route::post('sales/{sale}/restore', [SaleController::class, 'restore'])->withTrashed()->name('sales.restore')->middleware('can:restore,sale');
    Route::resource('sales', SaleController::class)->withTrashed(['restore', 'show']);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');

require __DIR__.'/auth.php';
