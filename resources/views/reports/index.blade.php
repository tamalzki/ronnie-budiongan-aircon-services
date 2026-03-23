@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-bar-chart-line text-primary"></i> Reports</h2>
            <p class="text-muted mb-0">Business overview & analytics</p>
        </div>
    </div>

    {{-- Date Filter --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <form action="{{ route('reports.index') }}" method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small fw-semibold mb-1">From</label>
                    <input type="date" class="form-control form-control-sm" name="start_date" value="{{ $startDate }}">
                </div>
                <div class="col-auto">
                    <label class="form-label small fw-semibold mb-1">To</label>
                    <input type="date" class="form-control form-control-sm" name="end_date" value="{{ $endDate }}">
                </div>
                {{-- Quick date shortcuts --}}
                <div class="col-auto d-flex gap-1 align-items-end">
                    <a href="{{ route('reports.index', ['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
                       class="btn btn-outline-secondary btn-sm">This Month</a>
                    <a href="{{ route('reports.index', ['start_date' => now()->subMonth()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->subMonth()->endOfMonth()->format('Y-m-d')]) }}"
                       class="btn btn-outline-secondary btn-sm">Last Month</a>
                    <a href="{{ route('reports.index', ['start_date' => now()->startOfYear()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}"
                       class="btn btn-outline-secondary btn-sm">This Year</a>
                </div>
                <div class="col-auto d-flex gap-1 align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Apply</button>
                    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i> Reset</a>
                </div>
                <div class="col-auto ms-auto align-items-end d-flex">
                    <span class="badge bg-light text-muted border" style="font-size:0.8rem;padding:6px 10px;">
                        <i class="bi bi-calendar3"></i>
                        {{ \Carbon\Carbon::parse($startDate)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('M d, Y') }}
                    </span>
                </div>
            </form>
        </div>
    </div>

    {{-- KPI Summary Strip --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2"><i class="bi bi-bag-check fs-4 text-primary"></i></div>
                    <div>
                        <div class="text-muted small">Total Sales</div>
                        <div class="fw-bold">₱{{ number_format($totalSales, 2) }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">{{ $salesCount }} orders</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 rounded p-2"><i class="bi bi-cash-stack fs-4 text-success"></i></div>
                    <div>
                        <div class="text-muted small">Collected</div>
                        <div class="fw-bold text-success">₱{{ number_format($totalCollected, 2) }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">Actual cash in</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-danger bg-opacity-10 rounded p-2"><i class="bi bi-hourglass-split fs-4 text-danger"></i></div>
                    <div>
                        <div class="text-muted small">Receivables</div>
                        <div class="fw-bold text-danger">₱{{ number_format($totalPending, 2) }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">Still to collect</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="{{ $profitMargin >= 0 ? 'bg-success' : 'bg-danger' }} bg-opacity-10 rounded p-2">
                        <i class="bi bi-graph-up-arrow fs-4 {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Gross Profit</div>
                        <div class="fw-bold {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">₱{{ number_format($profitMargin, 2) }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">{{ number_format($profitPercentage, 1) }}% margin</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3" id="reportTabs" role="tablist" style="font-size:0.9rem;">
        <li class="nav-item">
            <button class="nav-link active px-4" data-bs-toggle="tab" data-bs-target="#tab-overview">
                <i class="bi bi-speedometer2"></i> Overview
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4" data-bs-toggle="tab" data-bs-target="#tab-installments">
                <i class="bi bi-calendar-check"></i> Installments
                @if($overdueInstallments->count() > 0)
                    <span class="badge bg-danger ms-1">{{ $overdueInstallments->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4" data-bs-toggle="tab" data-bs-target="#tab-purchases">
                <i class="bi bi-cart-plus"></i> Purchases
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4" data-bs-toggle="tab" data-bs-target="#tab-customers">
                <i class="bi bi-people"></i> Customers
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4" data-bs-toggle="tab" data-bs-target="#tab-inventory">
                <i class="bi bi-boxes"></i> Inventory
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm p-4 mb-4" id="reportTabContent">

        {{-- ═══════════════════════════════
             TAB 1: OVERVIEW
        ════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="tab-overview">

            {{-- Sales Breakdown --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-3"><i class="bi bi-pie-chart"></i> Sales Breakdown</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Cash Sales</span>
                                <span class="fw-semibold text-success">₱{{ number_format($totalCashSales, 2) }}</span>
                            </div>
                            <div class="progress mb-3" style="height:6px;">
                                <div class="progress-bar bg-success" style="width:{{ $totalSales > 0 ? ($totalCashSales/$totalSales)*100 : 0 }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Installment Sales</span>
                                <span class="fw-semibold text-warning">₱{{ number_format($totalInstallmentSales, 2) }}</span>
                            </div>
                            <div class="progress mb-3" style="height:6px;">
                                <div class="progress-bar bg-warning" style="width:{{ $totalSales > 0 ? ($totalInstallmentSales/$totalSales)*100 : 0 }}%"></div>
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Avg. Sale Value</span>
                                <span class="fw-semibold">₱{{ number_format($averageSaleAmount, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="card border-0 bg-light h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-3"><i class="bi bi-graph-up"></i> Daily Sales Trend</h6>
                            <canvas id="salesChart" height="100"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Products --}}
            <h6 class="fw-semibold mb-2"><i class="bi bi-trophy text-warning"></i> Top 10 Products</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">#</th>
                            <th class="border-0 px-3 py-2">Product</th>
                            <th class="border-0 px-3 py-2">Brand</th>
                            <th class="border-0 px-3 py-2 text-center">Units Sold</th>
                            <th class="border-0 px-3 py-2 text-center">Stock Left</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topProducts as $i => $product)
                        <tr>
                            <td class="px-3 py-2 text-muted">{{ $i + 1 }}</td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                {{ $product->display_model }}
                            </td>
                            <td class="px-3 py-2">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                    {{ $product->brand->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="badge bg-primary">{{ $product->sale_items_count }}</span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="badge {{ $product->in_stock_count == 0 ? 'bg-danger' : ($product->in_stock_count <= 5 ? 'bg-warning text-dark' : 'bg-success') }}">
                                    {{ $product->in_stock_count }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No sales in this period</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════════════════════════
             TAB 2: INSTALLMENTS
        ════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-installments">

            {{-- Mini KPIs --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Total Due (Period)</div>
                        <div class="fw-bold fs-5">₱{{ number_format($totalInstallmentAmount, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Collected</div>
                        <div class="fw-bold fs-5 text-success">₱{{ number_format($paidInstallments, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Pending</div>
                        <div class="fw-bold fs-5 text-warning">{{ $pendingInstallments->count() }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-danger bg-opacity-10 text-center py-3">
                        <div class="text-muted small mb-1">Overdue</div>
                        <div class="fw-bold fs-5 text-danger">{{ $overdueInstallments->count() }}</div>
                    </div>
                </div>
            </div>

            {{-- Due This Month --}}
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-bell-fill text-warning"></i> Due This Month</h6>
                <span class="badge bg-warning text-dark">{{ $dueThisMonth->count() }} customer(s)</span>
            </div>
            <div class="table-responsive mb-4">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Customer</th>
                            <th class="border-0 px-3 py-2">Invoice</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Due Date</th>
                            <th class="border-0 px-3 py-2">Amount Due</th>
                            <th class="border-0 px-3 py-2">Status</th>
                            <th class="border-0 px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dueThisMonth as $payment)
                        @php $daysLeft = now()->diffInDays($payment->due_date, false); @endphp
                        <tr class="{{ $daysLeft < 0 ? 'table-danger' : 'table-warning' }}">
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                {{ $payment->sale->customer_name }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('sales.show', $payment->sale) }}" class="text-decoration-none text-primary fw-semibold">
                                    {{ $payment->sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                {{ $payment->due_date->format('M d, Y') }}
                                <br>
                                @if($daysLeft < 0)
                                    <small class="text-danger fw-bold"><i class="bi bi-alarm"></i> Overdue {{ abs((int)$daysLeft) }}d</small>
                                @elseif($daysLeft == 0)
                                    <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Due Today</small>
                                @else
                                    <small class="text-warning fw-bold"><i class="bi bi-clock"></i> {{ (int)$daysLeft }}d left</small>
                                @endif
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                ₱{{ number_format($payment->amount - $payment->amount_paid, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($payment->status == 'partial')
                                    <span class="badge bg-info text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('installments.show', $payment->sale_id) }}"
                                   class="btn btn-warning"
                                   style="padding:2px 8px;font-size:0.78rem">
                                    <i class="bi bi-calendar-check"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                                No installments due this month
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Overdue --}}
            @if($overdueInstallments->count() > 0)
            <div class="d-flex align-items-center gap-2 mb-2">
                <h6 class="fw-semibold mb-0"><i class="bi bi-exclamation-octagon-fill text-danger"></i> Overdue Installments</h6>
                <span class="badge bg-danger">{{ $overdueInstallments->count() }}</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm table-danger mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Customer</th>
                            <th class="border-0 px-3 py-2">Invoice</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Was Due</th>
                            <th class="border-0 px-3 py-2">Balance</th>
                            <th class="border-0 px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($overdueInstallments as $payment)
                        <tr>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $payment->sale->customer_name }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('sales.show', $payment->sale) }}" class="text-danger fw-semibold text-decoration-none">
                                    {{ $payment->sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                {{ $payment->due_date->format('M d, Y') }}
                                <br><small class="text-danger fw-bold">{{ abs(now()->diffInDays($payment->due_date)) }}d ago</small>
                            </td>
                            <td class="px-3 py-2 fw-semibold text-danger" style="white-space:nowrap">
                                ₱{{ number_format($payment->amount - $payment->amount_paid, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('installments.show', $payment->sale_id) }}"
                                   class="btn btn-danger"
                                   style="padding:2px 8px;font-size:0.78rem">
                                    <i class="bi bi-cash"></i> Collect
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>

        {{-- ═══════════════════════════════
             TAB 3: PURCHASES
        ════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-purchases">

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Total Purchased</div>
                        <div class="fw-bold fs-5">₱{{ number_format($totalPurchases, 2) }}</div>
                        <div class="text-muted" style="font-size:0.72rem;">{{ $purchaseOrdersCount }} orders</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Paid to Suppliers</div>
                        <div class="fw-bold fs-5 text-success">₱{{ number_format($totalPurchasesPaid, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Outstanding</div>
                        <div class="fw-bold fs-5 text-danger">₱{{ number_format($totalPurchasesPending, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Sales vs Purchases</div>
                        <div class="fw-bold fs-5 {{ $profitMargin >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ $profitMargin >= 0 ? '+' : '' }}₱{{ number_format($profitMargin, 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-semibold mb-2"><i class="bi bi-cart-plus text-primary"></i> Purchase Orders (Period)</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Supplier</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Order Date</th>
                            <th class="border-0 px-3 py-2">Total</th>
                            <th class="border-0 px-3 py-2">Paid</th>
                            <th class="border-0 px-3 py-2">Balance</th>
                            <th class="border-0 px-3 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrdersSummary as $po)
                        <tr>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="fw-semibold">{{ $po->supplier->name }}</div>
                                <small class="text-muted">
                                    <a href="{{ route('purchase-orders.show', $po) }}" class="text-muted text-decoration-none">
                                        {{ $po->po_number }}
                                    </a>
                                </small>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">₱{{ number_format($po->total, 2) }}</td>
                            <td class="px-3 py-2 text-success" style="white-space:nowrap">₱{{ number_format($po->amount_paid, 2) }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($po->balance > 0)
                                    <span class="text-danger fw-semibold">₱{{ number_format($po->balance, 2) }}</span>
                                @else
                                    <span class="text-success">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($po->payment_status == 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($po->payment_status == 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No purchase orders in this period</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════════════════════════
             TAB 4: CUSTOMERS
        ════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-customers">

            <h6 class="fw-semibold mb-2"><i class="bi bi-trophy text-warning"></i> Top 10 Customers by Spending</h6>
            <div class="table-responsive mb-4">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">#</th>
                            <th class="border-0 px-3 py-2">Customer</th>
                            <th class="border-0 px-3 py-2 text-center">Orders</th>
                            <th class="border-0 px-3 py-2">Total Spent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topCustomers as $i => $customer)
                        <tr>
                            <td class="px-3 py-2 text-muted">
                                @if($i == 0) 🥇 @elseif($i == 1) 🥈 @elseif($i == 2) 🥉 @else {{ $i + 1 }} @endif
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $customer->customer_name }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="badge bg-info text-dark">{{ $customer->purchase_count }}</span>
                            </td>
                            <td class="px-3 py-2 fw-semibold text-success" style="white-space:nowrap">
                                ₱{{ number_format($customer->total_spent, 2) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">No customer data in this period</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pending Installment Customers --}}
            <h6 class="fw-semibold mb-2"><i class="bi bi-person-exclamation text-danger"></i> Customers with Pending Installments</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Customer</th>
                            <th class="border-0 px-3 py-2">Invoice</th>
                            <th class="border-0 px-3 py-2">Total</th>
                            <th class="border-0 px-3 py-2">Paid</th>
                            <th class="border-0 px-3 py-2">Balance</th>
                            <th class="border-0 px-3 py-2">Pending</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $salesWithInstallments = $pendingInstallments->groupBy('sale_id'); @endphp
                        @forelse($salesWithInstallments as $saleId => $payments)
                        @php $sale = $payments->first()->sale; @endphp
                        <tr>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $sale->customer_name }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('sales.show', $sale) }}" class="text-primary fw-semibold text-decoration-none">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">₱{{ number_format($sale->total, 2) }}</td>
                            <td class="px-3 py-2 text-success" style="white-space:nowrap">₱{{ number_format($sale->paid_amount, 2) }}</td>
                            <td class="px-3 py-2 text-danger fw-semibold" style="white-space:nowrap">₱{{ number_format($sale->balance, 2) }}</td>
                            <td class="px-3 py-2">
                                <span class="badge bg-warning text-dark">{{ $payments->count() }} payment(s)</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle text-success fs-4 d-block mb-1"></i>
                                All installments are up to date
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ═══════════════════════════════
             TAB 5: INVENTORY
        ════════════════════════════════ --}}
        <div class="tab-pane fade" id="tab-inventory">

            {{-- Summary strip --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Active Products</div>
                        <div class="fw-bold fs-5">{{ $inventorySnapshot->count() }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Units In Stock</div>
                        <div class="fw-bold fs-5 text-success">{{ number_format($totalStockUnits) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-light text-center py-3">
                        <div class="text-muted small mb-1">Stock Value (Cost)</div>
                        <div class="fw-bold fs-5 text-primary">₱{{ number_format($totalStockValue, 2) }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 bg-danger bg-opacity-10 text-center py-3">
                        <div class="text-muted small mb-1">Low / Out of Stock</div>
                        <div class="fw-bold fs-5 text-danger">
                            {{ $inventorySnapshot->filter(fn($p) => $p->in_stock_count <= 2)->count() }}
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="fw-semibold mb-2"><i class="bi bi-boxes text-primary"></i> Current Stock Levels</h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Product</th>
                            <th class="border-0 px-3 py-2">Brand</th>
                            <th class="border-0 px-3 py-2 text-center">In Stock</th>
                            <th class="border-0 px-3 py-2 text-center">Pending</th>
                            <th class="border-0 px-3 py-2 text-center">Sold</th>
                            <th class="border-0 px-3 py-2 text-end">Cost/Unit</th>
                            <th class="border-0 px-3 py-2 text-end">Stock Value</th>
                            <th class="border-0 px-3 py-2 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventorySnapshot as $product)
                        @php $stockVal = $product->in_stock_count * (float) $product->cost; @endphp
                        <tr class="{{ $product->in_stock_count == 0 ? 'table-danger' : ($product->in_stock_count <= 2 ? 'table-warning' : '') }}">
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                {{ $product->display_model }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                {{ $product->brand->name ?? '—' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="badge {{ $product->in_stock_count == 0 ? 'bg-danger' : ($product->in_stock_count <= 2 ? 'bg-warning text-dark' : 'bg-success') }}">
                                    {{ $product->in_stock_count }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($product->pending_count > 0)
                                    <span class="badge bg-info text-dark">{{ $product->pending_count }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center text-muted">{{ $product->sold_count }}</td>
                            <td class="px-3 py-2 text-end" style="white-space:nowrap">₱{{ number_format($product->cost, 2) }}</td>
                            <td class="px-3 py-2 text-end fw-semibold" style="white-space:nowrap">
                                {{ $product->in_stock_count > 0 ? '₱' . number_format($stockVal, 2) : '—' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if($product->in_stock_count == 0)
                                    <span class="badge bg-danger">Out of Stock</span>
                                @elseif($product->in_stock_count <= 2)
                                    <span class="badge bg-warning text-dark">Low Stock</span>
                                @else
                                    <span class="badge bg-success">Available</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-center py-4 text-muted">No active products found</td></tr>
                        @endforelse
                    </tbody>
                    @if($inventorySnapshot->count() > 0)
                    <tfoot class="bg-light fw-semibold">
                        <tr>
                            <td colspan="2" class="px-3 py-2">Total</td>
                            <td class="px-3 py-2 text-center">{{ $totalStockUnits }}</td>
                            <td class="px-3 py-2 text-center">{{ $inventorySnapshot->sum('pending_count') }}</td>
                            <td class="px-3 py-2 text-center">{{ $inventorySnapshot->sum('sold_count') }}</td>
                            <td></td>
                            <td class="px-3 py-2 text-end text-primary">₱{{ number_format($totalStockValue, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

    </div>{{-- end tab-content --}}

</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const salesData = @json($salesByDate);
if (salesData.length > 0) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: salesData.map(d => d.date),
            datasets: [{
                label: 'Daily Sales (₱)',
                data: salesData.map(d => d.total),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.08)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: v => '₱' + v.toLocaleString() },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>
@endpush

<style>

/* Make tab text black */
#reportTabs .nav-link {
    color: #000 !important;
    font-weight: 500;
    cursor: pointer;
}

/* Active tab style */
#reportTabs .nav-link.active {
    color: #000 !important;
    font-weight: 600;
    border-color: #dee2e6 #dee2e6 #fff;
    background-color: #fff;
}

/* Hover effect */
#reportTabs .nav-link:hover {
    background-color: #f8f9fa;
    color: #000 !important;
}

/* Restore bottom border under tabs */
#reportTabs {
    border-bottom: 1px solid #dee2e6;
}

</style>

@endsection