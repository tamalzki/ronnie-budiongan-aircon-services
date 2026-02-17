@extends('layouts.app')

@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-boxes text-primary"></i> Inventory</h2>
            <p class="text-muted mb-0">Track stock levels and movements</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="bi bi-box-seam fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Products</div>
                        <div class="fw-bold fs-5">{{ $totalProducts }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-warning bg-opacity-10 rounded p-2">
                        <i class="bi bi-exclamation-triangle fs-4 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Low Stock</div>
                        <div class="fw-bold fs-5 text-warning">{{ $lowStock }}</div>
                        <div class="text-muted" style="font-size:0.75rem">≤ 5 units</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-danger bg-opacity-10 rounded p-2">
                        <i class="bi bi-x-circle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Out of Stock</div>
                        <div class="fw-bold fs-5 text-danger">{{ $outOfStock }}</div>
                        <div class="text-muted" style="font-size:0.75rem">0 units</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 rounded p-2">
                        <i class="bi bi-currency-dollar fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Value</div>
                        <div class="fw-bold fs-5 text-success">₱{{ number_format($totalValue, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filters --}}
   <div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" id="searchProduct" class="form-control border-start-0"
                           placeholder="Search product or model...">
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" id="stockFilter">
                    <option value="all">All Stock</option>
                    <option value="low">Low Stock</option>
                    <option value="out">Out of Stock</option>
                    <option value="available">In Stock</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" id="brandFilter">
                    <option value="all">All Brands</option>
                    @foreach($products->pluck('brand')->unique()->filter() as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
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
                <table class="table table-hover table-sm mb-0" id="inventoryTable" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Product</th>
                            <th class="border-0 px-3 py-2">Brand</th>
                            <th class="border-0 px-3 py-2">Supplier</th>
                            <th class="border-0 px-3 py-2">Price</th>
                            <th class="border-0 px-3 py-2">Stock</th>
                            <th class="border-0 px-3 py-2">Value</th>
                            <th class="border-0 px-3 py-2">Movements</th>
                            <th class="border-0 px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        @forelse($products as $product)
                        @php
                            $rowClass = '';
                            if ($product->stock_quantity == 0)     $rowClass = 'table-danger';
                            elseif ($product->stock_quantity <= 5)  $rowClass = 'table-warning';
                        @endphp
                        <tr class="{{ $rowClass }}"
                            data-stock="{{ $product->stock_quantity }}"
                            data-brand="{{ $product->brand_id ?? 'none' }}"
                            data-name="{{ strtolower($product->name . ' ' . ($product->model ?? '')) }}">
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="fw-semibold">{{ $product->name }}</div>
                                @if($product->model)
                                    <small class="text-muted">{{ $product->model }}</small>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($product->brand)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                        {{ $product->brand->name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($product->supplier)
                                    <small class="text-muted">{{ $product->supplier->name }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                ₱{{ number_format($product->price, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($product->stock_quantity == 0)
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Out of Stock
                                    </span>
                                @elseif($product->stock_quantity <= 5)
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $product->stock_quantity }} units
                                    </span>
                                @else
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> {{ $product->stock_quantity }} units
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-success fw-semibold" style="white-space:nowrap">
                                ₱{{ number_format($product->stock_quantity * $product->price, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                    {{ $product->inventory_movements_count }}
                                </span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="d-flex gap-1">
    <a href="{{ route('inventory.show', $product) }}"
       class="btn btn-primary"
       style="padding:2px 8px;font-size:0.78rem"
       title="Manage">
        <i class="bi bi-box-arrow-in-right"></i> Manage
    </a>
    <button class="btn btn-warning"
            style="padding:2px 8px;font-size:0.78rem"
            data-bs-toggle="modal"
            data-bs-target="#adjustModal{{ $product->id }}"
            title="Adjust stock">
        <i class="bi bi-gear"></i> Adjust
    </button>
</div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No products in inventory
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Adjustment Modals --}}
@foreach($products as $product)
<div class="modal fade" id="adjustModal{{ $product->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.adjust', $product) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning border-0">
                    <h5 class="modal-title"><i class="bi bi-gear"></i> Adjust Stock — {{ $product->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-3">
                        <i class="bi bi-info-circle"></i>
                        Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity"
                               value="{{ $product->stock_quantity }}" min="0" required>
                        <small class="text-muted">Enter the new total stock quantity</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Adjustment <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="notes" rows="3" required
                                  placeholder="e.g., Stock count discrepancy, damaged items..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning px-4">
                        <i class="bi bi-check-circle"></i> Adjust Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
function clearFilters() {
    document.getElementById('searchProduct').value = '';
    document.getElementById('stockFilter').value = 'all';
    document.getElementById('brandFilter').value = 'all';
    filterAndSort();
}

function filterAndSort() {
    const table  = document.getElementById('inventoryTable');
    const rows   = Array.from(table.querySelectorAll('tbody tr'));
    const search = document.getElementById('searchProduct').value.toLowerCase();
    const stock  = document.getElementById('stockFilter').value;
    const brand  = document.getElementById('brandFilter').value;

    let anyVisible = false;

    rows.forEach(row => {
        if (!row.dataset.name) return;

        const qty      = parseInt(row.dataset.stock);
        const rowBrand = row.dataset.brand;

        let show = true;
        if (search && !row.dataset.name.includes(search))    show = false;
        if (stock === 'low'       && qty > 5)                show = false;
        if (stock === 'out'       && qty !== 0)              show = false;
        if (stock === 'available' && qty === 0)              show = false;
        if (brand !== 'all'       && rowBrand !== brand)     show = false;

        row.style.display = show ? '' : 'none';
        if (show) anyVisible = true;
    });

    let noResults = document.getElementById('noResultsRow');
    if (!anyVisible) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            table.querySelector('tbody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('searchProduct').addEventListener('input', filterAndSort);
    document.getElementById('stockFilter').addEventListener('change', filterAndSort);
    document.getElementById('brandFilter').addEventListener('change', filterAndSort);
});
</script>
@endpush

@endsection