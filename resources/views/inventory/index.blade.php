@extends('layouts.app')

@section('title', 'Inventory Management')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="bi bi-boxes text-primary"></i> Inventory Management</h2>
        <p class="text-muted mb-0">Track stock levels and movements</p>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-box-seam fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Products</h6>
                            <h3 class="mb-0">{{ $totalProducts }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 p-3 rounded">
                                <i class="bi bi-exclamation-triangle fs-2 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Low Stock</h6>
                            <h3 class="mb-0 text-warning">{{ $lowStock }}</h3>
                            <small class="text-muted">≤ 5 units</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="bi bi-x-circle fs-2 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Out of Stock</h6>
                            <h3 class="mb-0 text-danger">{{ $outOfStock }}</h3>
                            <small class="text-muted">0 units</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-currency-dollar fs-2 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Value</h6>
                            <h3 class="mb-0 text-success">₱{{ number_format($totalValue, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Filter by Stock Level</label>
                    <select class="form-select" id="stockFilter">
                        <option value="all">All Products</option>
                        <option value="low">Low Stock (≤5)</option>
                        <option value="out">Out of Stock</option>
                        <option value="available">In Stock</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Search Product</label>
                    <input type="text" class="form-control" id="searchProduct" placeholder="Search by name...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Filter by Brand</label>
                    <select class="form-select" id="brandFilter">
                        <option value="all">All Brands</option>
                        @foreach($products->pluck('brand')->unique()->filter() as $brand)
                            <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Sort By</label>
                    <select class="form-select" id="sortBy">
                        <option value="name">Name (A-Z)</option>
                        <option value="stock_low">Stock (Low to High)</option>
                        <option value="stock_high">Stock (High to Low)</option>
                        <option value="value">Total Value</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Products List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="inventoryTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-4 py-3">Product</th>
                            <th class="border-0 px-4 py-3">Brand</th>
                            <th class="border-0 px-4 py-3">Supplier</th>
                            <th class="border-0 px-4 py-3">Price</th>
                            <th class="border-0 px-4 py-3">Stock</th>
                            <th class="border-0 px-4 py-3">Value</th>
                            <th class="border-0 px-4 py-3">Movements</th>
                            <th class="border-0 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                        <tr data-stock="{{ $product->stock_quantity }}" data-brand="{{ $product->brand_id ?? 'none' }}">
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 p-2 rounded me-3">
                                        <i class="bi bi-box-seam fs-5 text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $product->name }}</div>
                                        @if($product->model)
                                        <small class="text-muted">Model: {{ $product->model }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($product->brand)
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                                    {{ $product->brand->name }}
                                </span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($product->supplier)
                                <small class="text-muted">{{ $product->supplier->name }}</small>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <strong>₱{{ number_format($product->price, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3">
                                @if($product->stock_quantity == 0)
                                    <span class="badge bg-danger px-3 py-2">
                                        <i class="bi bi-x-circle"></i> Out of Stock
                                    </span>
                                @elseif($product->stock_quantity <= 5)
                                    <span class="badge bg-warning px-3 py-2">
                                        <i class="bi bi-exclamation-triangle"></i> {{ $product->stock_quantity }} units
                                    </span>
                                @else
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="bi bi-check-circle"></i> {{ $product->stock_quantity }} units
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <strong class="text-success">₱{{ number_format($product->stock_quantity * $product->price, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary">
                                    {{ $product->inventory_movements_count }} movements
                                </span>
                            </td>
                            <td class="px-4 py-3">
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.show', $product) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-box-arrow-in-right"></i> Manage
        </a>
        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#adjustModal{{ $product->id }}">
            <i class="bi bi-gear"></i> Adjust
        </button>
    </div>
</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <p class="mb-0">No products in inventory</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Adjustment Modals -->
@foreach($products as $product)
<div class="modal fade" id="adjustModal{{ $product->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.adjust', $product) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning border-0">
                    <h5 class="modal-title"><i class="bi bi-gear"></i> Adjust Inventory - {{ $product->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-4">
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>

                    <div class="mb-3">
                        <label for="quantity{{ $product->id }}" class="form-label fw-semibold">New Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" id="quantity{{ $product->id }}" name="quantity" 
                               value="{{ $product->stock_quantity }}" min="0" required>
                        <small class="text-muted">Enter the new total stock quantity</small>
                    </div>

                    <div class="mb-3">
                        <label for="notes{{ $product->id }}" class="form-label fw-semibold">Reason for Adjustment <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="notes{{ $product->id }}" name="notes" rows="3" required placeholder="e.g., Stock count discrepancy, damaged items, etc."></textarea>
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
document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('inventoryTable');
    const stockFilter = document.getElementById('stockFilter');
    const searchProduct = document.getElementById('searchProduct');
    const brandFilter = document.getElementById('brandFilter');
    const sortBy = document.getElementById('sortBy');
    
    function filterAndSort() {
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const searchTerm = searchProduct.value.toLowerCase();
        const stockFilterValue = stockFilter.value;
        const brandFilterValue = brandFilter.value;
        
        rows.forEach(row => {
            if (row.cells.length === 1) return; // Skip empty state row
            
            const productName = row.cells[0].textContent.toLowerCase();
            const stock = parseInt(row.getAttribute('data-stock'));
            const brand = row.getAttribute('data-brand');
            
            let showRow = true;
            
            // Search filter
            if (searchTerm && !productName.includes(searchTerm)) {
                showRow = false;
            }
            
            // Stock filter
            if (stockFilterValue === 'low' && stock > 5) {
                showRow = false;
            } else if (stockFilterValue === 'out' && stock !== 0) {
                showRow = false;
            } else if (stockFilterValue === 'available' && stock === 0) {
                showRow = false;
            }
            
            // Brand filter
            if (brandFilterValue !== 'all' && brand !== brandFilterValue) {
                showRow = false;
            }
            
            row.style.display = showRow ? '' : 'none';
        });
        
        // Sort
        const visibleRows = rows.filter(row => row.style.display !== 'none' && row.cells.length > 1);
        const sortValue = sortBy.value;
        
        visibleRows.sort((a, b) => {
            if (sortValue === 'name') {
                return a.cells[0].textContent.localeCompare(b.cells[0].textContent);
            } else if (sortValue === 'stock_low') {
                return parseInt(a.getAttribute('data-stock')) - parseInt(b.getAttribute('data-stock'));
            } else if (sortValue === 'stock_high') {
                return parseInt(b.getAttribute('data-stock')) - parseInt(a.getAttribute('data-stock'));
            }
            return 0;
        });
        
        const tbody = table.querySelector('tbody');
        visibleRows.forEach(row => tbody.appendChild(row));
    }
    
    stockFilter.addEventListener('change', filterAndSort);
    searchProduct.addEventListener('input', filterAndSort);
    brandFilter.addEventListener('change', filterAndSort);
    sortBy.addEventListener('change', filterAndSort);
});
</script>
@endpush
@endsection