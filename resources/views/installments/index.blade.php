@extends('layouts.app')

@section('title', 'Installment Customers')

@section('content')
<div class="container-fluid">

    <x-page-header title="Installment Customers" subtitle="Track customer installment payments" icon="bi-people-fill" />

    <x-flash />

    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="bi bi-people-fill fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Customers</div>
                        <div class="fw-bold fs-5">{{ $customersData->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-warning bg-opacity-10 rounded p-2">
                        <i class="bi bi-calendar-event fs-4 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Due This Month</div>
                        <div class="fw-bold fs-5 text-warning">{{ $dueThisMonth->count() }}</div>
                        <div class="text-muted" style="font-size:0.7rem;">payment(s)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 rounded p-2">
                        <i class="bi bi-cash-coin fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">To Collect</div>
                        <div class="fw-bold fs-5 text-success">₱{{ number_format($dueThisMonthTotal, 2) }}</div>
                        <div class="text-muted" style="font-size:0.7rem;">this month</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100 {{ $overdueCount > 0 ? 'border-danger border-opacity-25' : '' }}">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-danger bg-opacity-10 rounded p-2">
                        <i class="bi bi-alarm fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Overdue</div>
                        <div class="fw-bold fs-5 {{ $overdueCount > 0 ? 'text-danger' : 'text-muted' }}">{{ $overdueCount }}</div>
                        <div class="text-muted" style="font-size:0.7rem;">past due date</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tab Bar --}}
    <div class="d-flex gap-2 mb-3 align-items-center">
        <button id="tabAllBtn" onclick="switchTab('all')"
                class="btn btn-primary btn-sm">
            <i class="bi bi-people me-1"></i> All Customers
        </button>
        <button id="tabDueBtn" onclick="switchTab('due')"
                class="btn btn-outline-warning btn-sm text-dark">
            <i class="bi bi-calendar-check me-1"></i>
            Due This Month
            @if($dueThisMonth->count() > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $dueThisMonth->count() }}</span>
            @endif
        </button>
        @if($overdueCount > 0)
        <span class="badge bg-danger ms-1" style="font-size:0.72rem;">
            <i class="bi bi-alarm me-1"></i>{{ $overdueCount }} overdue
        </span>
        @endif
    </div>

    {{-- ═══════════════════════════════════ --}}
    {{-- TAB: All Customers                  --}}
    {{-- ═══════════════════════════════════ --}}
    <div id="tabAll">
        {{-- Search / Filter --}}
        <div class="card app-card-panel mb-3 app-filter-toolbar">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="installmentSearch" class="form-control border-start-0"
                                   placeholder="Search customer, contact...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="balanceFilter" class="form-select form-select-sm">
                            <option value="">All Balances</option>
                            <option value="unpaid">With Balance</option>
                            <option value="paid">Fully Paid</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearFilters()" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card app-card-panel">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0 app-table" id="installmentTable">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="installmentTableBody">
                            @forelse($customersData as $customer)
                            <tr class="installment-row"
                                data-customer="{{ strtolower($customer->customer_name) }}"
                                data-contact="{{ strtolower($customer->customer_contact ?? '') }}"
                                data-balance="{{ $customer->total_balance > 0 ? 'unpaid' : 'paid' }}">
                                <td style="white-space:nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:28px;height:28px;">
                                            <i class="bi bi-person-fill text-primary" style="font-size:0.8rem"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $customer->customer_name }}</div>
                                            @if($customer->customer_address)
                                                <small class="text-muted">{{ $customer->customer_address }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td style="white-space:nowrap">
                                    <span class="text-muted">{{ $customer->customer_contact ?? '—' }}</span>
                                </td>
                                <td class="fw-semibold text-end" style="white-space:nowrap">
                                    ₱{{ number_format($customer->total_amount, 2) }}
                                </td>
                                <td class="text-end" style="white-space:nowrap">
                                    <span class="text-success fw-semibold">₱{{ number_format($customer->total_paid, 2) }}</span>
                                </td>
                                <td class="text-end" style="white-space:nowrap">
                                    @if($customer->total_balance > 0)
                                        <span class="text-danger fw-semibold">₱{{ number_format($customer->total_balance, 2) }}</span>
                                    @else
                                        <span class="badge bg-success">Fully Paid</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    <a href="{{ route('installments.show', $customer->first_sale_id) }}"
                                       class="btn btn-light border app-act">
                                        <i class="bi bi-calendar-check"></i><span class="act-label"> View</span>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No installment customers yet
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════ --}}
    {{-- TAB: Due This Month                 --}}
    {{-- ═══════════════════════════════════ --}}
    <div id="tabDue" style="display:none;">

        {{-- Search bar for due tab --}}
        <div class="card app-card-panel mb-3 app-filter-toolbar">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="dueSearch" class="form-control border-start-0"
                                   placeholder="Search customer or invoice...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="dueStatusFilter" class="form-select form-select-sm">
                            <option value="">All Status</option>
                            <option value="overdue">Overdue</option>
                            <option value="today">Due Today</option>
                            <option value="upcoming">Upcoming</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-outline-secondary btn-sm w-100" onclick="clearDueFilters()" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <span class="badge bg-warning text-dark" style="font-size:0.78rem;padding:6px 10px;">
                            <i class="bi bi-calendar3 me-1"></i>
                            {{ now()->format('F Y') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card app-card-panel">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0 app-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Invoice</th>
                                <th style="white-space:nowrap">Due Date</th>
                                <th style="white-space:nowrap">Days</th>
                                <th class="text-end" style="white-space:nowrap">Amount Due</th>
                                <th class="text-end" style="white-space:nowrap">Paid So Far</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="dueTableBody">
                            @forelse($dueThisMonth as $payment)
                            @php
                                $daysLeft  = (int) now()->startOfDay()->diffInDays($payment->due_date->startOfDay(), false);
                                $remaining = $payment->amount - $payment->amount_paid;

                                if ($daysLeft < 0)      $urgency = 'overdue';
                                elseif ($daysLeft === 0) $urgency = 'today';
                                else                    $urgency = 'upcoming';
                            @endphp
                            <tr class="due-row"
                                data-customer="{{ strtolower($payment->sale->customer_name) }}"
                                data-invoice="{{ strtolower($payment->sale->invoice_number) }}"
                                data-urgency="{{ $urgency }}">
                                <td style="white-space:nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-{{ $urgency === 'overdue' ? 'danger' : ($urgency === 'today' ? 'warning' : 'primary') }} bg-opacity-10 rounded d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:28px;height:28px;">
                                            <i class="bi bi-person-fill text-{{ $urgency === 'overdue' ? 'danger' : ($urgency === 'today' ? 'warning' : 'primary') }}"
                                               style="font-size:0.8rem"></i>
                                        </div>
                                        <div class="fw-semibold">{{ $payment->sale->customer_name }}</div>
                                    </div>
                                </td>
                                <td style="white-space:nowrap">
                                    <a href="{{ route('sales.show', $payment->sale) }}"
                                       class="fw-semibold text-primary text-decoration-none"
                                       style="font-family:monospace;font-size:0.78rem;">
                                        {{ $payment->sale->invoice_number }}
                                    </a>
                                </td>
                                <td style="white-space:nowrap">
                                    <div>{{ $payment->due_date->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $payment->due_date->format('l') }}</small>
                                </td>
                                <td style="white-space:nowrap">
                                    @if($urgency === 'overdue')
                                        <span class="badge bg-danger">
                                            <i class="bi bi-alarm me-1"></i>{{ abs($daysLeft) }}d overdue
                                        </span>
                                    @elseif($urgency === 'today')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-exclamation-circle me-1"></i>Today
                                        </span>
                                    @else
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                            <i class="bi bi-clock me-1"></i>{{ $daysLeft }}d left
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold" style="white-space:nowrap">
                                    ₱{{ number_format($remaining, 2) }}
                                </td>
                                <td class="text-end" style="white-space:nowrap">
                                    @if($payment->amount_paid > 0)
                                        <span class="text-success fw-semibold">₱{{ number_format($payment->amount_paid, 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    @if($payment->status === 'partial')
                                        <span class="badge bg-info text-dark"><i class="bi bi-hourglass-split me-1"></i>Partial</span>
                                    @else
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Unpaid</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    <a href="{{ route('installments.show', $payment->sale_id) }}"
                                       class="btn btn-warning btn-sm app-act text-dark">
                                        <i class="bi bi-cash-coin"></i> Collect
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                                    No payments due this month
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($dueThisMonth->count() > 0)
                        <tfoot>
                            <tr class="fw-semibold">
                                <td colspan="4" class="text-end text-muted" style="font-size:0.78rem;">Total to collect</td>
                                <td class="text-end text-warning">₱{{ number_format($dueThisMonthTotal, 2) }}</td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
// ── Tab switching ──────────────────────────────────────
function switchTab(tab) {
    const allDiv  = document.getElementById('tabAll');
    const dueDiv  = document.getElementById('tabDue');
    const allBtn  = document.getElementById('tabAllBtn');
    const dueBtn  = document.getElementById('tabDueBtn');

    if (tab === 'all') {
        allDiv.style.display = '';
        dueDiv.style.display = 'none';
        allBtn.classList.replace('btn-outline-primary', 'btn-primary');
        dueBtn.classList.replace('btn-warning', 'btn-outline-warning');
        dueBtn.classList.add('text-dark');
    } else {
        allDiv.style.display = 'none';
        dueDiv.style.display = '';
        dueBtn.classList.replace('btn-outline-warning', 'btn-warning');
        allBtn.classList.replace('btn-primary', 'btn-outline-primary');
    }
}

// ── All Customers tab filters ──────────────────────────
function filterTable() {
    const search  = document.getElementById('installmentSearch').value.toLowerCase();
    const balance = document.getElementById('balanceFilter').value;
    const rows    = document.querySelectorAll('.installment-row');
    let visible   = 0;

    rows.forEach(row => {
        const matchSearch  = !search  || row.dataset.customer.includes(search)
                                      || row.dataset.contact.includes(search);
        const matchBalance = !balance || row.dataset.balance === balance;
        const show = matchSearch && matchBalance;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    let noResults = document.getElementById('noResultsRow');
    if (visible === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('installmentTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

function clearFilters() {
    document.getElementById('installmentSearch').value = '';
    document.getElementById('balanceFilter').value = '';
    filterTable();
}

// ── Due This Month tab filters ─────────────────────────
function filterDueTable() {
    const search  = document.getElementById('dueSearch').value.toLowerCase();
    const status  = document.getElementById('dueStatusFilter').value;
    const rows    = document.querySelectorAll('.due-row');
    let visible   = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.customer.includes(search)
                                    || row.dataset.invoice.includes(search);
        const matchStatus = !status || row.dataset.urgency === status;
        const show = matchSearch && matchStatus;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    let noResults = document.getElementById('noResultsDueRow');
    if (visible === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsDueRow';
            noResults.innerHTML = '<td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('dueTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

function clearDueFilters() {
    document.getElementById('dueSearch').value = '';
    document.getElementById('dueStatusFilter').value = '';
    filterDueTable();
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('installmentSearch').addEventListener('input', filterTable);
    document.getElementById('balanceFilter').addEventListener('change', filterTable);
    document.getElementById('dueSearch').addEventListener('input', filterDueTable);
    document.getElementById('dueStatusFilter').addEventListener('change', filterDueTable);

    // Auto-open Due This Month tab if there are overdue payments
    @if($overdueCount > 0)
    switchTab('due');
    @endif
});
</script>
@endpush

@endsection
