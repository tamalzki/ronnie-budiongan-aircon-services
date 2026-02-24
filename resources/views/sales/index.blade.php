@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-receipt text-primary"></i> Sales</h2>
            <p class="text-muted mb-0">Manage customer sales & invoices</p>
        </div>
        <a href="{{ route('sales.create') }}" class="btn btn-primary btn-lg shadow-sm">
            <i class="bi bi-plus-circle"></i> New Sale
        </a>
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
    <form method="GET" action="{{ route('sales.index') }}">
        <div class="input-group">
            <span class="input-group-text bg-white">
                <i class="bi bi-search text-muted"></i>
            </span>
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   class="form-control border-start-0"
                   placeholder="Search invoice, customer, serial...">
            <button class="btn btn-outline-secondary">
                Search
            </button>
        </div>
    </form>
</div>
                <div class="col-md-2">
                    <select id="paymentFilter" class="form-select">
                        <option value="">All Payment Types</option>
                        <option value="cash">Cash</option>
                        <option value="installment">Installment</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
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
            <table class="table table-hover table-sm mb-0" id="salesTable" style="font-size:0.875rem;">
                <thead class="bg-light">
                    <tr>
                        <th class="px-3 py-2">Invoice #</th>
                        <th class="px-3 py-2">Customer</th>
                        <th class="px-3 py-2">Contact</th>
                        <th class="px-3 py-2">Sale Date</th>
                        <th class="px-3 py-2 text-end">Total</th>
                        <th class="px-3 py-2 text-center">Items</th>
                        <th class="px-3 py-2 text-end">Paid</th>
                        <th class="px-3 py-2">Method</th>
                        <th class="px-3 py-2">Payment</th>
                        <th class="px-3 py-2 text-end">Balance</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody id="salesTableBody">
                    @forelse($sales as $sale)
                    <tr class="sale-row"
                        data-invoice="{{ strtolower($sale->invoice_number) }}"
                        data-customer="{{ strtolower($sale->customer_name) }}"
                        data-contact="{{ strtolower($sale->customer_contact ?? '') }}"
                        data-payment="{{ $sale->payment_type }}"
                        data-status="{{ $sale->status }}">

                        <td class="px-3 py-2">
                            <a href="{{ route('sales.show', $sale) }}"
                               class="fw-semibold text-primary text-decoration-none">
                                {{ $sale->invoice_number }}
                            </a>
                        </td>

                        <td class="px-3 py-2">{{ $sale->customer_name }}</td>

                        <td class="px-3 py-2 text-muted">
                            {{ $sale->customer_contact ?? '—' }}
                        </td>

                        <td class="px-3 py-2">
                            {{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}
                        </td>

                        <td class="px-3 py-2 text-end">
                            <div class="fw-bold">₱{{ number_format($sale->total, 2) }}</div>
                            @if(($sale->discount ?? 0) > 0)
                                <small class="text-danger">
                                    -₱{{ number_format($sale->discount, 2) }}
                                </small>
                            @endif
                        </td>

                        <td class="px-3 py-2 text-center">
                            {{ $sale->items_count ?? $sale->items->count() }}
                        </td>

                        <td class="px-3 py-2 text-end">
                            ₱{{ number_format($sale->paid_amount, 2) }}
                        </td>

                        <td class="px-3 py-2">
                            {{ ucfirst(str_replace('_',' ',$sale->payment_method)) }}
                        </td>

                        <td class="px-3 py-2">
                            <span class="badge {{ $sale->payment_type == 'cash' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ ucfirst($sale->payment_type) }}
                            </span>
                        </td>

                        <td class="px-3 py-2 text-end">
                            @if($sale->balance > 0)
                                <span class="text-danger fw-semibold">
                                    ₱{{ number_format($sale->balance, 2) }}
                                </span>
                            @else
                                <span class="text-success fw-semibold">
                                    Paid
                                </span>
                            @endif
                        </td>

                        <td class="px-3 py-2">
                            <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'pending' ? 'warning text-dark' : 'danger') }}">
                                {{ ucfirst($sale->status) }}
                            </span>
                        </td>

                        <td class="px-3 py-2 text-center">
    <div class="d-flex justify-content-center gap-1">

        {{-- View --}}
        <a href="{{ route('sales.show', $sale) }}"
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-eye me-1"></i> View
        </a>

        {{-- Installments (if applicable) --}}
        @if($sale->payment_type === 'installment')
        <a href="{{ route('installments.show', $sale->id) }}"
           class="btn btn-sm {{ $sale->balance > 0 ? 'btn-warning' : 'btn-success' }}">
            <i class="bi bi-calendar-check me-1"></i> Installments
        </a>
        @endif

        {{-- Delete (icon only) --}}
        <form action="{{ route('sales.destroy', $sale) }}"
              method="POST"
              onsubmit="return confirm('Delete this sale? Stock will be restored.')">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="btn btn-outline-danger btn-sm"
                    title="Delete">
                <i class="bi bi-trash"></i>
            </button>
        </form>

    </div>
</td>

                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No sales yet — create your first sale!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

        {{-- Pagination --}}
        @if($sales->hasPages())
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-light">
            <small class="text-muted">
                Showing {{ $sales->firstItem() }}–{{ $sales->lastItem() }}
                of {{ $sales->total() }} sales
            </small>
            {{ $sales->links() }}
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function filterTable() {
    const search  = document.getElementById('saleSearch').value.toLowerCase();
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
    document.getElementById('saleSearch').value = '';
    document.getElementById('paymentFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filterTable();
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('saleSearch').addEventListener('input', filterTable);
    document.getElementById('paymentFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@endsection