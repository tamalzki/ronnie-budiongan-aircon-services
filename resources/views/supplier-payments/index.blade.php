@extends('layouts.app')

@section('title', 'Supplier Payments')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-cash-coin text-primary"></i> Supplier Payments</h2>
            <p class="text-muted mb-0">Track payments to suppliers</p>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 rounded p-2">
                        <i class="bi bi-cash-stack fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Paid</div>
                        <div class="fw-bold text-success">₱{{ number_format($totalPaid, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-danger bg-opacity-10 rounded p-2">
                        <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Outstanding</div>
                        <div class="fw-bold text-danger">₱{{ number_format($totalPending, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="bi bi-receipt fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Transactions</div>
                        <div class="fw-bold">{{ $payments->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-0" id="paymentTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-history"
                    style="padding:0.75rem 1.5rem;font-size:0.9rem;border:1px solid transparent;background:white;color:#0d6efd;">
                <i class="bi bi-clock-history"></i> Payment History
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pending"
                    style="padding:0.75rem 1.5rem;font-size:0.9rem;border:1px solid transparent;">
                <i class="bi bi-exclamation-circle"></i> Pending Payments
                @if($unpaidPOs->count() > 0)
                    <span class="badge bg-danger ms-1">{{ $unpaidPOs->count() }}</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm mb-4" id="paymentTabContent">

        {{-- ═══════════════════════════════
             TAB 1: PAYMENT HISTORY
        ════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="tab-history">

            {{-- Search & Filter --}}
            <div class="border-bottom py-3 px-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="paymentSearch" class="form-control border-start-0"
                                   placeholder="Search PO, supplier...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="methodFilter" class="form-select form-select-sm">
                            <option value="">All Methods</option>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="typeFilter" class="form-select form-select-sm">
                            <option value="">All Types</option>
                            <option value="downpayment">Downpayment</option>
                            <option value="regular">Regular Payment</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="paymentsTable" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Payment Date</th>
                            <th class="border-0 px-3 py-2">PO Number</th>
                            <th class="border-0 px-3 py-2">Supplier</th>
                            <th class="border-0 px-3 py-2">Amount</th>
                            <th class="border-0 px-3 py-2">Method</th>
                            <th class="border-0 px-3 py-2">Reference</th>
                            <th class="border-0 px-3 py-2">By</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        @forelse($payments as $payment)
                        @php $isDownpayment = str_contains(strtolower($payment->payment_number), 'downpayment'); @endphp
                        <tr class="{{ $isDownpayment ? 'table-warning' : '' }} payment-row"
                            data-search="{{ strtolower($payment->purchaseOrder->po_number . ' ' . $payment->purchaseOrder->supplier->name) }}"
                            data-method="{{ $payment->payment_method }}"
                            data-type="{{ $isDownpayment ? 'downpayment' : 'regular' }}">
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div>{{ $payment->payment_date->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $payment->payment_date->diffForHumans() }}</small>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $payment->purchaseOrder) }}"
                                   class="text-decoration-none fw-semibold text-primary">
                                    {{ $payment->purchaseOrder->po_number }}
                                </a>
                                @if($isDownpayment)
                                    <br><span class="badge bg-warning text-dark"><i class="bi bi-cash"></i> Downpayment</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">{{ $payment->purchaseOrder->supplier->name }}</td>
                            <td class="px-3 py-2 fw-semibold text-success" style="white-space:nowrap">
                                ₱{{ number_format($payment->amount, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                </span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-muted">{{ $payment->reference_number ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <small class="text-muted">{{ $payment->user->name }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No payment history yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>{{-- end tab-history --}}

        {{-- ═══════════════════════════════
             TAB 2: PENDING PAYMENTS
        ════════════════════════════════ --}}
        <div class="tab-pane fade p-4" id="tab-pending">

            @if($unpaidPOs->count() > 0)
            <h6 class="fw-semibold mb-3">
                <i class="bi bi-exclamation-triangle text-danger"></i> Outstanding Payments — {{ $unpaidPOs->count() }} Order(s)
            </h6>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">PO Number</th>
                            <th class="border-0 px-3 py-2">Supplier</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Order Date</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Delivery Date</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Due Date</th>
                            <th class="border-0 px-3 py-2">Total</th>
                            <th class="border-0 px-3 py-2">Paid</th>
                            <th class="border-0 px-3 py-2">Balance</th>
                            <th class="border-0 px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaidPOs as $po)
                        @php
                            $daysLeft = $po->payment_due_date ? now()->diffInDays($po->payment_due_date, false) : null;
                            $rowClass = '';
                            if ($daysLeft !== null && $daysLeft < 0) $rowClass = 'table-danger';
                            elseif ($daysLeft !== null && $daysLeft <= 10) $rowClass = 'table-warning';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}"
                                   class="text-decoration-none fw-semibold text-primary">
                                    {{ $po->po_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">{{ $po->supplier->name }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($po->received_date)
                                    {{ $po->received_date->format('M d, Y') }}
                                @elseif($po->expected_delivery_date)
                                    <span class="text-muted">Expected: {{ $po->expected_delivery_date->format('M d, Y') }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($po->payment_due_date)
                                    <div>{{ $po->payment_due_date->format('M d, Y') }}</div>
                                    @if($daysLeft < 0)
                                        <small class="text-danger fw-bold"><i class="bi bi-alarm"></i> Overdue {{ abs((int)$daysLeft) }}d</small>
                                    @elseif($daysLeft == 0)
                                        <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Due Today</small>
                                    @elseif($daysLeft <= 10)
                                        <small class="text-warning fw-bold"><i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }}d left</small>
                                    @else
                                        <small class="text-muted">{{ (int)$daysLeft }}d left</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">₱{{ number_format($po->total, 2) }}</td>
                            <td class="px-3 py-2 text-success" style="white-space:nowrap">₱{{ number_format($po->amount_paid, 2) }}</td>
                            <td class="px-3 py-2 fw-semibold text-danger" style="white-space:nowrap">₱{{ number_format($po->balance, 2) }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}"
                                   class="btn btn-primary"
                                   style="padding:2px 8px;font-size:0.78rem">
                                    <i class="bi bi-cash-coin"></i> Pay Now
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                <p class="mb-0">All payments settled — no outstanding balances!</p>
            </div>
            @endif

        </div>{{-- end tab-pending --}}

    </div>{{-- end tab-content --}}

</div>{{-- end container-fluid --}}

@push('scripts')
<script>
function filterTable() {
    const search = document.getElementById('paymentSearch').value.toLowerCase();
    const method = document.getElementById('methodFilter').value;
    const type   = document.getElementById('typeFilter').value;
    const rows   = document.querySelectorAll('.payment-row');
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchMethod = !method || row.dataset.method === method;
        const matchType   = !type   || row.dataset.type === type;

        if (matchSearch && matchMethod && matchType) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    let noResults = document.getElementById('noResultsRow');
    if (visible === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('paymentsTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

function clearFilters() {
    document.getElementById('paymentSearch').value = '';
    document.getElementById('methodFilter').value = '';
    document.getElementById('typeFilter').value = '';
    filterTable();
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('paymentSearch').addEventListener('input', filterTable);
    document.getElementById('methodFilter').addEventListener('change', filterTable);
    document.getElementById('typeFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@push('styles')
<style>
.nav-tabs .nav-link {
    color: #6c757d;
    border: 1px solid transparent;
    border-radius: 0.375rem 0.375rem 0 0;
}
.nav-tabs .nav-link:hover {
    border-color: #e9ecef #e9ecef #dee2e6;
    color: #0d6efd;
}
.nav-tabs .nav-link.active {
    color: #0d6efd;
    background-color: #fff;
    border-color: #dee2e6 #dee2e6 #fff;
    font-weight: 600;
}
</style>
@endpush

@endsection