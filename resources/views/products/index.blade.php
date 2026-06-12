@extends('layouts.app')
@section('title', 'Products & Stock')
@section('content')
<div class="products-page container-fluid px-2 px-lg-3 pb-4">

    <x-page-header title="Products and Stocks" subtitle="Catalog, pricing, and inventory" icon="bi-boxes">
        <x-slot name="actions">
            <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm px-3">
                <i class="bi bi-plus-lg"></i> Add product
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    {{-- Compact metrics --}}
    <div class="products-metrics card border-0 shadow-sm mb-3">
        <div class="card-body py-2 px-0">
            <div class="products-metrics-row">
                <div class="products-metric">
                    <span class="products-metric-label">SKUs</span>
                    <span class="products-metric-value">{{ $totalProducts }}</span>
                </div>
                <div class="products-metric products-metric--out">
                    <span class="products-metric-label">Out</span>
                    <span class="products-metric-value">{{ $outOfStock }}</span>
                </div>
                <div class="products-metric products-metric--low">
                    <span class="products-metric-label">Low 1–5</span>
                    <span class="products-metric-value">{{ $lowStock }}</span>
                </div>
                <div class="products-metric products-metric--medium">
                    <span class="products-metric-label">Med 6–20</span>
                    <span class="products-metric-value">{{ $mediumStock }}</span>
                </div>
                <div class="products-metric products-metric--high">
                    <span class="products-metric-label">High 21+</span>
                    <span class="products-metric-value">{{ $highStock }}</span>
                </div>
                <div class="products-metric products-metric--value">
                    <span class="products-metric-label">Stock value</span>
                    <span class="products-metric-value text-success">₱{{ number_format($totalValue, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    @if($noPriceCount > 0)
    <div class="alert alert-warning border-0 shadow-sm mb-3 py-2 px-3 d-flex align-items-center gap-2 small">
        <i class="bi bi-exclamation-circle flex-shrink-0"></i>
        <span><strong>{{ $noPriceCount }}</strong> product(s) need a selling price before they can be sold.</span>
    </div>
    @endif

    {{-- Toolbar --}}
    <div class="products-toolbar card border-0 shadow-sm mb-2 sticky-top">
        <div class="card-body py-2 px-3">
            <div class="d-flex flex-wrap align-items-stretch align-items-md-center gap-2">
                <div class="flex-grow-1" style="min-width: 12rem;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text border-end-0 bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="productSearch" class="form-control border-start-0"
                               placeholder="Search brand, model, type…">
                    </div>
                </div>
                <select id="stockFilter" class="form-select form-select-sm products-filter-select" title="Quantity band">
                    <option value="">All quantities</option>
                    <option value="out">Out (0)</option>
                    <option value="low">Low (1–5)</option>
                    <option value="medium">Medium (6–20)</option>
                    <option value="high">High (21+)</option>
                </select>
                <select id="priceFilter" class="form-select form-select-sm products-filter-select">
                    <option value="">All prices</option>
                    <option value="noprice">No price</option>
                    <option value="priced">Priced</option>
                </select>
                <select id="brandFilter" class="form-select form-select-sm products-filter-select">
                    <option value="">All brands</option>
                    @foreach($products->pluck('brand')->unique()->filter() as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                <button type="button" class="btn btn-outline-secondary btn-sm px-2" onclick="clearFilters()" title="Reset filters">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm overflow-hidden">
        <div class="table-responsive products-table-scroll">
            <table class="table table-sm table-hover align-middle mb-0 products-table w-100" id="productsTable">
                <thead>
                    <tr>
                        <th scope="col" class="products-th ps-3">Brand</th>
                        <th scope="col" class="products-th">Model</th>
                        <th scope="col" class="products-th">Type</th>
                        <th scope="col" class="products-th text-end">Purchase Cost</th>
                        <th scope="col" class="products-th text-end">Selling Price</th>
                        <th scope="col" class="products-th text-center">Quantity</th>
                        <th scope="col" class="products-th text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody id="productsTableBody">
                    @forelse($products as $product)
                    @php
                        $outdoor = $product->pairedProduct;
                        $isSet   = (bool) $outdoor;
                        $canSell = $product->price > 0;
                        $ownStock     = (int) ($product->in_stock_count ?? 0);
                        $outdoorStock = $isSet ? (int) ($outdoor->in_stock_count ?? 0) : null;
                        $inStock = $isSet ? min($ownStock, $outdoorStock) : $ownStock;
                        $pending = (int) ($product->pending_count ?? 0) + ($isSet ? (int) ($outdoor->pending_count ?? 0) : 0);
                        if ($inStock === 0) {
                            $qtyTier = 'out';
                        } elseif ($inStock <= 5) {
                            $qtyTier = 'low';
                        } elseif ($inStock <= 20) {
                            $qtyTier = 'medium';
                        } else {
                            $qtyTier = 'high';
                        }
                        $hasLinkedData = ($product->sale_items_count ?? 0) > 0
                            || ($product->purchase_order_items_count ?? 0) > 0
                            || ($product->inventory_movements_count ?? 0) > 0
                            || ($product->linked_serials_count ?? 0) > 0;
                    @endphp
                    <tr class="{{ !$canSell ? 'products-row--noprice' : '' }} product-row"
                        data-search="{{ strtolower(($product->brand->name ?? '') . ' ' . ($product->model ?? '') . ' ' . ($isSet ? ($outdoor->model . ' set') : ($product->unit_type ?? ''))) }}"
                        data-qty-tier="{{ $qtyTier }}"
                        data-price="{{ $canSell ? 'priced' : 'noprice' }}"
                        data-brand="{{ $product->brand_id ?? '' }}">

                        <td class="ps-3">
                            <span class="products-brand">{{ $product->brand->name ?? '—' }}</span>
                        </td>
                        <td>
                            <a href="{{ route('inventory.show', $product) }}" class="products-model">{{ $product->model ?? '—' }}</a>
                            @if($isSet)
                                <span class="text-muted"> / </span>
                                <a href="{{ route('inventory.show', $outdoor) }}" class="products-model">{{ $outdoor->model }}</a>
                            @endif
                        </td>
                        <td>
                            @if($isSet)
                                <span class="products-type products-type--set">Set (IDU + ODU)</span>
                            @elseif($product->unit_type === 'indoor')
                                <span class="products-type products-type--in">Indoor</span>
                            @elseif($product->unit_type === 'outdoor')
                                <span class="products-type products-type--out">Outdoor</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end products-num">
                            @if($product->cost > 0)
                                <span class="fw-semibold text-body-secondary">₱{{ number_format($product->cost, 2) }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end products-num">
                            @if($canSell)
                                <span class="text-success fw-semibold">₱{{ number_format($product->price, 2) }}</span>
                            @else
                                <form action="{{ route('products.set-price', $product) }}" method="POST" class="products-price-form">
                                    @csrf
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text py-0">₱</span>
                                        <input type="number" step="0.01" min="0.01" class="form-control py-0" name="price" placeholder="0" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning btn-sm py-0">Set</button>
                                </form>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('inventory.show', $product) }}" class="products-qty-wrap"
                               title="{{ $isSet ? "Complete sets — indoor: {$ownStock}, outdoor: {$outdoorStock}" : 'Open inventory' }}">
                                <span class="products-qty products-qty--{{ $qtyTier }}">{{ $inStock }}</span>
                                @if($isSet)
                                    <span class="products-pending" title="Indoor in stock / Outdoor in stock">{{ $ownStock }} IDU · {{ $outdoorStock }} ODU</span>
                                @endif
                                @if($pending > 0)
                                    <span class="products-pending" title="{{ $pending }} pending (not received)">+{{ $pending }}</span>
                                @endif
                            </a>
                        </td>
                        <td class="text-end pe-3">
                            <div class="products-actions">
                                <a href="{{ route('inventory.show', $product) }}" class="btn btn-light border btn-sm products-act" title="Inventory &amp; serials">
                                    <i class="bi bi-sliders"></i><span>Manage</span>
                                </a>
                                <a href="{{ route('inventory.show', $product) }}#stock-in" class="btn btn-light border btn-sm products-act" title="Stock in">
                                    <i class="bi bi-box-arrow-in-down"></i><span>Stock in</span>
                                </a>
                                <a href="{{ route('products.edit', $product) }}" class="btn btn-light border btn-sm products-act" title="Edit">
                                    <i class="bi bi-pencil"></i><span>Edit</span>
                                </a>
                                @if($hasLinkedData)
                                    <button type="button" class="btn btn-light border btn-sm products-act" disabled title="Linked to sales, POs, or stock">
                                        <i class="bi bi-trash"></i><span>Delete</span>
                                    </button>
                                @else
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="products-act-form"
                                          onsubmit="return confirm('Delete this product? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm products-act" title="Delete">
                                            <i class="bi bi-trash"></i><span>Delete</span>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox d-block fs-3 mb-2 opacity-50"></i>
                            No products yet
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(isset($products) && method_exists($products, 'hasPages') && $products->hasPages())
        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-body-tertiary small">
            <span class="text-muted">Showing {{ $products->firstItem() }}–{{ $products->lastItem() }} of {{ $products->total() }}</span>
            {{ $products->links() }}
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
function clearFilters() {
    document.getElementById('productSearch').value = '';
    document.getElementById('stockFilter').value = '';
    document.getElementById('priceFilter').value = '';
    document.getElementById('brandFilter').value = '';
    filterTable();
}

function filterTable() {
    const search = document.getElementById('productSearch').value.toLowerCase();
    const qtyTier = document.getElementById('stockFilter').value;
    const price = document.getElementById('priceFilter').value;
    const brand = document.getElementById('brandFilter').value;
    const rows = document.querySelectorAll('.product-row');
    let visible = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchQty = !qtyTier || row.dataset.qtyTier === qtyTier;
        const matchPrice = !price || row.dataset.price === price;
        const matchBrand = !brand || row.dataset.brand === brand;
        const show = matchSearch && matchQty && matchPrice && matchBrand;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    let noResults = document.getElementById('noResultsRow');
    if (visible === 0 && rows.length > 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-search d-block fs-3 mb-2 opacity-50"></i>No matches</td>';
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
    document.getElementById('brandFilter').addEventListener('change', filterTable);
});
</script>
@endpush

<style>
.products-page {
    max-width: 100%;
}
.products-metrics-row {
    display: flex;
    flex-wrap: nowrap;
    overflow-x: auto;
    gap: 0;
    -webkit-overflow-scrolling: touch;
}
.products-metric {
    flex: 1 1 0;
    min-width: 5.5rem;
    padding: 0.35rem 0.75rem;
    border-inline-end: 1px solid var(--bs-border-color);
    text-align: center;
}
.products-metric:last-child {
    border-inline-end: none;
}
@media (min-width: 768px) {
    .products-metric {
        text-align: start;
    }
}
.products-metric-label {
    display: block;
    font-size: 0.65rem;
    font-weight: 600;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
    white-space: nowrap;
}
.products-metric-value {
    font-size: 1.05rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
}
.products-metric--out .products-metric-value { color: #b91c1c; }
.products-metric--low .products-metric-value { color: #b45309; }
.products-metric--medium .products-metric-value { color: #1d4ed8; }
.products-metric--high .products-metric-value { color: #15803d; }

.products-toolbar {
    z-index: 1020;
    top: 0;
}
.products-filter-select {
    width: auto;
    min-width: 7.5rem;
    max-width: 11rem;
}

.products-table-scroll {
    max-height: calc(100vh - 280px);
    min-height: 200px;
}
.products-table {
    font-size: 0.8125rem;
    --row-line: color-mix(in srgb, var(--bs-body-color) 7%, transparent);
}
.products-th {
    position: sticky;
    top: 0;
    z-index: 2;
    padding: 0.5rem 0.6rem;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: var(--bs-secondary-color);
    background: var(--bs-body-bg);
    border-bottom: 1px solid var(--bs-border-color);
    box-shadow: 0 1px 0 var(--bs-border-color);
    white-space: nowrap;
}
.products-table tbody td {
    padding: 0.45rem 0.6rem;
    border-bottom: 1px solid var(--row-line);
    vertical-align: middle;
}
.products-table tbody tr:last-child td {
    border-bottom: none;
}
.products-table tbody tr:hover td {
    background-color: rgba(var(--bs-primary-rgb), 0.04);
}
.products-row--noprice td {
    background-color: rgba(255, 193, 7, 0.07);
}
.products-row--noprice:hover td {
    background-color: rgba(255, 193, 7, 0.11);
}

.products-brand {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--bs-secondary-color);
    max-width: 10rem;
    display: inline-block;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: bottom;
}
.products-model {
    font-weight: 600;
    color: var(--bs-body-color);
    text-decoration: none;
    border-bottom: 1px solid transparent;
}
.products-model:hover {
    color: var(--bs-primary);
    border-bottom-color: rgba(var(--bs-primary-rgb), 0.35);
}
.products-type {
    display: inline-block;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    padding: 0.15rem 0.45rem;
    border-radius: 0.25rem;
    white-space: nowrap;
}
.products-type--in {
    color: #1e40af;
    background: #dbeafe;
    border: 1px solid #93c5fd;
}
.products-type--out {
    color: #166534;
    background: #dcfce7;
    border: 1px solid #86efac;
}
.products-type--set {
    color: #5b21b6;
    background: #ede9fe;
    border: 1px solid #c4b5fd;
}
.products-num {
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
}
.products-price-form {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    justify-content: flex-end;
    flex-wrap: nowrap;
    width: 100%;
}
.products-price-form .input-group {
    width: 6.5rem;
    flex-shrink: 0;
}

.products-qty-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    flex-wrap: wrap;
    text-decoration: none;
}
.products-qty {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    padding: 0.2rem 0.5rem;
    font-size: 0.8125rem;
    font-weight: 800;
    font-variant-numeric: tabular-nums;
    border-radius: 0.35rem;
    border: 1px solid transparent;
    line-height: 1.2;
}
.products-qty--out {
    color: #991b1b;
    background: #fef2f2;
    border-color: #fecaca;
}
.products-qty--low {
    color: #9a3412;
    background: #fff7ed;
    border-color: #fed7aa;
}
.products-qty--medium {
    color: #1e40af;
    background: #eff6ff;
    border-color: #bfdbfe;
}
.products-qty--high {
    color: #166534;
    background: #f0fdf4;
    border-color: #bbf7d0;
}
.products-pending {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--bs-secondary-color);
    background: var(--bs-secondary-bg);
    border: 1px solid var(--bs-border-color);
    border-radius: 999px;
    padding: 0.1rem 0.4rem;
}

.products-actions {
    display: inline-flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: flex-end;
    gap: 0.25rem;
}
.products-act-form {
    display: inline-flex;
    margin: 0;
}
.products-act {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.2rem 0.45rem;
    font-size: 0.72rem;
    font-weight: 600;
    white-space: nowrap;
    line-height: 1.3;
    flex-shrink: 0;
}
.products-act i {
    font-size: 0.95rem;
    opacity: 0.85;
}
@media (max-width: 1199.98px) {
    .products-act span {
        display: none;
    }
    .products-act {
        padding: 0.25rem 0.4rem;
    }
}
</style>
@endsection
