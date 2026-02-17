@extends('layouts.app')

@section('title', 'Installment Customers')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-people-fill text-primary"></i> Installment Customers</h2>
            <p class="text-muted mb-0">Track customer installment payments</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="installmentSearch" class="form-control border-start-0"
                               placeholder="Search customer, contact...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="balanceFilter" class="form-select">
                        <option value="">All Balances</option>
                        <option value="unpaid">With Balance</option>
                        <option value="paid">Fully Paid</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button class="btn btn-outline-secondary w-100" onclick="clearFilters()" title="Clear">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="installmentTable" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Customer</th>
                            <th class="border-0 px-3 py-2">Contact</th>
                       
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Total Amount</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Total Paid</th>
                            <th class="border-0 px-3 py-2">Balance</th>
                            <th class="border-0 px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="installmentTableBody">
                        @forelse($customersData as $customer)
                        <tr class="installment-row {{ $customer->total_balance > 0 ? '' : 'table-success table-success-subtle' }}"
                            data-customer="{{ strtolower($customer->customer_name) }}"
                            data-contact="{{ strtolower($customer->customer_contact ?? '') }}"
                            data-balance="{{ $customer->total_balance > 0 ? 'unpaid' : 'paid' }}">
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center"
                                         style="width:28px;height:28px;flex-shrink:0">
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
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-muted">{{ $customer->customer_contact ?? '—' }}</span>
                            </td>
                           
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                ₱{{ number_format($customer->total_amount, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-success fw-semibold">₱{{ number_format($customer->total_paid, 2) }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($customer->total_balance > 0)
                                    <span class="text-danger fw-semibold">₱{{ number_format($customer->total_balance, 2) }}</span>
                                @else
                                    <span class="badge bg-success">Fully Paid</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('installments.show', $customer->first_sale_id) }}"
                                   class="btn btn-outline-primary"
                                   style="padding:2px 8px;font-size:0.78rem">
                                    <i class="bi bi-calendar-check"></i> View Installments
                                </a>
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

@push('scripts')
<script>
function filterTable() {
    const search  = document.getElementById('installmentSearch').value.toLowerCase();
    const balance = document.getElementById('balanceFilter').value;
    const rows    = document.querySelectorAll('.installment-row');
    let visible   = 0;

    rows.forEach(row => {
        const matchSearch  = !search  || row.dataset.customer.includes(search)
                                      || row.dataset.contact.includes(search);
        const matchBalance = !balance || row.dataset.balance === balance;

        if (matchSearch && matchBalance) {
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

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('installmentSearch').addEventListener('input', filterTable);
    document.getElementById('balanceFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@endsection