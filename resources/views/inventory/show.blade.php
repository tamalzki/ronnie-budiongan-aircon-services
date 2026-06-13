@extends('layouts.app')

@section('title', 'Inventory - ' . $product->name)

@section('content')
<div class="container-fluid">

    <x-page-header title="{{ $product->name }}" subtitle="Inventory &amp; stock movements" icon="bi-box-seam">
        <x-slot name="actions">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    @php
        $stockColor = $product->stock_count == 0 ? 'danger' : ($product->stock_count <= 5 ? 'warning' : 'success');
        $stockLabel = $product->stock_count == 0 ? 'Out of Stock' : ($product->stock_count <= 5 ? 'Low Stock' : 'In Stock');
    @endphp

    {{-- ═══════════════════════════════════════════════════
         Summary Stats
    ════════════════════════════════════════════════════ --}}
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-{{ $stockColor }} bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-box-seam text-{{ $stockColor }}"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Current Stock</div>
                        <div class="fw-bold text-{{ $stockColor }}" style="font-size:1.05rem;line-height:1.2;">{{ $product->stock_count }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-cash-stack text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Stock Value</div>
                        <div class="fw-bold text-success" style="font-size:1.05rem;line-height:1.2;">₱{{ number_format($product->stock_count * $product->price, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-box-arrow-in-down text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Stock In</div>
                        <div class="fw-bold" style="font-size:1.05rem;line-height:1.2;">{{ $totalStockIn }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-box-arrow-up text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Sold</div>
                        <div class="fw-bold" style="font-size:1.05rem;line-height:1.2;">{{ $totalStockOut }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-arrow-return-left text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Returns</div>
                        <div class="fw-bold" style="font-size:1.05rem;line-height:1.2;">{{ $totalReturns }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:34px;height:34px;">
                        <i class="bi bi-gear text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.7rem;line-height:1.1;">Adjustments</div>
                        <div class="fw-bold" style="font-size:1.05rem;line-height:1.2;">{{ $totalAdjustments }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         Product Info + Quick Actions (anchor #stock-in opens Stock In modal from listing pages)
    ════════════════════════════════════════════════════ --}}
    <div class="row g-2 mb-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-2 px-3">
                    <span class="fw-semibold" style="font-size:0.85rem;"><i class="bi bi-info-circle text-primary me-1"></i>Product Information</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0" style="font-size:0.82rem;">
                        <tr>
                            <th class="px-3 py-1 text-muted" width="90">Brand</th>
                            <td class="px-3 py-1">
                                @if($product->brand)
                                    <span class="badge bg-secondary">{{ $product->brand->name }}</span>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="px-3 py-1 text-muted">Model</th>
                            <td class="px-3 py-1">{{ $product->model ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="px-3 py-1 text-muted">Supplier</th>
                            <td class="px-3 py-1">{{ $product->supplier->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="px-3 py-1 text-muted">Price</th>
                            <td class="px-3 py-1 fw-semibold text-success">₱{{ number_format($product->price, 2) }}</td>
                        </tr>
                        @if($pairedProduct)
                        <tr>
                            <th class="px-3 py-1 text-muted">Set Pair</th>
                            <td class="px-3 py-1">
                                <a href="{{ route('inventory.show', $pairedProduct) }}" class="text-decoration-none">
                                    {{ ucfirst($pairedProduct->unit_type) }}: {{ $pairedProduct->brand->name ?? '' }} {{ $pairedProduct->model }}
                                </a>
                                <div class="text-muted" style="font-size:0.72rem;">Sold as one set with one price — stock is tracked separately for each unit.</div>
                            </td>
                        </tr>
                        @endif
                        @if($product->description)
                        <tr>
                            <th class="px-3 py-1 text-muted">Notes</th>
                            <td class="px-3 py-1 text-muted small">{{ $product->description }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4" id="stock-in" style="scroll-margin-top: 1rem;">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-2 px-3 d-flex align-items-center justify-content-between">
                    <span class="fw-semibold" style="font-size:0.85rem;"><i class="bi bi-lightning-fill text-warning me-1"></i>Quick Actions</span>
                    <span class="badge bg-{{ $stockColor }}">{{ $stockLabel }}</span>
                </div>
                <div class="card-body py-2 px-3">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#stockInModal">
                            <i class="bi bi-plus-circle"></i> Add Stock (Stock In)
                        </button>
                        <button class="btn btn-outline-danger btn-sm" disabled>
                            <i class="bi bi-arrow-return-left"></i> Return to Supplier (Disabled)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         Serial Numbers
    ════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-upc-scan text-primary me-1"></i>Serial Numbers
                <span class="badge bg-secondary ms-1">{{ $serials->count() }}</span>
            </h6>
            <div class="d-flex flex-wrap gap-1">
                <button class="btn btn-outline-secondary btn-sm sn-filter-btn active" data-filter="all" onclick="filterSN('all')">
                    All <span class="badge bg-secondary ms-1">{{ $serials->count() }}</span>
                </button>
                @if($serialCounts['in_stock'] > 0)
                <button class="btn btn-outline-success btn-sm sn-filter-btn" data-filter="in_stock" onclick="filterSN('in_stock')">
                    In Stock <span class="badge bg-success ms-1">{{ $serialCounts['in_stock'] }}</span>
                </button>
                @endif
                @if($serialCounts['pending'] > 0)
                <button class="btn btn-outline-warning btn-sm sn-filter-btn text-dark" data-filter="pending" onclick="filterSN('pending')">
                    Pending <span class="badge bg-warning text-dark ms-1">{{ $serialCounts['pending'] }}</span>
                </button>
                @endif
                @if($serialCounts['sold'] > 0)
                <button class="btn btn-outline-primary btn-sm sn-filter-btn" data-filter="sold" onclick="filterSN('sold')">
                    Sold <span class="badge bg-primary ms-1">{{ $serialCounts['sold'] }}</span>
                </button>
                @endif
                @if($serialCounts['defective'] + $serialCounts['lost'] > 0)
                <button class="btn btn-outline-danger btn-sm sn-filter-btn" data-filter="defective" onclick="filterSN('defective')">
                    Defective/Lost <span class="badge bg-danger ms-1">{{ $serialCounts['defective'] + $serialCounts['lost'] }}</span>
                </button>
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if($serials->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover table-sm table-bordered align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-2 py-2 text-center">#</th>
                            <th class="px-2 py-2">Serial Number</th>
                            <th class="px-2 py-2 text-center">Status</th>
                            <th class="px-2 py-2">Purchase Order</th>
                            <th class="px-2 py-2">Received</th>
                            <th class="px-2 py-2">Sale / Customer</th>
                        </tr>
                    </thead>
                    <tbody id="snTableBody">
                        @foreach($serials as $serial)
                        @php
                            $snCfg = [
                                'in_stock'  => ['badge'=>'bg-success',          'label'=>'In Stock'],
                                'pending'   => ['badge'=>'bg-warning text-dark', 'label'=>'Pending'],
                                'sold'      => ['badge'=>'bg-primary',           'label'=>'Sold'],
                                'returned'  => ['badge'=>'bg-secondary',         'label'=>'Returned'],
                                'defective' => ['badge'=>'bg-danger',            'label'=>'Defective'],
                                'lost'      => ['badge'=>'bg-secondary',         'label'=>'Lost'],
                            ];
                            $cfg = $snCfg[$serial->status] ?? $snCfg['lost'];
                            $snFilter = in_array($serial->status, ['defective','lost']) ? 'defective' : $serial->status;
                        @endphp
                        <tr class="sn-row" data-status="{{ $snFilter }}">
                            <td class="px-2 py-2 text-center text-muted">{{ $loop->iteration }}</td>
                            <td class="px-2 py-2">
                                <code class="fw-semibold" style="letter-spacing:0.03em;">
                                    {{ $serial->serial_number }}
                                </code>
                            </td>
                            <td class="px-2 py-2 text-center">
                                <span class="badge {{ $cfg['badge'] }}">{{ $cfg['label'] }}</span>
                            </td>
                            <td class="px-2 py-2" style="white-space:nowrap">
                                @if($serial->purchaseOrder)
                                    <a href="{{ route('purchase-orders.show', $serial->purchaseOrder) }}"
                                       class="text-decoration-none text-primary fw-semibold" style="font-size:0.82rem;">
                                        <i class="bi bi-cart-plus me-1"></i>{{ $serial->purchaseOrder->display_po_number }}
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-2" style="white-space:nowrap">
                                @if($serial->received_date)
                                    <small>{{ \Carbon\Carbon::parse($serial->received_date)->format('M d, Y') }}</small>
                                @else
                                    <small class="text-muted">—</small>
                                @endif
                            </td>
                            <td class="px-2 py-2">
                                @if($serial->sale)
                                    <a href="{{ route('sales.show', $serial->sale) }}"
                                       class="text-decoration-none text-success fw-semibold" style="font-size:0.82rem;">
                                        <i class="bi bi-receipt me-1"></i>{{ $serial->sale->invoice_number }}
                                    </a>
                                    <div class="text-muted" style="font-size:0.72rem;">{{ $serial->sale->customer_name }}</div>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-3 py-2 border-top bg-light d-flex justify-content-between align-items-center">
                <small class="text-muted"><span id="snVisibleCount">{{ $serials->count() }}</span> of {{ $serials->count() }} serials shown</small>
                <small class="text-muted">Total units ever: <strong>{{ $serials->count() }}</strong></small>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-upc fs-1 d-block mb-2 opacity-50"></i>
                <p class="mb-1">No serial numbers recorded yet.</p>
                <small>Serials are added when creating or receiving a Purchase Order.</small>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         Movement History (full width)
    ════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2 px-3">
            <h6 class="mb-0 fw-semibold"><i class="bi bi-clock-history text-primary"></i> Movement History</h6>
            <small class="text-muted">{{ $movements->count() }} record(s)</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm table-bordered align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-2 py-2" style="white-space:nowrap">Date & Time</th>
                            <th class="px-2 py-2 text-center">Type</th>
                            <th class="px-2 py-2 text-center">Qty</th>
                            <th class="px-2 py-2 text-center">Before</th>
                            <th class="px-2 py-2 text-center">After</th>
                            <th class="px-2 py-2">Reference</th>
                            <th class="px-2 py-2">Notes</th>
                            <th class="px-2 py-2">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        <tr>
                            <td class="px-2 py-2" style="white-space:nowrap">
                                <div>{{ $movement->created_at->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $movement->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="px-2 py-2 text-center" style="white-space:nowrap">
                                @if($movement->type == 'stock_in')
                                    <span class="badge bg-success"><i class="bi bi-box-arrow-in-down"></i> Stock In</span>
                                @elseif($movement->type == 'stock_out')
                                    <span class="badge bg-danger"><i class="bi bi-box-arrow-up"></i> Stock Out</span>
                                @elseif($movement->type == 'return')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-arrow-return-left"></i> Return</span>
                                @else
                                    <span class="badge bg-info text-dark"><i class="bi bi-gear"></i> Adjustment</span>
                                @endif
                            </td>
                            <td class="px-2 py-2 text-center fw-bold" style="white-space:nowrap">
                                <span class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </td>
                            <td class="px-2 py-2 text-center text-muted" style="white-space:nowrap">
                                {{ $movement->stock_before }}
                            </td>
                            <td class="px-2 py-2 text-center fw-semibold" style="white-space:nowrap">
                                {{ $movement->stock_after }}
                            </td>
                            <td class="px-2 py-2" style="white-space:nowrap">
                                <small class="text-muted">{{ $movement->reference_type ?? '—' }}</small>
                            </td>
                            <td class="px-2 py-2">
                                <small class="text-muted">{{ $movement->notes ?? '—' }}</small>
                            </td>
                            <td class="px-2 py-2" style="white-space:nowrap">
                                <small class="text-muted">{{ $movement->user->name ?? '—' }}</small>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No movement history yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Stock In Modal --}}
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.stock-in', $product) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Add Stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-3">
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $product->stock_count }} units</strong>
                    </div>
                    @if($pairedProduct)
                    <div class="alert alert-secondary border-0 mb-3">
                        <i class="bi bi-link-45deg"></i> This unit is part of a set with
                        <strong>{{ $pairedProduct->brand->name ?? '' }} {{ $pairedProduct->model }}</strong>
                        ({{ ucfirst($pairedProduct->unit_type) }}, currently {{ $pairedProduct->stock_count }} units).
                        Enter serials for both units below — inventory stays separate, but they're priced as one set.
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number"
                            class="form-control"
                            id="stockInQuantity"
                            min="1"
                            required
                            placeholder="Enter quantity">
                    </div>
                        <div id="serialInputsContainer" class="mt-3"></div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Cost Per Unit (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" name="cost_per_unit" placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Add Stock
                    </button>
                    @if($product->stock_count > 0 && $product->inStockSerials()->count() == 0)
                    <button class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#encodeSerialsModal">
                        <i class="bi bi-upc-scan"></i> Encode Existing Serials
                    </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>



{{-- Return to Supplier Modal --}}
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.return', $product) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-arrow-return-left"></i> Return to Supplier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 mb-3">
                        <i class="bi bi-exclamation-triangle"></i> Current stock: <strong>{{ $product->stock_count }} units</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Return <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity"
                               min="1" max="{{ $product->stock_count }}" required placeholder="Enter quantity">
                        <small class="text-muted">Maximum: {{ $product->stock_count }} units</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Return to Supplier (Optional)</label>
                        <select class="form-select" name="supplier_id">
                            <option value="">— Select Supplier —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $product->supplier_id == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason for Return <span class="text-danger">*</span></label>
                        <select class="form-select" name="reason" required>
                            <option value="">— Select Reason —</option>
                            <option value="Defective/Damaged">Defective / Damaged</option>
                            <option value="Wrong Item">Wrong Item</option>
                            <option value="Overstocked">Overstocked</option>
                            <option value="Quality Issues">Quality Issues</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Additional Notes</label>
                        <textarea class="form-control" name="notes" rows="2"
                                  placeholder="Describe the issue or provide additional details"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4">
                        <i class="bi bi-check-circle"></i> Process Return
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function filterSN(status) {
    const rows    = document.querySelectorAll('.sn-row');
    const btns    = document.querySelectorAll('.sn-filter-btn');
    let   visible = 0;

    rows.forEach(row => {
        const show = status === 'all' || row.dataset.status === status;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    btns.forEach(btn => {
        const isActive = btn.dataset.filter === status;
        btn.classList.toggle('active', isActive);
        btn.style.boxShadow = isActive ? '0 0 0 2px #4F46E5' : '';
    });

    const countEl = document.getElementById('snVisibleCount');
    if (countEl) countEl.textContent = visible;
}

document.addEventListener('DOMContentLoaded', function () {

    if (window.location.hash === '#stock-in') {
        const stockModal = document.getElementById('stockInModal');
        if (stockModal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(stockModal).show();
        }
    }

    const quantityInput = document.getElementById('stockInQuantity');
    const container = document.getElementById('serialInputsContainer');

    quantityInput.addEventListener('input', function () {

        const quantity = parseInt(this.value) || 0;
        container.innerHTML = '';

        if (quantity > 0) {

            @if($pairedProduct)
            const indoorName  = '{{ $product->unit_type === "indoor" ? "serial_numbers" : "paired_serial_numbers" }}[]';
            const outdoorName = '{{ $product->unit_type === "outdoor" ? "serial_numbers" : "paired_serial_numbers" }}[]';

            const label = document.createElement('label');
            label.className = 'form-label fw-semibold';
            label.innerText = 'Enter Serial Numbers (Indoor & Outdoor)';
            container.appendChild(label);

            for (let i = 0; i < quantity; i++) {
                const row = document.createElement('div');
                row.className = 'row g-2 mb-2';

                const indoorCol = document.createElement('div');
                indoorCol.className = 'col-6';
                const indoorLabel = document.createElement('div');
                indoorLabel.className = 'form-text mb-1';
                indoorLabel.innerText = 'Indoor';
                const indoorInput = document.createElement('input');
                indoorInput.type = 'text';
                indoorInput.name = indoorName;
                indoorInput.className = 'form-control';
                indoorInput.placeholder = 'Indoor Serial #' + (i + 1);
                indoorInput.required = true;
                indoorCol.appendChild(indoorLabel);
                indoorCol.appendChild(indoorInput);

                const outdoorCol = document.createElement('div');
                outdoorCol.className = 'col-6';
                const outdoorLabel = document.createElement('div');
                outdoorLabel.className = 'form-text mb-1';
                outdoorLabel.innerText = 'Outdoor';
                const outdoorInput = document.createElement('input');
                outdoorInput.type = 'text';
                outdoorInput.name = outdoorName;
                outdoorInput.className = 'form-control';
                outdoorInput.placeholder = 'Outdoor Serial #' + (i + 1);
                outdoorInput.required = true;
                outdoorCol.appendChild(outdoorLabel);
                outdoorCol.appendChild(outdoorInput);

                row.appendChild(indoorCol);
                row.appendChild(outdoorCol);
                container.appendChild(row);
            }
            @else
            const label = document.createElement('label');
            label.className = 'form-label fw-semibold';
            label.innerText = 'Enter Serial Numbers';
            container.appendChild(label);

            for (let i = 0; i < quantity; i++) {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = 'serial_numbers[]';
                input.className = 'form-control mb-2';
                input.placeholder = 'Serial #' + (i + 1);
                input.required = true;

                container.appendChild(input);
            }
            @endif
        }
    });

});
</script>

@endsection
