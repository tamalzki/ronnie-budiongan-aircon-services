@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="container-fluid">

    <x-page-header title="Sales" subtitle="Manage customer sales & invoices" icon="bi-receipt">
        <x-slot name="actions">
            <a href="{{ route('sales.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> New Sale
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    {{-- Search --}}
    <div class="card app-card-panel mb-2 app-filter-toolbar">
        <div class="card-body py-2 px-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="saleSearchInput"
                               value="{{ request('search') }}"
                               class="form-control border-start-0"
                               placeholder="Search customer, serial..."
                               autocomplete="off">
                    </div>
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
    <div class="card app-card-panel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table" id="salesTable">
                    <thead>
                        <tr>
                            <th>Customer / Invoice</th>
                            <th>Date</th>
                            <th class="text-end">Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="salesTableBody">
                        @forelse($sales as $sale)
                        @php
                            $itemCnt = $sale->items_count ?? $sale->items->count();
                            $searchLower = strtolower($search ?? '');
                            $matchedSerials = $searchLower !== ''
                                ? $sale->items->flatMap->serials
                                    ->filter(fn ($s) => str_contains(strtolower($s->serial_number), $searchLower))
                                    ->unique('serial_number')
                                : collect();
                            $allSerials = strtolower(
                                $sale->items->flatMap->serials->pluck('serial_number')->implode(' ')
                            );
                            $serialHint = $matchedSerials->isNotEmpty()
                                ? ' · ' . $matchedSerials->pluck('serial_number')->take(3)->implode(', ')
                                : '';
                        @endphp
                        <tr class="sale-row"
                            data-invoice="{{ strtolower($sale->invoice_number) }}"
                            data-customer="{{ strtolower($sale->customer_name) }}"
                            data-contact="{{ strtolower($sale->customer_contact ?? '') }}"
                            data-serials="{{ $allSerials }}"
                            data-payment="{{ $sale->payment_type }}"
                            data-status="{{ $sale->status }}"
                            data-href="{{ route('sales.show', $sale) }}"
                            style="cursor:pointer;">

                            <td class="px-2 py-1" style="white-space:nowrap;max-width:280px;overflow:hidden;text-overflow:ellipsis;">
                                <span class="fw-semibold">{{ $sale->customer_name }}</span>
                                <span class="text-muted" style="font-size:0.72rem;">
                                    · <span style="font-family:monospace;">{{ $sale->invoice_number }}</span>
                                    @if($sale->customer_contact) · {{ $sale->customer_contact }} @endif
                                    @if($serialHint)<span class="text-primary">{{ $serialHint }}</span>@endif
                                </span>
                            </td>

                            <td class="px-2 py-1" style="white-space:nowrap;">
                                {{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}
                                <span class="text-muted" style="font-size:0.72rem;">· {{ $itemCnt }} item{{ $itemCnt != 1 ? 's' : '' }}</span>
                            </td>

                            <td class="px-2 py-1 text-end" style="white-space:nowrap;">
                                <span class="fw-bold">₱{{ number_format($sale->total, 2) }}</span>
                                @if(($sale->discount ?? 0) > 0)
                                    <small class="text-danger">(-₱{{ number_format($sale->discount, 0) }})</small>
                                @endif
                            </td>

                            <td class="px-2 py-1" style="white-space:nowrap;">
                                <span class="badge {{ $sale->payment_type == 'cash' ? 'bg-success' : 'bg-warning text-dark' }}" style="font-size:0.65rem;">
                                    {{ ucfirst($sale->payment_type) }}
                                </span>
                                @if($sale->balance > 0)
                                    <small class="text-danger fw-semibold">· ₱{{ number_format($sale->balance, 2) }} due</small>
                                @else
                                    <small class="text-success">· Paid</small>
                                @endif
                            </td>

                            <td class="px-2 py-1" style="white-space:nowrap;">
                                <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'pending' ? 'warning text-dark' : 'danger') }}" style="font-size:0.65rem;">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>

                            <td class="px-2 py-1" style="white-space:nowrap;">
                                <div class="app-act-wrap">
                                    <a href="{{ route('sales.edit', $sale) }}"
                                       class="btn btn-light border app-act" title="Edit sale details">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    @if($sale->payment_type === 'installment')
                                    <a href="{{ route('installments.show', $sale->id) }}"
                                       class="btn app-act {{ $sale->balance > 0 ? 'btn-warning text-dark' : 'btn-success text-white' }}"
                                       title="Installment schedule">
                                        <i class="bi bi-calendar-check"></i> Installments
                                    </a>
                                    @endif
                                    <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="app-act-form"
                                          onsubmit="return confirm('Delete this sale? Stock will be restored.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light border app-act text-danger" title="Delete">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No sales yet — create your first sale!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

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
let saleSearchTimer = null;

function filterTable() {
    const search  = document.getElementById('saleSearchInput').value.toLowerCase().trim();
    const payment = document.getElementById('paymentFilter').value;
    const status  = document.getElementById('statusFilter').value;
    const rows    = document.querySelectorAll('.sale-row');
    let visible   = 0;

    rows.forEach(row => {
        const matchSearch  = !search
            || row.dataset.invoice.includes(search)
            || row.dataset.customer.includes(search)
            || row.dataset.contact.includes(search)
            || (row.dataset.serials || '').includes(search);
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
    if (visible === 0 && rows.length > 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
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
    document.getElementById('saleSearchInput').value = '';

    const url = new URL(window.location.href);
    if (url.searchParams.has('search')) {
        url.searchParams.delete('search');
        window.location.href = url.pathname + (url.searchParams.toString() ? '?' + url.searchParams : '');
        return;
    }

    filterTable();
}

function syncSaleSearchToServer() {
    const input   = document.getElementById('saleSearchInput');
    const trimmed = input.value.trim();
    const current = new URLSearchParams(window.location.search).get('search') || '';

    if (trimmed === current) {
        return;
    }

    const url = new URL('{{ route('sales.index') }}', window.location.origin);
    if (trimmed) {
        url.searchParams.set('search', trimmed);
    }

    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('saleSearchInput');

    searchInput.addEventListener('input', function () {
        filterTable();
        clearTimeout(saleSearchTimer);
        saleSearchTimer = setTimeout(syncSaleSearchToServer, 350);
    });

    document.getElementById('paymentFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);

    document.querySelectorAll('.sale-row[data-href]').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form, input, select, .modal')) return;
            window.location.href = this.dataset.href;
        });
    });
});
</script>
@endpush

@endsection
