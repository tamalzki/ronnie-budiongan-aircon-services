<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\InstallmentPayment;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));
        $reportType = $request->input('report_type', 'all');

        // Sales Summary
        $totalSales = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('total');
        $totalCashSales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('payment_type', 'cash')->sum('total');
        $totalInstallmentSales = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->where('payment_type', 'installment')->sum('total');
        $salesCount = Sale::whereBetween('sale_date', [$startDate, $endDate])->count();
        $averageSaleAmount = $salesCount > 0 ? $totalSales / $salesCount : 0;

        // Payment Collection
        $totalCollected = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('paid_amount');
        $totalPending = Sale::whereBetween('sale_date', [$startDate, $endDate])->sum('balance');

        // Sales by Date Chart
        $salesByDate = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top Products
        $topProducts = Product::withCount(['saleItems' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('sale', function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('sale_date', [$startDate, $endDate]);
                });
            }])
            ->with('brand')
            ->orderBy('sale_items_count', 'desc')
            ->take(10)
            ->get();

        // Installments Summary
        $totalInstallmentAmount = InstallmentPayment::whereBetween('due_date', [$startDate, $endDate])->sum('amount');
        $paidInstallments = InstallmentPayment::whereBetween('due_date', [$startDate, $endDate])
            ->where('status', 'paid')->sum('amount_paid');
        $pendingInstallments = InstallmentPayment::whereBetween('due_date', [$startDate, $endDate])
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('sale')
            ->get();
        $overdueInstallments = InstallmentPayment::where('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('sale')
            ->get();

        // Purchase Orders Summary
        $totalPurchases = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('total');
        $totalPurchasesPaid = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('amount_paid');
        $totalPurchasesPending = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->sum('balance');
        $purchaseOrdersCount = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])->count();
        
        $purchaseOrdersSummary = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->with('supplier')
            ->orderBy('order_date', 'desc')
            ->get();

        // Profit Analysis (Sales Revenue - Purchase Costs)
        $profitMargin = $totalSales - $totalPurchases;
        $profitPercentage = $totalPurchases > 0 ? (($totalSales - $totalPurchases) / $totalPurchases) * 100 : 0;

        // Top Customers
        $topCustomers = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->select('customer_name', DB::raw('COUNT(*) as purchase_count'), DB::raw('SUM(total) as total_spent'))
            ->groupBy('customer_name')
            ->orderBy('total_spent', 'desc')
            ->take(10)
            ->get();

        return view('reports.index', compact(
            'startDate',
            'endDate',
            'reportType',
            'totalSales',
            'totalCashSales',
            'totalInstallmentSales',
            'salesCount',
            'averageSaleAmount',
            'totalCollected',
            'totalPending',
            'salesByDate',
            'topProducts',
            'totalInstallmentAmount',
            'paidInstallments',
            'pendingInstallments',
            'overdueInstallments',
            'totalPurchases',
            'totalPurchasesPaid',
            'totalPurchasesPending',
            'purchaseOrdersCount',
            'purchaseOrdersSummary',
            'profitMargin',
            'profitPercentage',
            'topCustomers'
        ));
    }
}