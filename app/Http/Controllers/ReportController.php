<?php

namespace App\Http\Controllers;

use App\Models\OperationExpense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\InstallmentPayment;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public const REPORT_KEYS = ['overview', 'installments', 'purchases', 'customers', 'inventory', 'expenses'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Sale::class);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate   = $request->input('end_date', now()->format('Y-m-d'));

        $currentReport = $request->query('report');
        if ($currentReport !== null && ! in_array($currentReport, self::REPORT_KEYS, true)) {
            $currentReport = null;
        }

        // ── Sales summary + collection (single aggregate query) ─────
        $saleStats = DB::table('sales')
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total), 0) as total_sales')
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'cash' THEN total ELSE 0 END), 0) as total_cash_sales")
            ->selectRaw("COALESCE(SUM(CASE WHEN payment_type = 'installment' THEN total ELSE 0 END), 0) as total_installment_sales")
            ->selectRaw('COUNT(*) as sales_count')
            ->selectRaw('COALESCE(SUM(paid_amount), 0) as total_collected')
            ->selectRaw('COALESCE(SUM(balance), 0) as total_pending')
            ->first();

        $totalSales            = (float) ($saleStats->total_sales ?? 0);
        $totalCashSales        = (float) ($saleStats->total_cash_sales ?? 0);
        $totalInstallmentSales = (float) ($saleStats->total_installment_sales ?? 0);
        $salesCount            = (int) ($saleStats->sales_count ?? 0);
        $averageSaleAmount     = $salesCount > 0 ? $totalSales / $salesCount : 0;
        $totalCollected        = (float) ($saleStats->total_collected ?? 0);
        $totalPending          = (float) ($saleStats->total_pending ?? 0);

        // ── Sales by Date (chart) ───────────────────────────────────
        $salesByDate = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->select(DB::raw('DATE(sale_date) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // ── Top Products ────────────────────────────────────────────
        $topProducts = Product::withCount([
                'saleItems as sale_items_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereHas('sale', fn($s) => $s->whereBetween('sale_date', [$startDate, $endDate]));
                },
                'serials as in_stock_count' => fn($q) => $q->where('status', 'in_stock'),
            ])
            ->with('brand')
            ->having('sale_items_count', '>', 0)
            ->orderBy('sale_items_count', 'desc')
            ->take(10)
            ->get();

        // ── Installments summary for period (single aggregate query) ─
        $installPeriod = DB::table('installment_payments')
            ->whereBetween('due_date', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(amount), 0) as total_installment_amount')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN amount_paid ELSE 0 END), 0) as paid_installments")
            ->first();

        $totalInstallmentAmount = (float) ($installPeriod->total_installment_amount ?? 0);
        $paidInstallments       = (float) ($installPeriod->paid_installments ?? 0);

        $pendingInstallments = InstallmentPayment::whereBetween('due_date', [$startDate, $endDate])
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('sale')
            ->get();

        $overdueInstallments = InstallmentPayment::where('due_date', '<', now())
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('sale')
            ->orderBy('due_date')
            ->get();

        // Due this month (current calendar month, not yet paid)
        $dueThisMonth = InstallmentPayment::whereYear('due_date', now()->year)
            ->whereMonth('due_date', now()->month)
            ->whereIn('status', ['unpaid', 'partial'])
            ->with('sale')
            ->orderBy('due_date')
            ->get();

        // ── Purchase orders summary (single aggregate query) ────────
        $poStats = DB::table('purchase_orders')
            ->whereBetween('order_date', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(total), 0) as total_purchases')
            ->selectRaw('COALESCE(SUM(amount_paid), 0) as total_purchases_paid')
            ->selectRaw('COALESCE(SUM(balance), 0) as total_purchases_pending')
            ->selectRaw('COUNT(*) as purchase_orders_count')
            ->first();

        $totalPurchases        = (float) ($poStats->total_purchases ?? 0);
        $totalPurchasesPaid    = (float) ($poStats->total_purchases_paid ?? 0);
        $totalPurchasesPending = (float) ($poStats->total_purchases_pending ?? 0);
        $purchaseOrdersCount   = (int) ($poStats->purchase_orders_count ?? 0);

        $purchaseOrdersSummary = PurchaseOrder::whereBetween('order_date', [$startDate, $endDate])
            ->with('supplier')
            ->orderBy('order_date', 'desc')
            ->get();

        // ── Profit ──────────────────────────────────────────────────
        $profitMargin     = $totalSales - $totalPurchases;
        $profitPercentage = $totalSales > 0 ? (($profitMargin / $totalSales) * 100) : 0;

        // ── Operating expenses (same date range as sales / PO) ──────
        $expenseAgg = DB::table('operation_expenses')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->selectRaw('COALESCE(SUM(amount), 0) as total')
            ->first();
        $totalOperatingExpenses = (float) ($expenseAgg->total ?? 0);

        $expensesByCategory = DB::table('operation_expenses')
            ->join('expense_categories', 'operation_expenses.expense_category_id', '=', 'expense_categories.id')
            ->whereBetween('operation_expenses.expense_date', [$startDate, $endDate])
            ->groupBy('expense_categories.id', 'expense_categories.name')
            ->orderByDesc(DB::raw('SUM(operation_expenses.amount)'))
            ->select('expense_categories.name as category_name')
            ->selectRaw('SUM(operation_expenses.amount) as total')
            ->get();

        $operationExpensesList = OperationExpense::query()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->with(['category', 'user'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        // ── Inventory Snapshot (always current, not date-filtered) ──
        $inventorySnapshot = Product::with('brand')
            ->where('is_active', true)
            ->withCount([
                'serials as in_stock_count'  => fn($q) => $q->where('status', 'in_stock'),
                'serials as sold_count'      => fn($q) => $q->where('status', 'sold'),
                'serials as pending_count'   => fn($q) => $q->where('status', 'pending'),
            ])
            ->orderByRaw('COALESCE(brand_id, 0)')
            ->orderBy('model')
            ->get();

        $totalStockValue = $inventorySnapshot->sum(fn($p) => $p->in_stock_count * (float) $p->cost);
        $totalStockUnits = $inventorySnapshot->sum('in_stock_count');

        // ── Top Customers ───────────────────────────────────────────
        $topCustomers = Sale::whereBetween('sale_date', [$startDate, $endDate])
            ->select('customer_name', DB::raw('COUNT(*) as purchase_count'), DB::raw('SUM(total) as total_spent'))
            ->groupBy('customer_name')
            ->orderBy('total_spent', 'desc')
            ->take(10)
            ->get();

        return view('reports.index', compact(
            'startDate', 'endDate', 'currentReport',
            'totalSales', 'totalCashSales', 'totalInstallmentSales',
            'salesCount', 'averageSaleAmount',
            'totalCollected', 'totalPending',
            'salesByDate',
            'topProducts',
            'totalInstallmentAmount', 'paidInstallments',
            'pendingInstallments', 'overdueInstallments', 'dueThisMonth',
            'totalPurchases', 'totalPurchasesPaid', 'totalPurchasesPending',
            'purchaseOrdersCount', 'purchaseOrdersSummary',
            'profitMargin', 'profitPercentage',
            'totalOperatingExpenses',
            'expensesByCategory', 'operationExpensesList',
            'topCustomers',
            'inventorySnapshot', 'totalStockValue', 'totalStockUnits'
        ));
    }
}