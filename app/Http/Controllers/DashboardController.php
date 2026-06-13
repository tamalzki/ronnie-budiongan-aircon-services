<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Sale::class);

        $now = now();

        // ── Sales metrics (single aggregate query) ───────────────────
        $salesAgg = DB::table('sales')
            ->selectRaw('SUM(CASE WHEN DATE(sale_date) = ? THEN total ELSE 0 END) as today_sales', [$now->toDateString()])
            ->selectRaw('SUM(CASE WHEN YEAR(sale_date) = ? AND MONTH(sale_date) = ? THEN total ELSE 0 END) as month_sales', [$now->year, $now->month])
            ->selectRaw('COALESCE(SUM(total), 0) as total_sales')
            ->first();

        $todaySales = (float) ($salesAgg->today_sales ?? 0);
        $monthSales = (float) ($salesAgg->month_sales ?? 0);
        $totalSales = (float) ($salesAgg->total_sales ?? 0);

        // ── Inventory: count + cost value (single query) ─────────────
        $stockAgg = DB::table('product_serials')
            ->join('products', 'products.id', '=', 'product_serials.product_id')
            ->where('product_serials.status', 'in_stock')
            ->selectRaw('COUNT(*) as total_stock')
            ->selectRaw('COALESCE(SUM(products.cost), 0) as total_stock_value')
            ->first();

        $totalStock      = (int) ($stockAgg->total_stock ?? 0);
        $totalStockValue = (float) ($stockAgg->total_stock_value ?? 0);

        // Low stock = products with 5 or fewer in_stock serials (has at least one in_stock)
        $lowStockProducts   = Product::whereHas('serials', fn($q) => $q->where('status', 'in_stock'))
            ->withCount(['serials as in_stock_count' => fn($q) => $q->where('status', 'in_stock')])
            ->having('in_stock_count', '<=', 5)
            ->count();

        // Out of stock = active products with no in_stock serials
        $outOfStockProducts = Product::where('is_active', true)
            ->whereDoesntHave('serials', fn($q) => $q->where('status', 'in_stock'))
            ->count();

        // Low stock product list for dashboard widget
        $lowStockProductsList = Product::query()
            ->with('brand')
            ->where('is_active', true)
            ->withCount(['serials as in_stock_count' => fn($q) => $q->where('status', 'in_stock')])
            ->having('in_stock_count', '<=', 5)
            ->orderBy('in_stock_count')
            ->take(10)
            ->get();

        // ── Installments: overdue + due this month (single query) ────
        $installAgg = DB::table('installment_payments')
            ->selectRaw(
                "COUNT(CASE WHEN due_date < ? AND status IN ('unpaid','partial') THEN 1 END) as overdue_count",
                [$now]
            )
            ->selectRaw(
                "COUNT(CASE WHEN YEAR(due_date) = ? AND MONTH(due_date) = ? AND status IN ('unpaid','partial') THEN 1 END) as due_month_count",
                [$now->year, $now->month]
            )
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN YEAR(due_date) = ? AND MONTH(due_date) = ? AND status IN ('unpaid','partial') THEN amount ELSE 0 END), 0) as due_month_amount",
                [$now->year, $now->month]
            )
            ->first();

        $installmentsDueThisMonth       = (int) ($installAgg->due_month_count ?? 0);
        $installmentsAmountDueThisMonth = (float) ($installAgg->due_month_amount ?? 0);
        $overdueInstallments            = (int) ($installAgg->overdue_count ?? 0);

        // ── Supplier payments due (single query) ─────────────────────
        $poDue = DB::table('purchase_orders')
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->where('payment_type', '45days')
            ->selectRaw('COALESCE(SUM(balance), 0) as balance_sum')
            ->selectRaw('COUNT(*) as po_count')
            ->first();
        $supplierPaymentsDue      = (float) ($poDue->balance_sum ?? 0);
        $supplierPaymentsDueCount = (int) ($poDue->po_count ?? 0);

        // ── Installments to Collect This Month ────────────────────────
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

        // ── Recent Sales ───────────────────────────────────────────────
        $recentSales = Sale::with('user')
                          ->orderBy('created_at', 'desc')
                          ->take(10)
                          ->get();

        // ── Daily Customers: unpaid follow-ups ────────────────────────
        $dcUnpaidAgg = DB::table('daily_customers')
            ->where('status', 'unpaid')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as amt')
            ->first();

        $unpaidDailyCustomersCount  = (int) ($dcUnpaidAgg->cnt ?? 0);
        $unpaidDailyCustomersAmount = (float) ($dcUnpaidAgg->amt ?? 0);

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
            'lowStockProductsList',
            'unpaidDailyCustomersCount',
            'unpaidDailyCustomersAmount'
        ));
    }
}