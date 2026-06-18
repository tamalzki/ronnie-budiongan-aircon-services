@extends('layouts.app')

@section('title', 'Installment Customers')

@section('content')
<div class="container-fluid">

    <x-page-header title="Installment Customers" subtitle="Track customer installment payments" icon="bi-people-fill" />

    <x-flash />

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-people-fill text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Customers</div>
                        <div class="fw-bold" style="font-size:1.05rem;line-height:1.2;">{{ $customersData->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-calendar-event text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Due This Month</div>
                        <div class="fw-bold text-warning" style="font-size:1.05rem;line-height:1.2;">{{ $dueThisMonthCustomers->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-cash-coin text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">To Collect</div>
                        <div class="fw-bold text-success" style="font-size:1.05rem;line-height:1.2;">₱{{ number_format($dueThisMonthTotal, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════ --}}
    {{-- TAB: All Customers                  --}}
    {{-- ═══════════════════════════════════ --}}
    <div id="tabAll">
        {{-- Tabs + Search / Filter --}}
        <div class="card app-card-panel mb-3 app-filter-toolbar">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="installmentSearch" class="form-control border-start-0"
                                   placeholder="Search customer...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="balanceFilter" class="form-select form-select-sm">
                            <option value="">All Balances</option>
                            <option value="unpaid">With Balance</option>
                            <option value="paid">Fully Paid</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-group btn-group-sm" role="group">
                            <button id="tabAllBtn" onclick="switchTab('all')" class="btn btn-primary btn-sm">
                                <i class="bi bi-people"></i> All Customers
                            </button>
                            <button id="tabDueBtn" onclick="switchTab('due')" class="btn btn-outline-warning btn-sm text-dark">
                                <i class="bi bi-calendar-check"></i> Due This Month
                                @if($dueThisMonthCustomers->count() > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $dueThisMonthCustomers->count() }}</span>
                                @endif
                            </button>
                        </div>
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
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Balance</th>
                                <th style="white-space:nowrap">Last Payment</th>
                                <th style="white-space:nowrap">Next Due</th>
                                <th class="text-center no-print" style="width:56px;white-space:nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="installmentTableBody">
                            @php
                                $ordinal = function (int $n): string {
                                    if ($n <= 0) return '';
                                    $mod100 = $n % 100;
                                    if ($mod100 >= 11 && $mod100 <= 13) return $n . 'th';
                                    return $n . (['th','st','nd','rd'][$n % 10] ?? 'th');
                                };
                            @endphp
                            @forelse($customersData as $customer)
                            <tr class="installment-row"
                                data-customer="{{ strtolower($customer->customer_name) }}"
                                data-contact="{{ strtolower($customer->customer_contact ?? '') }}"
                                data-balance="{{ $customer->total_balance > 0 ? 'unpaid' : 'paid' }}"
                                data-href="{{ route('installments.show', $customer->first_sale_id) }}"
                                style="cursor:pointer;">
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
                                    @if($customer->last_payment_date)
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($customer->last_payment_date)->format('M d, Y') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    @if($customer->next_due_date)
                                        <span class="fw-semibold">{{ \Carbon\Carbon::parse($customer->next_due_date)->format('M d, Y') }}</span>
                                        <span class="text-muted" style="font-size:0.72rem;">· {{ $ordinal((int) $customer->next_installment_number) }} payment</span>
                                    @else
                                        <span class="badge bg-success">Fully Paid</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    @include('installments.partials.edit-customer-button', [
                                        'saleId' => $customer->first_sale_id,
                                        'name' => $customer->customer_name,
                                        'contact' => $customer->customer_contact,
                                        'address' => $customer->customer_address,
                                    ])
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
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

        {{-- Tabs + Search bar for due tab --}}
        <div class="card app-card-panel mb-3 app-filter-toolbar">
            <div class="card-body py-2">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="dueSearch" class="form-control border-start-0"
                                   placeholder="Search customer...">
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
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearDueFilters()" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <div class="col-auto">
                        <span class="badge bg-light text-secondary border" style="font-size:0.72rem;padding:5px 8px;">
                            <i class="bi bi-calendar3 me-1"></i>{{ now()->format('F Y') }}
                        </span>
                    </div>
                    <div class="col-auto ms-auto">
                        <div class="btn-group btn-group-sm" role="group">
                            <button onclick="switchTab('all')" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-people"></i> All Customers
                            </button>
                            <button onclick="switchTab('due')" class="btn btn-warning btn-sm text-dark">
                                <i class="bi bi-calendar-check"></i> Due This Month
                                @if($dueThisMonthCustomers->count() > 0)
                                    <span class="badge bg-warning text-dark ms-1" style="color:#664d03 !important;">{{ $dueThisMonthCustomers->count() }}</span>
                                @endif
                            </button>
                        </div>
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
                                <th class="text-end">Total Amount</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Balance</th>
                                <th style="white-space:nowrap">Last Payment</th>
                                <th style="white-space:nowrap">Next Due</th>
                                <th class="text-center no-print" style="width:56px;white-space:nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="dueTableBody">
                            @forelse($dueThisMonthCustomers as $customer)
                            @php
                                $daysLeft = (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($customer->next_due_date)->startOfDay(), false);

                                if ($daysLeft < 0)      $urgency = 'overdue';
                                elseif ($daysLeft === 0) $urgency = 'today';
                                else                    $urgency = 'upcoming';
                            @endphp
                            <tr class="due-row"
                                data-customer="{{ strtolower($customer->customer_name) }}"
                                data-urgency="{{ $urgency }}"
                                data-href="{{ route('installments.show', $customer->first_sale_id) }}"
                                style="cursor:pointer;">
                                <td style="white-space:nowrap">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-{{ $urgency === 'overdue' ? 'danger' : ($urgency === 'today' ? 'warning' : 'primary') }} bg-opacity-10 rounded d-flex align-items-center justify-content-center flex-shrink-0"
                                             style="width:28px;height:28px;">
                                            <i class="bi bi-person-fill text-{{ $urgency === 'overdue' ? 'danger' : ($urgency === 'today' ? 'warning' : 'primary') }}"
                                               style="font-size:0.8rem"></i>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">{{ $customer->customer_name }}</div>
                                            @if($customer->customer_address)
                                                <small class="text-muted">{{ $customer->customer_address }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-semibold text-end" style="white-space:nowrap">
                                    ₱{{ number_format($customer->total_amount, 2) }}
                                </td>
                                <td class="text-end" style="white-space:nowrap">
                                    <span class="text-success fw-semibold">₱{{ number_format($customer->total_paid, 2) }}</span>
                                </td>
                                <td class="text-end" style="white-space:nowrap">
                                    <span class="text-danger fw-semibold">₱{{ number_format($customer->total_balance, 2) }}</span>
                                </td>
                                <td style="white-space:nowrap">
                                    @if($customer->last_payment_date)
                                        <span class="text-muted">{{ \Carbon\Carbon::parse($customer->last_payment_date)->format('M d, Y') }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap">
                                    <span class="fw-semibold">{{ \Carbon\Carbon::parse($customer->next_due_date)->format('M d, Y') }}</span>
                                    <span class="text-muted" style="font-size:0.72rem;">· {{ $ordinal((int) $customer->next_installment_number) }} payment</span>
                                    @if($urgency === 'overdue')
                                        <span class="badge bg-danger ms-1"><i class="bi bi-alarm me-1"></i>{{ abs($daysLeft) }}d overdue</span>
                                    @elseif($urgency === 'today')
                                        <span class="badge bg-warning text-dark ms-1"><i class="bi bi-exclamation-circle me-1"></i>Today</span>
                                    @endif
                                </td>
                                <td class="text-center no-print">
                                    @include('installments.partials.edit-customer-button', [
                                        'saleId' => $customer->first_sale_id,
                                        'name' => $customer->customer_name,
                                        'contact' => $customer->customer_contact,
                                        'address' => $customer->customer_address,
                                    ])
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                                    No payments due this month
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($dueThisMonthCustomers->count() > 0)
                        <tfoot>
                            <tr class="fw-semibold">
                                <td colspan="3" class="text-end text-muted" style="font-size:0.78rem;">Total to collect this month</td>
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

@include('installments.partials.edit-customer-modal')

@push('scripts')
<script>
function openEditCustomerModal(btn) {
    const form = document.getElementById('editCustomerModalForm');
    form.action = btn.dataset.action;
    document.getElementById('editCustomerModalName').value = btn.dataset.name || '';
    document.getElementById('editCustomerModalContact').value = btn.dataset.contact || '';
    document.getElementById('editCustomerModalAddress').value = btn.dataset.address || '';
}
// ── Tab switching ──────────────────────────────────────
function switchTab(tab) {
    const allDiv  = document.getElementById('tabAll');
    const dueDiv  = document.getElementById('tabDue');

    if (tab === 'all') {
        allDiv.style.display = '';
        dueDiv.style.display = 'none';
    } else {
        allDiv.style.display = 'none';
        dueDiv.style.display = '';
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
            noResults.innerHTML = '<td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
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
        const matchSearch = !search || row.dataset.customer.includes(search);
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
            noResults.innerHTML = '<td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
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

    document.querySelectorAll('.installment-row[data-href], .due-row[data-href]').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form, input, select, .modal, .edit-customer-btn')) return;
            window.location.href = this.dataset.href;
        });
    });

    document.querySelectorAll('.edit-customer-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            openEditCustomerModal(btn);
        });
    });
});
</script>
@endpush

@endsection
