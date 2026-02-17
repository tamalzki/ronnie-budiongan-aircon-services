@extends('layouts.app')

@section('title', 'Inventory - ' . $product->name)

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">{{ $product->name }}</li>
                </ol>
            </nav>
            <h2 class="mb-0"><i class="bi bi-box-seam text-primary"></i> {{ $product->name }}</h2>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════
         ROW 1: Product Info + Stock Status + Actions
    ════════════════════════════════════════════════════ --}}
    <div class="row g-3 mb-4">

        {{-- Product Info --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white border-0 py-2">
                    <h6 class="mb-0"><i class="bi bi-info-circle"></i> Product Information</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0" style="font-size:0.875rem;">
                        <tr>
                            <th class="px-3 py-2 text-muted" width="90">Brand</th>
                            <td class="px-3 py-2">
                                @if($product->brand)
                                    <span class="badge bg-secondary">{{ $product->brand->name }}</span>
                                @else <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th class="px-3 py-2 text-muted">Model</th>
                            <td class="px-3 py-2">{{ $product->model ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="px-3 py-2 text-muted">Supplier</th>
                            <td class="px-3 py-2">{{ $product->supplier->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th class="px-3 py-2 text-muted">Price</th>
                            <td class="px-3 py-2 fw-semibold text-success">₱{{ number_format($product->price, 2) }}</td>
                        </tr>
                        @if($product->description)
                        <tr>
                            <th class="px-3 py-2 text-muted">Notes</th>
                            <td class="px-3 py-2 text-muted small">{{ $product->description }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- Stock Status --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header border-0 py-2"
                     style="background:{{ $product->stock_quantity == 0 ? '#dc3545' : ($product->stock_quantity <= 5 ? '#ffc107' : '#198754') }}">
                    <h6 class="mb-0 text-white"><i class="bi bi-box"></i> Current Stock</h6>
                </div>
                <div class="card-body text-center d-flex flex-column justify-content-center py-4">
                    <div class="fw-bold mb-1" style="font-size:3.5rem;line-height:1;color:{{ $product->stock_quantity == 0 ? '#dc3545' : ($product->stock_quantity <= 5 ? '#e0a800' : '#198754') }}">
                        {{ $product->stock_quantity }}
                    </div>
                    <div class="text-muted mb-3" style="font-size:0.85rem;">units available</div>

                    @if($product->stock_quantity == 0)
                        <span class="badge bg-danger align-self-center px-3 py-2 mb-3">
                            <i class="bi bi-exclamation-triangle"></i> Out of Stock
                        </span>
                    @elseif($product->stock_quantity <= 5)
                        <span class="badge bg-warning text-dark align-self-center px-3 py-2 mb-3">
                            <i class="bi bi-exclamation-circle"></i> Low Stock — Reorder Soon
                        </span>
                    @else
                        <span class="badge bg-success align-self-center px-3 py-2 mb-3">
                            <i class="bi bi-check-circle"></i> In Stock
                        </span>
                    @endif

                    <div class="bg-light rounded p-2">
                        <small class="text-muted d-block">Total Stock Value</small>
                        <strong class="text-success fs-5">₱{{ number_format($product->stock_quantity * $product->price, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions + Stats --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-2">
                    <h6 class="mb-0"><i class="bi bi-lightning-fill text-warning"></i> Quick Actions</h6>
                </div>
                <div class="card-body py-3">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#stockInModal">
                            <i class="bi bi-plus-circle"></i> Add Stock (Stock In)
                        </button>
                        <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#adjustModal">
                            <i class="bi bi-gear"></i> Adjust Inventory
                        </button>
                        <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#returnModal"
                                {{ $product->stock_quantity == 0 ? 'disabled' : '' }}>
                            <i class="bi bi-arrow-return-left"></i> Return to Supplier
                        </button>
                    </div>
                </div>
            </div>

            {{-- Mini stats --}}
            <div class="row g-2">
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center py-2">
                        <div class="text-success fw-bold fs-5">{{ $totalStockIn }}</div>
                        <div class="text-muted" style="font-size:0.72rem;"><i class="bi bi-box-arrow-in-down"></i> Stock In</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center py-2">
                        <div class="text-danger fw-bold fs-5">{{ $totalStockOut }}</div>
                        <div class="text-muted" style="font-size:0.72rem;"><i class="bi bi-box-arrow-up"></i> Sold</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center py-2">
                        <div class="text-warning fw-bold fs-5">{{ $totalReturns }}</div>
                        <div class="text-muted" style="font-size:0.72rem;"><i class="bi bi-arrow-return-left"></i> Returns</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="card border-0 shadow-sm text-center py-2">
                        <div class="text-info fw-bold fs-5">{{ $totalAdjustments }}</div>
                        <div class="text-muted" style="font-size:0.72rem;"><i class="bi bi-gear"></i> Adjustments</div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ═══════════════════════════════════════════════════
         ROW 2: Movement History (full width)
    ════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2">
            <h6 class="mb-0"><i class="bi bi-clock-history text-primary"></i> Movement History</h6>
            <small class="text-muted">{{ $movements->count() }} record(s)</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Date & Time</th>
                            <th class="border-0 px-3 py-2">Type</th>
                            <th class="border-0 px-3 py-2 text-center">Qty</th>
                            <th class="border-0 px-3 py-2 text-center">Before</th>
                            <th class="border-0 px-3 py-2 text-center">After</th>
                            <th class="border-0 px-3 py-2">Reference</th>
                            <th class="border-0 px-3 py-2">Notes</th>
                            <th class="border-0 px-3 py-2">By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($movements as $movement)
                        <tr>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div>{{ $movement->created_at->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $movement->created_at->format('h:i A') }}</small>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
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
                            <td class="px-3 py-2 text-center fw-bold" style="white-space:nowrap">
                                <span class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center text-muted" style="white-space:nowrap">
                                {{ $movement->stock_before }}
                            </td>
                            <td class="px-3 py-2 text-center fw-semibold" style="white-space:nowrap">
                                {{ $movement->stock_after }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <small class="text-muted">{{ $movement->reference_type ?? '—' }}</small>
                            </td>
                            <td class="px-3 py-2">
                                <small class="text-muted">{{ $movement->notes ?? '—' }}</small>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
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
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity" min="1" required placeholder="Enter quantity">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">From Supplier (Optional)</label>
                        <select class="form-select" name="supplier_id">
                            <option value="">— Select Supplier —</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
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
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Adjust Inventory Modal --}}
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('inventory.adjust', $product) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning border-0">
                    <h5 class="modal-title"><i class="bi bi-gear"></i> Adjust Inventory</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-3">
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity"
                               value="{{ $product->stock_quantity }}" min="0" required>
                        <small class="text-muted">Enter the corrected total stock quantity</small>
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
                        <i class="bi bi-exclamation-triangle"></i> Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Return <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="quantity"
                               min="1" max="{{ $product->stock_quantity }}" required placeholder="Enter quantity">
                        <small class="text-muted">Maximum: {{ $product->stock_quantity }} units</small>
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

@endsection
