<div class="report-section app-tab-panel">
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Active Products</div>
                <div class="fw-bold fs-5">{{ $inventorySnapshot->count() }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Units In Stock</div>
                <div class="fw-bold fs-5 text-success">{{ number_format($totalStockUnits) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-light text-center py-3">
                <div class="text-muted small mb-1">Stock Value (Cost)</div>
                <div class="fw-bold fs-5 text-primary">₱{{ number_format($totalStockValue, 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger bg-opacity-10 text-center py-3">
                <div class="text-muted small mb-1">Low / Out of Stock</div>
                <div class="fw-bold fs-5 text-danger">
                    {{ $inventorySnapshot->filter(fn ($p) => $p->in_stock_count <= 2)->count() }}
                </div>
            </div>
        </div>
    </div>

    <h6 class="fw-semibold mb-2"><i class="bi bi-boxes text-primary"></i> Current Stock Levels</h6>
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
            <thead class="bg-light">
                <tr>
                    <th class="border-0 px-3 py-2">Product</th>
                    <th class="border-0 px-3 py-2">Brand</th>
                    <th class="border-0 px-3 py-2 text-center">In Stock</th>
                    <th class="border-0 px-3 py-2 text-center">Pending</th>
                    <th class="border-0 px-3 py-2 text-center">Sold</th>
                    <th class="border-0 px-3 py-2 text-end">Cost/Unit</th>
                    <th class="border-0 px-3 py-2 text-end">Stock Value</th>
                    <th class="border-0 px-3 py-2 text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inventorySnapshot as $product)
                @php $stockVal = $product->in_stock_count * (float) $product->cost; @endphp
                <tr class="{{ $product->in_stock_count == 0 ? 'table-danger' : ($product->in_stock_count <= 2 ? 'table-warning' : '') }}">
                    <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $product->display_model }}</td>
                    <td class="px-3 py-2" style="white-space:nowrap">{{ $product->brand->name ?? '—' }}</td>
                    <td class="px-3 py-2 text-center">
                        <span class="badge {{ $product->in_stock_count == 0 ? 'bg-danger' : ($product->in_stock_count <= 2 ? 'bg-warning text-dark' : 'bg-success') }}">
                            {{ $product->in_stock_count }}
                        </span>
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($product->pending_count > 0)
                            <span class="badge bg-info text-dark">{{ $product->pending_count }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="px-3 py-2 text-center text-muted">{{ $product->sold_count }}</td>
                    <td class="px-3 py-2 text-end" style="white-space:nowrap">₱{{ number_format($product->cost, 2) }}</td>
                    <td class="px-3 py-2 text-end fw-semibold" style="white-space:nowrap">
                        {{ $product->in_stock_count > 0 ? '₱' . number_format($stockVal, 2) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-center">
                        @if($product->in_stock_count == 0)
                            <span class="badge bg-danger">Out of Stock</span>
                        @elseif($product->in_stock_count <= 2)
                            <span class="badge bg-warning text-dark">Low Stock</span>
                        @else
                            <span class="badge bg-success">Available</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="text-center py-4 text-muted">No active products found</td></tr>
                @endforelse
            </tbody>
            @if($inventorySnapshot->count() > 0)
            <tfoot class="bg-light fw-semibold">
                <tr>
                    <td colspan="2" class="px-3 py-2">Total</td>
                    <td class="px-3 py-2 text-center">{{ $totalStockUnits }}</td>
                    <td class="px-3 py-2 text-center">{{ $inventorySnapshot->sum('pending_count') }}</td>
                    <td class="px-3 py-2 text-center">{{ $inventorySnapshot->sum('sold_count') }}</td>
                    <td></td>
                    <td class="px-3 py-2 text-end text-primary">₱{{ number_format($totalStockValue, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
