@extends('layouts.app')
@section('title', 'Products')
@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-box-seam text-primary"></i> Products</h2>
            <p class="text-muted mb-0">Manage your product catalog</p>
        </div>
        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-circle"></i> Add Product
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($noPriceCount > 0)
    <div class="alert alert-warning border-0 shadow-sm mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-lock-fill fs-5 text-warning flex-shrink-0"></i>
        <div>
            <strong>{{ $noPriceCount }} product(s) have no selling price</strong> — set price below before they can be sold.
        </div>
    </div>
    @endif

    {{-- Search & Filters --}}
    <div class="card border-0 shadow-sm mb-3 sticky-top" style="top:0;z-index:1020;">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="productSearch" class="form-control border-start-0"
                               placeholder="Search brand, model, supplier...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="stockFilter" class="form-select form-select-sm">
                        <option value="">All Stock</option>
                        <option value="out">Out of Stock</option>
                        <option value="low">Low Stock</option>
                        <option value="in">In Stock</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="priceFilter" class="form-select form-select-sm">
                        <option value="">All Prices</option>
                        <option value="noprice">No Price Set</option>
                        <option value="priced">Price Set</option>
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
            <div class="table-responsive" style="max-height:calc(100vh - 280px);overflow-y:auto;">
                <table class="table table-hover table-sm mb-0" id="productsTable" style="font-size:0.875rem;">
                    <thead class="bg-light" style="position:sticky;top:0;z-index:10;">
                        <tr>
                            <th class="border-0 px-3 py-2 bg-light">Brand</th>
                            <th class="border-0 px-3 py-2 bg-light">Model</th>
                            <th class="border-0 px-3 py-2 bg-light">Unit Type</th>
                            <th class="border-0 px-3 py-2 bg-light">Supplier</th>
                            <th class="border-0 px-3 py-2 bg-light" style="white-space:nowrap">Cost (PO)</th>
                            <th class="border-0 px-3 py-2 bg-light" style="white-space:nowrap">Selling Price</th>
                            <th class="border-0 px-3 py-2 bg-light">Profit</th>
                            <th class="border-0 px-3 py-2 bg-light">
                                <i class="bi bi-upc-scan me-1"></i>Inventory
                            </th>
                            <th class="border-0 px-3 py-2 bg-light">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        @forelse($products as $product)
                        @php
                            $canSell    = $product->price > 0;
                            $profit     = $product->price - $product->cost;
                            $profitPct  = $product->cost > 0 ? (($profit / $product->cost) * 100) : 0;
                            $inStock    = $product->in_stock_count ?? 0;
                            $pending    = $product->pending_count  ?? 0;
                            $stockLevel = $inStock == 0 ? 'out' : ($inStock <= 5 ? 'low' : 'in');
                        @endphp
                        <tr class="{{ !$canSell ? 'table-warning' : '' }} product-row"
                            data-search="{{ strtolower(($product->brand->name ?? '') . ' ' . ($product->model ?? '') . ' ' . ($product->supplier->name ?? '') . ' ' . ($product->unit_type ?? '')) }}"
                            data-stock="{{ $stockLevel }}"
                            data-price="{{ $canSell ? 'priced' : 'noprice' }}">

                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                    {{ $product->brand->name ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                <a href="{{ route('products.show', $product) }}" class="text-decoration-none text-dark">
                                    {{ $product->model ?? '—' }}
                                </a>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($product->unit_type === 'indoor')
                                    <span style="font-size:0.75rem;padding:2px 8px;border-radius:20px;background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;font-weight:600;">❄️ Indoor</span>
                                @elseif($product->unit_type === 'outdoor')
                                    <span style="font-size:0.75rem;padding:2px 8px;border-radius:20px;background:#dcfce7;color:#166534;border:1px solid #86efac;font-weight:600;">🌀 Outdoor</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <small class="text-muted">{{ $product->supplier->name ?? '—' }}</small>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($product->cost > 0)
                                    <span class="text-danger fw-semibold">₱{{ number_format($product->cost, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($canSell)
                                    <span class="text-success fw-semibold">₱{{ number_format($product->price, 2) }}</span>
                                @else
                                    <form action="{{ route('products.set-price', $product) }}" method="POST"
                                          class="d-flex align-items-center gap-1">
                                        @csrf
                                        <div class="input-group input-group-sm" style="width:120px;">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" min="0.01"
                                                   class="form-control" name="price"
                                                   placeholder="0.00" required>
                                        </div>
                                        <button type="submit" class="btn btn-warning btn-sm fw-semibold">Set</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($canSell && $product->cost > 0)
                                    <span class="badge {{ $profit >= 0 ? 'bg-success' : 'bg-danger' }}">
                                        ₱{{ number_format($profit, 2) }} ({{ number_format($profitPct, 1) }}%)
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- ── Inventory / Serial Count column ── --}}
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('products.show', $product) }}"
                                   class="text-decoration-none d-inline-flex align-items-center gap-1"
                                   title="View serial numbers">
                                    @if($inStock === 0)
                                        <span class="badge bg-danger">Out of Stock</span>
                                    @elseif($inStock <= 5)
                                        <span class="badge bg-warning text-dark">{{ $inStock }} in stock</span>
                                    @else
                                        <span class="badge bg-success">{{ $inStock }} in stock</span>
                                    @endif
                                    @if($pending > 0)
                                        <span class="badge bg-secondary" title="{{ $pending }} pending (not yet received)">
                                            +{{ $pending }} pending
                                        </span>
                                    @endif
                                    <i class="bi bi-chevron-right text-muted" style="font-size:0.7rem;"></i>
                                </a>
                            </td>

                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('products.show', $product) }}"
                                       class="btn btn-outline-info btn-sm" style="font-size:0.78rem"
                                       title="View serials">
                                        <i class="bi bi-upc-scan">View Products</i>
                                    </a>
                                    <a href="{{ route('products.edit', $product) }}"
                                       class="btn btn-primary btn-sm" style="font-size:0.78rem">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Delete this product?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                                style="font-size:0.78rem">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No products yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if(isset($products) && method_exists($products, 'hasPages') && $products->hasPages())
        <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-light">
            <small class="text-muted">
                Showing {{ $products->firstItem() }}–{{ $products->lastItem() }}
                of {{ $products->total() }} products
            </small>
            {{ $products->links() }}
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function clearFilters() {
    document.getElementById('productSearch').value = '';
    document.getElementById('stockFilter').value   = '';
    document.getElementById('priceFilter').value   = '';
    filterTable();
}

function filterTable() {
    const search = document.getElementById('productSearch').value.toLowerCase();
    const stock  = document.getElementById('stockFilter').value;
    const price  = document.getElementById('priceFilter').value;
    const rows   = document.querySelectorAll('.product-row');
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchStock  = !stock  || row.dataset.stock  === stock;
        const matchPrice  = !price  || row.dataset.price  === price;

        const show = matchSearch && matchStock && matchPrice;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    let noResults = document.getElementById('noResultsRow');
    if (visible === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('productsTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('productSearch').addEventListener('input', filterTable);
    document.getElementById('stockFilter').addEventListener('change', filterTable);
    document.getElementById('priceFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@endsection