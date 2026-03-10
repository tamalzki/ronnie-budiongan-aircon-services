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

    {{-- Search & Filters (STICKY) --}}
    <div class="card border-0 shadow-sm mb-3 sticky-top" style="top:0;z-index:1020;">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchProduct" class="form-control border-start-0"
                               placeholder="Search model, description...">
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

    {{-- Table - Compact & Full Width --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height:calc(100vh - 380px);overflow-y:auto;">
                <table class="table table-hover mb-0" id="inventoryTable" style="font-size:0.8rem;">
                    <thead class="bg-light" style="position:sticky;top:0;z-index:10;">
                        <tr>
                            <th class="border-0 px-2 py-2 bg-light" style="min-width:180px;">Model</th>
                            <th class="border-0 px-2 py-2 bg-light" style="width:80px;">Brand</th>
                            <th class="border-0 px-2 py-2 bg-light" style="width:70px;">Type</th>
                            <th class="border-0 px-2 py-2 bg-light" style="width:100px;">Supplier</th>
                            <th class="border-0 px-2 py-2 bg-light text-end" style="width:90px;">Price</th>
                            <th class="border-0 px-2 py-2 bg-light text-center" style="width:100px;">Stock</th>
                            <th class="border-0 px-2 py-2 bg-light text-end" style="width:100px;">Value</th>
                            <th class="border-0 px-2 py-2 bg-light text-center" style="width:60px;">Moves</th>
                            <th class="border-0 px-2 py-2 bg-light text-center" style="width:90px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryTableBody">
                        @forelse($products as $product)
                        @php
                            $rowClass = '';
                            if ($product->stock_count == 0)     $rowClass = 'table-danger';
                            elseif ($product->stock_count <= 5)  $rowClass = 'table-warning';
                        @endphp
                        <tr class="{{ $rowClass }}"
                            data-stock="{{ $product->stock_count }}"
                            data-brand="{{ $product->brand_id ?? 'none' }}"
                            data-name="{{ strtolower(($product->model ?? '') . ' ' . ($product->description ?? '')) }}">
                            
                            {{-- Model - Main Title --}}
                            <td class="px-2 py-2">
                                <div class="fw-bold" style="font-size:0.85rem;">{{ $product->model ?? $product->name }}</div>
                                @if($product->description)
                                    <small class="text-muted d-block" style="font-size:0.7rem;line-height:1.2;">{{ Str::limit($product->description, 50) }}</small>
                                @endif
                            </td>
                            
                            {{-- Brand - Compact Badge --}}
                            <td class="px-2 py-2">
                                @if($product->brand)
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary" style="font-size:0.7rem;">
                                        {{ $product->brand->name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            
                            {{-- Unit Type - Plain Text --}}
                            <td class="px-2 py-2">
                                <small class="text-muted">{{ ucfirst($product->unit_type ?? '—') }}</small>
                            </td>
                            
                            {{-- Supplier - Compact --}}
                            <td class="px-2 py-2">
                                @if($product->supplier)
                                    <small class="text-muted">{{ Str::limit($product->supplier->name, 15) }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            
                            {{-- Price - Right Aligned --}}
                            <td class="px-2 py-2 text-end fw-semibold">
                                <small>₱{{ number_format($product->price, 0) }}</small>
                            </td>
                            
                            {{-- Stock - Compact Badges --}}
                            <td class="px-2 py-2 text-center">
                                @if($product->stock_count == 0)
                                    <span class="badge bg-danger" style="font-size:0.7rem;">
                                        <i class="bi bi-x"></i> 0
                                    </span>
                                @elseif($product->stock_count <= 5)
                                    <span class="badge bg-warning text-dark" style="font-size:0.7rem;">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $product->stock_count }}
                                    </span>
                                @else
                                    <span class="badge bg-success" style="font-size:0.7rem;">
                                        {{ $product->stock_count }}
                                    </span>
                                @endif
                            </td>
                            
                            {{-- Value - Right Aligned --}}
                            <td class="px-2 py-2 text-end text-success fw-semibold">
                                <small>₱{{ number_format($product->stock_count * $product->price, 0) }}</small>
                            </td>
                            
                            {{-- Movements Count --}}
                            <td class="px-2 py-2 text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:0.7rem;">
                                    {{ $product->inventory_movements_count }}
                                </span>
                            </td>
                            
                            {{-- Actions - Compact Button --}}
                            <td class="px-2 py-2 text-center">
                                <a href="{{ route('inventory.show', $product) }}"
                                   class="btn btn-primary btn-sm"
                                   style="padding:2px 6px;font-size:0.7rem;"
                                   title="Manage">
                                    <i class="bi bi-box-arrow-in-right"></i> Manage
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
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
            noResults.innerHTML = '<td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
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