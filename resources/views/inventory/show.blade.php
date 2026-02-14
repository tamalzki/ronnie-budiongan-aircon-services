@extends('layouts.app')

@section('title', 'Inventory - ' . $product->name)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="{{ route('inventory.index') }}">Inventory</a></li>
                    <li class="breadcrumb-item active">{{ $product->name }}</li>
                </ol>
            </nav>
            <h2 class="mb-0"><i class="bi bi-box-seam text-primary"></i> {{ $product->name }}</h2>
        </div>
        <div>
            <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Inventory
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <!-- Product Info Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-primary text-white border-0">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Product Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless mb-0">
                        <tr>
                            <th width="120">Brand:</th>
                            <td>
                                @if($product->brand)
                                <span class="badge bg-secondary">{{ $product->brand->name }}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Model:</th>
                            <td>{{ $product->model ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Supplier:</th>
                            <td>{{ $product->supplier->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Price:</th>
                            <td><strong class="text-success">₱{{ number_format($product->price, 2) }}</strong></td>
                        </tr>
                        <tr>
                            <th>Description:</th>
                            <td>{{ $product->description ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Current Stock Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header border-0" 
                     style="background: linear-gradient(135deg, {{ $product->stock_quantity == 0 ? '#dc3545' : ($product->stock_quantity <= 5 ? '#ffc107' : '#198754') }} 0%, {{ $product->stock_quantity == 0 ? '#c82333' : ($product->stock_quantity <= 5 ? '#e0a800' : '#157347') }} 100%);">
                    <h5 class="mb-0 text-white"><i class="bi bi-box"></i> Current Stock</h5>
                </div>
                <div class="card-body text-center">
                    <h1 class="display-3 fw-bold mb-2" style="color: {{ $product->stock_quantity == 0 ? '#dc3545' : ($product->stock_quantity <= 5 ? '#ffc107' : '#198754') }}">
                        {{ $product->stock_quantity }}
                    </h1>
                    <p class="text-muted mb-0">units available</p>
                    
                    @if($product->stock_quantity == 0)
                        <div class="alert alert-danger mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle"></i> Out of Stock
                        </div>
                    @elseif($product->stock_quantity <= 5)
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="bi bi-exclamation-circle"></i> Low Stock Alert
                        </div>
                    @endif

                    <div class="mt-3">
                        <small class="text-muted">Stock Value:</small>
                        <h4 class="mb-0 text-success">₱{{ number_format($product->stock_quantity * $product->price, 2) }}</h4>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0">
                    <h6 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#stockInModal">
                            <i class="bi bi-plus-circle"></i> Add Stock (Stock In)
                        </button>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#adjustModal">
                            <i class="bi bi-gear"></i> Adjust Inventory
                        </button>
                        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#returnModal" {{ $product->stock_quantity == 0 ? 'disabled' : '' }}>
                            <i class="bi bi-arrow-return-left"></i> Return to Supplier
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Movement Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-box-arrow-in-down fs-2 text-success mb-2"></i>
                            <h3 class="mb-0 text-success">{{ $totalStockIn }}</h3>
                            <small class="text-muted">Total Stock In</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-box-arrow-up fs-2 text-danger mb-2"></i>
                            <h3 class="mb-0 text-danger">{{ $totalStockOut }}</h3>
                            <small class="text-muted">Total Sold</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-arrow-return-left fs-2 text-warning mb-2"></i>
                            <h3 class="mb-0 text-warning">{{ $totalReturns }}</h3>
                            <small class="text-muted">Total Returns</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="bi bi-gear fs-2 text-info mb-2"></i>
                            <h3 class="mb-0 text-info">{{ $totalAdjustments }}</h3>
                            <small class="text-muted">Adjustments</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Movement History -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Movement History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4 py-3">Date</th>
                                    <th class="border-0 px-4 py-3">Type</th>
                                    <th class="border-0 px-4 py-3">Quantity</th>
                                    <th class="border-0 px-4 py-3">Before</th>
                                    <th class="border-0 px-4 py-3">After</th>
                                    <th class="border-0 px-4 py-3">Reference</th>
                                    <th class="border-0 px-4 py-3">Notes</th>
                                    <th class="border-0 px-4 py-3">By</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                <tr>
                                    <td class="px-4 py-3">
                                        <small>{{ $movement->created_at->format('M d, Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $movement->created_at->format('h:i A') }}</small>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($movement->type == 'stock_in')
                                            <span class="badge bg-success">
                                                <i class="bi bi-box-arrow-in-down"></i> Stock In
                                            </span>
                                        @elseif($movement->type == 'stock_out')
                                            <span class="badge bg-danger">
                                                <i class="bi bi-box-arrow-up"></i> Stock Out
                                            </span>
                                        @elseif($movement->type == 'return')
                                            <span class="badge bg-warning">
                                                <i class="bi bi-arrow-return-left"></i> Return
                                            </span>
                                        @else
                                            <span class="badge bg-info">
                                                <i class="bi bi-gear"></i> Adjustment
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <strong class="{{ $movement->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                        </strong>
                                    </td>
                                    <td class="px-4 py-3">{{ $movement->stock_before }}</td>
                                    <td class="px-4 py-3"><strong>{{ $movement->stock_after }}</strong></td>
                                    <td class="px-4 py-3">
                                        <small class="text-muted">{{ $movement->reference_type ?? '-' }}</small>
                                    </td>
                                    <td class="px-4 py-3">
                                        <small class="text-muted">{{ $movement->notes ?? '-' }}</small>
                                    </td>
                                    <td class="px-4 py-3">
                                        <small class="text-muted">{{ $movement->user->name }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                            <p class="mb-0">No movement history yet</p>
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
    </div>
</div>

<!-- Stock In Modal -->
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
                    <div class="alert alert-info border-0 mb-4">
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>

                    <div class="mb-3">
                        <label for="stock_in_quantity" class="form-label fw-semibold">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" id="stock_in_quantity" name="quantity" 
                               min="1" required placeholder="Enter quantity">
                    </div>

                    <div class="mb-3">
                        <label for="supplier_id" class="form-label fw-semibold">From Supplier (Optional)</label>
                        <select class="form-select" id="supplier_id" name="supplier_id">
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="cost_per_unit" class="form-label fw-semibold">Cost Per Unit (Optional)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" id="cost_per_unit" name="cost_per_unit" placeholder="0.00">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="stock_in_notes" class="form-label fw-semibold">Notes</label>
                        <textarea class="form-control" id="stock_in_notes" name="notes" rows="2" placeholder="Optional notes"></textarea>
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

<!-- Adjust Inventory Modal -->
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
                    <div class="alert alert-info border-0 mb-4">
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>

                    <div class="mb-3">
                        <label for="adjust_quantity" class="form-label fw-semibold">New Stock Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" id="adjust_quantity" name="quantity" 
                               value="{{ $product->stock_quantity }}" min="0" required>
                        <small class="text-muted">Enter the corrected total stock quantity</small>
                    </div>

                    <div class="mb-3">
                        <label for="adjust_notes" class="form-label fw-semibold">Reason for Adjustment <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="adjust_notes" name="notes" rows="3" required placeholder="e.g., Stock count discrepancy, damaged items found, etc."></textarea>
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

<!-- Return to Supplier Modal -->
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
                    <div class="alert alert-warning border-0 mb-4">
                        <i class="bi bi-exclamation-triangle"></i> Current stock: <strong>{{ $product->stock_quantity }} units</strong>
                    </div>

                    <div class="mb-3">
                        <label for="return_quantity" class="form-label fw-semibold">Quantity to Return <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-lg" id="return_quantity" name="quantity" 
                               min="1" max="{{ $product->stock_quantity }}" required placeholder="Enter quantity">
                        <small class="text-muted">Maximum: {{ $product->stock_quantity }} units</small>
                    </div>

                    <div class="mb-3">
                        <label for="return_supplier_id" class="form-label fw-semibold">Return to Supplier (Optional)</label>
                        <select class="form-select" id="return_supplier_id" name="supplier_id">
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ $product->supplier_id == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="return_reason" class="form-label fw-semibold">Reason for Return <span class="text-danger">*</span></label>
                        <select class="form-select" id="return_reason" name="reason" required>
                            <option value="">-- Select Reason --</option>
                            <option value="Defective/Damaged">Defective/Damaged</option>
                            <option value="Wrong Item">Wrong Item</option>
                            <option value="Overstocked">Overstocked</option>
                            <option value="Quality Issues">Quality Issues</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="return_notes" class="form-label fw-semibold">Additional Notes</label>
                        <textarea class="form-control" id="return_notes" name="notes" rows="3" placeholder="Describe the issue or provide additional details"></textarea>
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