<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\InstallmentPaymentController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierPaymentController;

// Root: logged in → dashboard, guest → login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Guest-only routes — logged-in users get redirected to dashboard
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [LoginController::class, 'login']);
});

// Logout (auth users only)
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Resources
    Route::resource('brands', BrandController::class);
    Route::resource('suppliers', SupplierController::class);
    Route::resource('services', ServiceController::class);
    Route::resource('products', ProductController::class);
    Route::resource('sales', SaleController::class);

    // Installments
    Route::get('installments', [InstallmentPaymentController::class, 'index'])->name('installments.index');
    Route::get('installments/sale/{sale}', [InstallmentPaymentController::class, 'show'])->name('installments.show');
    Route::post('installments/{installment}/pay', [InstallmentPaymentController::class, 'recordPayment'])->name('installments.pay');
    Route::put('installments/{installment}/update', [InstallmentPaymentController::class, 'update'])->name('installments.update');

    // Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

    // Purchase Orders
    Route::resource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    Route::post('purchase-orders/{purchaseOrder}/payment', [PurchaseOrderController::class, 'recordPayment'])->name('purchase-orders.payment');
    Route::patch('purchase-orders/{purchaseOrder}/due-date', [PurchaseOrderController::class, 'updateDueDate'])->name('purchase-orders.update-due-date');

    // Supplier Payments
    Route::get('supplier-payments', [SupplierPaymentController::class, 'index'])->name('supplier-payments.index');

    // Inventory / Stock Management
    Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('inventory/{product}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::post('inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::post('inventory/{product}/stock-in', [InventoryController::class, 'stockIn'])->name('inventory.stock-in');
    Route::post('inventory/{product}/return', [InventoryController::class, 'returnStock'])->name('inventory.return');

    Route::post('products/{product}/set-price', [ProductController::class, 'setPrice'])->name('products.set-price');
});