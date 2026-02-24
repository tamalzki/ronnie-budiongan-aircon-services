<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\InstallmentPayment;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // ── Sales Metrics ──────────────────────────────────────────────
        $todaySales = Sale::whereDate('sale_date', today())->sum('total');
        $monthSales = Sale::whereMonth('sale_date', now()->month)
                         ->whereYear('sale_date', now()->year)
                         ->sum('total');
        $totalSales = Sale::sum('total');

        // ── Inventory Metrics (now from product_serials) ───────────────
        $totalStock         = ProductSerial::where('status', 'in_stock')->count();
        $totalStockValue    = ProductSerial::where('status', 'in_stock')
                                ->join('products', 'products.id', '=', 'product_serials.product_id')
                                ->sum('products.cost');

        // Low stock = products with 5 or fewer in_stock serials
        $lowStockProducts   = Product::whereHas('serials', fn($q) => $q->where('status', 'in_stock'))
                                ->withCount(['serials as in_stock_count' => fn($q) => $q->where('status', 'in_stock')])
                                ->having('in_stock_count', '<=', 5)
                                ->count();

        // Out of stock = products with no in_stock serials at all
        $outOfStockProducts = Product::where('is_active', true)
                                ->whereDoesntHave('serials', fn($q) => $q->where('status', 'in_stock'))
                                ->count();

        // Low stock product list for dashboard widget
        $lowStockProductsList = Product::with('brand')
                                ->where('is_active', true)
                                ->withCount(['serials as in_stock_count' => fn($q) => $q->where('status', 'in_stock')])
                                ->having('in_stock_count', '<=', 5)
                                ->orderBy('in_stock_count')
                                ->take(10)
                                ->get();

        // ── Customer Installments ──────────────────────────────────────
        $installmentsDueThisMonth = InstallmentPayment::whereMonth('due_date', now()->month)
                                        ->whereYear('due_date', now()->year)
                                        ->whereIn('status', ['unpaid', 'partial'])
                                        ->count();

        $installmentsAmountDueThisMonth = InstallmentPayment::whereMonth('due_date', now()->month)
                                            ->whereYear('due_date', now()->year)
                                            ->whereIn('status', ['unpaid', 'partial'])
                                            ->sum('amount');

        $overdueInstallments = InstallmentPayment::where('due_date', '<', now())
                                    ->whereIn('status', ['unpaid', 'partial'])
                                    ->count();

        // ── Supplier Payments Due ──────────────────────────────────────
        $supplierPaymentsDue      = 0;
        $supplierPaymentsDueCount = 0;

        try {
            $supplierPaymentsDue = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
                                        ->where('payment_type', '45days')
                                        ->sum('balance');
            $supplierPaymentsDueCount = PurchaseOrder::whereIn('payment_status', ['unpaid', 'partial'])
                                            ->where('payment_type', '45days')
                                            ->count();
        } catch (\Exception $e) {
            $supplierPaymentsDue      = 0;
            $supplierPaymentsDueCount = 0;
        }

        // ── Installments to Collect This Month ────────────────────────
        $installmentsToCollectThisMonth = collect();
        try {
            $installmentsToCollectThisMonth = Sale::where('payment_type', 'installment')
                ->whereHas('installmentPayments', function ($q) {
                    $q->whereMonth('due_date', now()->month)
                      ->whereYear('due_date', now()->year)
                      ->whereIn('status', ['unpaid', 'partial']);
                })
                ->with(['installmentPayments' => function ($q) {
                    $q->whereMonth('due_date', now()->month)
                      ->whereYear('due_date', now()->year)
                      ->whereIn('status', ['unpaid', 'partial']);
                }])
                ->get();
        } catch (\Exception $e) {
            $installmentsToCollectThisMonth = collect();
        }

        // ── Recent Sales ───────────────────────────────────────────────
        $recentSales = Sale::with('user')
                          ->orderBy('created_at', 'desc')
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