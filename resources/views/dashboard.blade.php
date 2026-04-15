@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">

    <x-page-header
        title="Dashboard"
        :subtitle="'Snapshot for ' . now()->format('l, F j, Y') . ' · figures use calendar month where noted'"
        icon="bi-speedometer2"
        marginClass="mb-2"
    />

    <x-flash />

    <p class="text-muted small mb-4">
        <strong>Sales</strong> shows revenue from sales. <strong>Follow-ups</strong> highlights collections, payables, and stock that may need action.
    </p>

    {{-- Sales overview --}}
    <h6 class="text-uppercase text-muted fw-bold mb-2" style="font-size:0.68rem; letter-spacing:0.06em;">Sales overview</h6>
    <div class="row g-2 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-primary bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-calendar-day fs-5 text-primary" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">Sales today</div>
                            <div class="fw-bold fs-5 text-truncate">₱{{ number_format($todaySales, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">Invoices dated today</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-success bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-calendar-month fs-5 text-success" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">Sales this month</div>
                            <div class="fw-bold fs-5 text-success text-truncate">₱{{ number_format($monthSales, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ now()->format('F Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-info bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-graph-up fs-5 text-info" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">All-time sales</div>
                            <div class="fw-bold fs-5 text-info text-truncate">₱{{ number_format($totalSales, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">Total of every sale recorded</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-warning bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-cash-coin fs-5 text-warning" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">Installments due this month</div>
                            <div class="fw-bold fs-5 text-warning text-truncate">₱{{ number_format($installmentsAmountDueThisMonth, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $installmentsDueThisMonth }} payment(s) still unpaid or partial</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Follow-ups & inventory --}}
    <h6 class="text-uppercase text-muted fw-bold mb-2" style="font-size:0.68rem; letter-spacing:0.06em;">Follow-ups &amp; inventory</h6>
    <div class="row g-2 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100 {{ $overdueInstallments > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-danger bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-exclamation-triangle fs-5 text-danger" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">Overdue installments</div>
                            <div class="fw-bold fs-5 text-danger">{{ $overdueInstallments }}</div>
                            <div style="font-size:0.72rem;">
                                @if($overdueInstallments > 0)
                                    <span class="text-danger">Past due date — collect or follow up</span>
                                @else
                                    <span class="text-muted">Nothing overdue right now</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100 {{ $supplierPaymentsDueCount > 0 ? 'border-start border-warning border-4' : '' }}">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-warning bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-truck fs-5 text-warning" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">Supplier balance due</div>
                            <div class="fw-bold fs-5 text-warning text-truncate">₱{{ number_format($supplierPaymentsDue, 2) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $supplierPaymentsDueCount }} PO(s) on 45-day terms with balance</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-primary bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-box-seam fs-5 text-primary" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">Units in warehouse</div>
                            <div class="fw-bold fs-5 text-truncate">{{ number_format($totalStock) }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">In-stock serials (cost value ₱{{ number_format($totalStockValue, 2) }})</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card app-card-panel border-0 shadow-sm h-100 {{ $lowStockProducts > 0 ? 'border-start border-danger border-4' : '' }}">
                <div class="card-body py-3 px-3">
                    <div class="d-flex align-items-start gap-2">
                        <div class="bg-danger bg-opacity-10 rounded p-2 flex-shrink-0">
                            <i class="bi bi-boxes fs-5 text-danger" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <div class="text-muted small mb-0">Low-stock products</div>
                            <div class="fw-bold fs-5 text-danger">{{ $lowStockProducts }}</div>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $outOfStockProducts }} with zero units in stock</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tables --}}
    <h6 class="text-uppercase text-muted fw-bold mb-2" style="font-size:0.68rem; letter-spacing:0.06em;">Details</h6>
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card app-card-panel border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-calendar-check text-warning me-1"></i> Installments due this month</h5>
                        <small class="text-muted">Unpaid or partial payments with a due date in {{ now()->format('F Y') }}</small>
                    </div>
                    <a href="{{ route('installments.index') }}" class="btn btn-sm btn-outline-primary">View all installments</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3 py-2">Customer</th>
                                    <th class="px-3 py-2">Invoice</th>
                                    <th class="px-3 py-2">Due date</th>
                                    <th class="px-3 py-2 text-end">Amount due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($installmentsToCollectThisMonth as $sale)
                                    @foreach($sale->installmentPayments as $payment)
                                    @php $due = max(0, (float) $payment->amount - (float) $payment->amount_paid); @endphp
                                    <tr>
                                        <td class="px-3 py-2">{{ $sale->customer_name }}</td>
                                        <td class="px-3 py-2">
                                            <a href="{{ route('installments.show', $sale->id) }}" class="text-decoration-none fw-semibold">
                                                {{ $sale->invoice_number }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2">
                                            <small>{{ $payment->due_date->format('M j, Y') }}</small>
                                            @if($payment->due_date->lt(now()->startOfDay()))
                                                <span class="badge bg-danger ms-1">Overdue</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-end fw-semibold text-danger">₱{{ number_format($due, 2) }}</td>
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

        <div class="col-lg-5">
            <div class="card app-card-panel border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-danger me-1"></i> Low stock</h5>
                        <small class="text-muted">Products with 5 or fewer units in stock</small>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-sm btn-outline-primary">Products &amp; stock</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3 py-2">Brand</th>
                                    <th class="px-3 py-2">Model</th>
                                    <th class="px-3 py-2">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockProductsList as $product)
                                <tr>
                                    <td class="px-3 py-2"><span class="fw-semibold">{{ $product->brand->name ?? '—' }}</span></td>
                                    <td class="px-3 py-2 text-muted small">{{ $product->display_model ?? $product->model ?? '—' }}</td>
                                    <td class="px-3 py-2">
                                        <span class="badge bg-{{ $product->in_stock_count == 0 ? 'danger' : 'warning text-dark' }}">
                                            {{ $product->in_stock_count }} left
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        <i class="bi bi-check-circle text-success fs-4 d-block mb-2"></i>
                                        Stock levels look good
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

    <div class="card app-card-panel border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-0"><i class="bi bi-clock-history text-primary me-1"></i> Recent sales</h5>
                <small class="text-muted">Latest 10 sales by time entered</small>
            </div>
            <a href="{{ route('sales.index') }}" class="btn btn-sm btn-outline-primary">All sales</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3 py-2">Invoice</th>
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2">Date</th>
                            <th class="px-3 py-2 text-end">Total</th>
                            <th class="px-3 py-2">Payment</th>
                            <th class="px-3 py-2 text-end">Balance</th>
                            <th class="px-3 py-2">Status</th>
                            <th class="px-3 py-2 text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentSales as $sale)
                        <tr>
                            <td class="px-3 py-2">
                                <a href="{{ route('sales.show', $sale) }}" class="text-decoration-none fw-semibold">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2">{{ $sale->customer_name }}</td>
                            <td class="px-3 py-2 small">{{ $sale->sale_date->format('M j, Y') }}</td>
                            <td class="px-3 py-2 text-end fw-semibold">₱{{ number_format($sale->total, 2) }}</td>
                            <td class="px-3 py-2">
                                <span class="badge bg-{{ $sale->payment_type == 'cash' ? 'success' : 'warning text-dark' }}">
                                    {{ ucfirst($sale->payment_type) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-end">
                                @if($sale->balance > 0)
                                    <span class="text-danger fw-semibold">₱{{ number_format($sale->balance, 2) }}</span>
                                @else
                                    <span class="text-success small">Paid</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-end">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No sales recorded yet
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
