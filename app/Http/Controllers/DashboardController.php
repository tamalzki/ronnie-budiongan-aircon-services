<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\InstallmentPayment;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Sales Metrics
        $todaySales = Sale::whereDate('sale_date', today())->sum('total');
        $monthSales = Sale::whereMonth('sale_date', now()->month)
                         ->whereYear('sale_date', now()->year)
                         ->sum('total');
        $totalSales = Sale::sum('total');
        
        // Inventory Metrics
        $totalStock = Product::sum('stock_quantity');
        $totalStockValue = 0; // Will be enabled after cost migration
        $lowStockProducts = Product::where('stock_quantity', '<=', 5)->count();
        $outOfStockProducts = Product::where('stock_quantity', 0)->count();
        
        // Customer Installments Due This Month
        $installmentsDueThisMonth = InstallmentPayment::whereMonth('due_date', now()->month)
                                                      ->whereYear('due_date', now()->year)
                                                      ->whereIn('status', ['unpaid', 'partial'])
                                                      ->count();
        
        $installmentsAmountDueThisMonth = InstallmentPayment::whereMonth('due_date', now()->month)
                                                             ->whereYear('due_date', now()->year)
                                                             ->whereIn('status', ['unpaid', 'partial'])
                                                             ->sum('amount');
        
        // Overdue Installments
        $overdueInstallments = InstallmentPayment::where('due_date', '<', now())
                                                  ->whereIn('status', ['unpaid', 'partial'])
                                                  ->count();
        
        // Supplier Payments Due (with safety check)
        $supplierPaymentsDue = 0;
        $supplierPaymentsDueCount = 0;
        
        if (class_exists(\App\Models\PurchaseOrder::class)) {
            try {
                $supplierPaymentsDue = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
                                                    ->where('payment_type', 'installment')
                                                    ->sum('balance');
                $supplierPaymentsDueCount = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
                                                          ->where('payment_type', 'installment')
                                                          ->count();
            } catch (\Exception $e) {
                $supplierPaymentsDue = 0;
                $supplierPaymentsDueCount = 0;
            }
        }
        
        // Installments to Collect This Month
        $installmentsToCollectThisMonth = collect();
        try {
            $installmentsToCollectThisMonth = Sale::where('payment_type', 'installment')
                                                   ->whereHas('installmentPayments', function($q) {
                                                       $q->whereMonth('due_date', now()->month)
                                                         ->whereYear('due_date', now()->year)
                                                         ->whereIn('status', ['unpaid', 'partial']);
                                                   })
                                                   ->with(['installmentPayments' => function($q) {
                                                       $q->whereMonth('due_date', now()->month)
                                                         ->whereYear('due_date', now()->year)
                                                         ->whereIn('status', ['unpaid', 'partial']);
                                                   }])
                                                   ->get();
        } catch (\Exception $e) {
            $installmentsToCollectThisMonth = collect();
        }
        
        // Recent Sales
        $recentSales = Sale::with('user')
                          ->orderBy('created_at', 'desc')
                          ->take(10)
                          ->get();
        
        // Low Stock Products
        $lowStockProductsList = Product::where('stock_quantity', '<=', 5)
                                       ->with('brand')
                                       ->orderBy('stock_quantity')
                                       ->take(10)
                                       ->get();

        return view('dashboard', compact(
            'todaySales',
            'monthSales',
            'totalSales',
            'totalStock',
            'totalStockValue',
            'lowStockProducts',
            'outOfStockProducts',
            'installmentsDueThisMonth',
            'installmentsAmountDueThisMonth',
            'overdueInstallments',
            'supplierPaymentsDue',
            'supplierPaymentsDueCount',
            'installmentsToCollectThisMonth',
            'recentSales',
            'lowStockProductsList'
        ));
    }
}