@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-bold"><i class="bi bi-receipt text-primary"></i> Sales</h4>
            <p class="text-muted mb-0 small">Manage customer sales & invoices</p>
        </div>
        <a href="{{ route('sales.create') }}" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-circle"></i> New Sale
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-2 py-2">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Search --}}
    <div class="card border-0 shadow-sm mb-2">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <form method="GET" action="{{ route('sales.index') }}">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="saleSearchInput" name="search"
                                   value="{{ request('search') }}"
                                   class="form-control border-start-0"
                                   placeholder="Search customer, serial...">
                            <button class="btn btn-outline-secondary">Search</button>
                        </div>
                    </form>
                </div>
                <div class="col-md-2">
                    <select id="paymentFilter" class="form-select form-select-sm">
                        <option value="">All Payment Types</option>
                        <option value="cash">Cash</option>
                        <option value="installment">Installment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()" title="Clear">
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
                <table class="table table-hover table-sm mb-0" id="salesTable" style="font-size:0.82rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-2 py-2 text-center" style="width:40px;">#</th>
                            <th class="px-2 py-2">Customer</th>
                            <th class="px-2 py-2" style="white-space:nowrap">Date</th>
                            <th class="px-2 py-2 text-end" style="white-space:nowrap">Total</th>
                            <th class="px-2 py-2 text-center">Items</th>
                            <th class="px-2 py-2">Payment</th>
                            <th class="px-2 py-2 text-end">Balance</th>
                            <th class="px-2 py-2">Status</th>
                            <th class="px-2 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        @forelse($sales as $sale)
                        @php $rowNum = $sales->firstItem() + $loop->index; @endphp
                        <tr class="sale-row"
                            data-invoice="{{ strtolower($sale->invoice_number) }}"
                            data-customer="{{ strtolower($sale->customer_name) }}"
                            data-contact="{{ strtolower($sale->customer_contact ?? '') }}"
                            data-payment="{{ $sale->payment_type }}"
                            data-status="{{ $sale->status }}">

                            {{-- # (sequential, invoice on hover) --}}
                            <td class="px-2 py-1 text-center">
                                <a href="{{ route('sales.show', $sale) }}"
                                   class="fw-bold text-primary text-decoration-none"
                                   title="{{ $sale->invoice_number }}">
                                    #{{ $rowNum }}
                                </a>
                            </td>

                            {{-- Customer + contact --}}
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div class="fw-semibold" style="font-size:0.83rem;">{{ $sale->customer_name }}</div>
                                @if($sale->customer_contact)
                                    <div class="text-muted" style="font-size:0.72rem;">{{ $sale->customer_contact }}</div>
                                @endif
                            </td>

                            {{-- Date --}}
                            <td class="px-2 py-1" style="white-space:nowrap">
                                {{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}
                            </td>

                            {{-- Total --}}
                            <td class="px-2 py-1 text-end" style="white-space:nowrap">
                                <div class="fw-bold">₱{{ number_format($sale->total, 2) }}</div>
                                @if(($sale->discount ?? 0) > 0)
                                    <div class="text-danger" style="font-size:0.72rem;">-₱{{ number_format($sale->discount, 2) }}</div>
                                @endif
                            </td>

                            {{-- Items --}}
                            <td class="px-2 py-1 text-center">
                                {{ $sale->items_count ?? $sale->items->count() }}
                            </td>

                            {{-- Payment type + method --}}
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <span class="badge {{ $sale->payment_type == 'cash' ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size:0.7rem;">
                                    {{ ucfirst($sale->payment_type) }}
                                </span>
                                <div class="text-muted" style="font-size:0.72rem;">{{ ucfirst(str_replace('_',' ',$sale->payment_method)) }}</div>
                            </td>

                            {{-- Balance --}}
                            <td class="px-2 py-1 text-end" style="white-space:nowrap">
                                @if($sale->balance > 0)
                                    <div class="text-danger fw-semibold">₱{{ number_format($sale->balance, 2) }}</div>
                                    <div class="text-muted" style="font-size:0.72rem;">paid ₱{{ number_format($sale->paid_amount, 2) }}</div>
                                @else
                                    <span class="text-success fw-semibold">Paid</span>
                                @endif
                            </td>

                            {{-- Status --}}
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'pending' ? 'warning text-dark' : 'danger') }}" style="font-size:0.7rem;">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>

                            {{-- Actions --}}
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('sales.show', $sale) }}"
                                       class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size:0.75rem;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if($sale->payment_type === 'installment')
                                    <a href="{{ route('installments.show', $sale->id) }}"
                                       class="btn btn-sm py-0 px-2 {{ $sale->balance > 0 ? 'btn-warning' : 'btn-success' }}" style="font-size:0.75rem;">
                                        <i class="bi bi-calendar-check"></i> Installments
                                    </a>
                                    @endif
                                    <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this sale? Stock will be restored.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2"
                                                title="Delete" style="font-size:0.75rem;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>

                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No sales yet — create your first sale!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($sales->hasPages())
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted">
                    Showing {{ $sales->firstItem() }}–{{ $sales->lastItem() }} of {{ $sales->total() }} sales
                </small>
                {{ $sales->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
function filterTable() {
    const search  = ''; // server-side search handles this
    const payment = document.getElementById('paymentFilter').value;
    const status  = document.getElementById('statusFilter').value;
    const rows    = document.querySelectorAll('.sale-row');
    let visible   = 0;

    rows.forEach(row => {
        const matchSearch  = !search  || row.dataset.invoice.includes(search)
                                      || row.dataset.customer.includes(search)
                                      || row.dataset.contact.includes(search);
        const matchPayment = !payment || row.dataset.payment === payment;
        const matchStatus  = !status  || row.dataset.status === status;

        if (matchSearch && matchPayment && matchStatus) {
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
            noResults.innerHTML = '<td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('salesTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

function clearFilters() {
    document.getElementById('paymentFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filterTable();
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('paymentFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@endsection