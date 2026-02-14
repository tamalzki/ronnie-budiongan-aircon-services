@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">

    <!-- Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="bi bi-speedometer2 text-primary"></i> Dashboard</h2>
        <p class="text-muted mb-0">Welcome back! Here's what's happening today — {{ now()->format('F d, Y') }}</p>
    </div>

    <!-- ROW 1: Sales Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-calendar-day fs-3 text-primary"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Today's Sales</p>
                            <h4 class="mb-0 fw-bold">₱{{ number_format($todaySales, 2) }}</h4>
                            <small class="text-muted">{{ now()->format('M d, Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-calendar-month fs-3 text-success"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">This Month's Sales</p>
                            <h4 class="mb-0 fw-bold text-success">₱{{ number_format($monthSales, 2) }}</h4>
                            <small class="text-muted">{{ now()->format('F Y') }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-graph-up fs-3 text-info"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Total Sales (All Time)</p>
                            <h4 class="mb-0 fw-bold text-info">₱{{ number_format($totalSales, 2) }}</h4>
                            <small class="text-muted">All records</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-cash-coin fs-3 text-warning"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">To Collect This Month</p>
                            <h4 class="mb-0 fw-bold text-warning">₱{{ number_format($installmentsAmountDueThisMonth, 2) }}</h4>
                            <small class="text-muted">{{ $installmentsDueThisMonth }} installments due</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 2: Alerts and Inventory -->
    <div class="row g-3 mb-4">
        <!-- Overdue Installments Alert -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $overdueInstallments > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-exclamation-triangle fs-3 text-danger"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Overdue Installments</p>
                            <h4 class="mb-0 fw-bold text-danger">{{ $overdueInstallments }}</h4>
                            <small class="text-muted">
                                @if($overdueInstallments > 0)
                                    <span class="text-danger">Needs attention!</span>
                                @else
                                    All on track
                                @endif
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Supplier Payments Due -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $supplierPaymentsDueCount > 0 ? 'border-start border-warning border-4' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-truck fs-3 text-warning"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Supplier Payments Due</p>
                            <h4 class="mb-0 fw-bold text-warning">₱{{ number_format($supplierPaymentsDue, 2) }}</h4>
                            <small class="text-muted">{{ $supplierPaymentsDueCount }} orders pending</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Stock -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-box-seam fs-3 text-primary"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Total Stock in Warehouse</p>
                            <h4 class="mb-0 fw-bold">{{ number_format($totalStock) }} units</h4>
                            <small class="text-muted">Across all products</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $lowStockProducts > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                            <i class="bi bi-boxes fs-3 text-danger"></i>
                        </div>
                        <div>
                            <p class="text-muted mb-1 small">Low Stock Products</p>
                            <h4 class="mb-0 fw-bold text-danger">{{ $lowStockProducts }}</h4>
                            <small class="text-muted">{{ $outOfStockProducts }} out of stock</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 3: Tables -->
    <div class="row g-3 mb-4">
        <!-- Installments Due This Month -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-calendar-check text-warning"></i> Installments Due This Month</h5>
                    <a href="{{ route('installments.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-3 py-2 border-0">Customer</th>
                                    <th class="px-3 py-2 border-0">Invoice</th>
                                    <th class="px-3 py-2 border-0">Due Date</th>
                                    <th class="px-3 py-2 border-0">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($installmentsToCollectThisMonth as $sale)
                                    @foreach($sale->installmentPayments as $payment)
                                    <tr>
                                        <td class="px-3 py-2">{{ $sale->customer_name }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('installments.show', $sale->id) }}" class="text-decoration-none small">
                                                {{ $sale->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2">
                                            <small>{{ $payment->due_date->format('M d, Y') }}</small>
                                            @if($payment->due_date < now())
                                                <span class="badge bg-danger ms-1">Overdue</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-danger fw-bold">₱{{ number_format($payment->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                                        No installments due this month
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Low Stock Alert -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-danger"></i> Low Stock Alert</h5>
                    <a href="{{ route('inventory.index') }}" class="btn btn-sm btn-outline-primary">Manage Stock</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-3 py-2 border-0">Product</th>
                                    <th class="px-3 py-2 border-0">Brand</th>
                                    <th class="px-3 py-2 border-0">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProductsList as $product)
                                <tr>
                                    <td class="px-3 py-2">
                                        <small class="fw-semibold">{{ $product->name }}</small>
                                    </td>
                                    <td class="px-3 py-2">
                                        <small class="text-muted">{{ $product->brand->name ?? 'N/A' }}</small>
                                    </td>
                                    <td class="px-3 py-2">
                                        <span class="badge bg-{{ $product->stock_quantity == 0 ? 'danger' : 'warning' }}">
                                            {{ $product->stock_quantity }} units
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                                        All stock levels are good
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 4: Recent Sales -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Recent Sales</h5>
            <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3 border-0">Invoice #</th>
                            <th class="px-4 py-3 border-0">Customer</th>
                            <th class="px-4 py-3 border-0">Date</th>
                            <th class="px-4 py-3 border-0">Total</th>
                            <th class="px-4 py-3 border-0">Payment</th>
                            <th class="px-4 py-3 border-0">Balance</th>
                            <th class="px-4 py-3 border-0">Status</th>
                            <th class="px-4 py-3 border-0">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('sales.show', $sale) }}" class="text-decoration-none fw-bold">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $sale->customer_name }}</td>
                            <td class="px-4 py-3">{{ $sale->sale_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 fw-bold">₱{{ number_format($sale->total, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="badge bg-{{ $sale->payment_type == 'cash' ? 'success' : 'warning' }}">
                                    {{ ucfirst($sale->payment_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($sale->balance > 0)
                                    <span class="text-danger fw-bold">₱{{ number_format($sale->balance, 2) }}</span>
                                @else
                                    <span class="text-success">Paid</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : 'warning' }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No sales yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection