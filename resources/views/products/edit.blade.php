{{-- resources/views/products/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">
                <i class="bi bi-pencil text-primary"></i>
                Edit: {{ $product->brand->name ?? '' }} {{ $product->model }}
            </h2>
            <p class="text-muted mb-0">Products are identified by Brand + Model</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white border-0">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Product Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('products.update', $product) }}" method="POST">
                        @csrf @method('PUT')

                        {{-- Brand & Model --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Brand <span class="text-danger">*</span></label>
                                <select class="form-select @error('brand_id') is-invalid @enderror"
                                        name="brand_id" required>
                                    <option value="">-- Select Brand --</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}"
                                            {{ old('brand_id', $product->brand_id) == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Model <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control @error('model') is-invalid @enderror"
                                       name="model"
                                       value="{{ old('model', $product->model) }}"
                                       placeholder="e.g. Inverter 1.5HP"
                                       required>
                                @error('model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Supplier --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select class="form-select @error('supplier_id') is-invalid @enderror" name="supplier_id">
                                <option value="">-- Select Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}"
                                        {{ old('supplier_id', $product->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="2">{{ old('description', $product->description) }}</textarea>
                        </div>

                        {{-- Price & Cost --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Selling Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01"
                                           class="form-control @error('price') is-invalid @enderror"
                                           name="price" value="{{ old('price', $product->price) }}"
                                           required min="0" id="priceInput">
                                </div>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">What customers pay</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Cost (from last PO)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control bg-light"
                                           value="{{ number_format($product->cost, 2) }}" readonly>
                                </div>
                                <small class="text-muted">Auto-updated when receiving purchase orders</small>
                            </div>
                        </div>

                        {{-- Live profit display --}}
                        @if($product->cost > 0)
                        <div class="alert alert-info mb-3" id="profitAlert">
                            <i class="bi bi-bar-chart"></i>
                            <strong>Profit Margin:</strong>
                            ₱<span id="profitAmount">{{ number_format($product->price - $product->cost, 2) }}</span>
                            (<span id="profitPct">{{ number_format((($product->price - $product->cost) / $product->cost) * 100, 1) }}</span>%)
                        </div>
                        @endif

                        {{-- Stock --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Stock Quantity</label>
                            <input type="number" class="form-control" name="stock_quantity"
                                   value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0">
                            <small class="text-muted">Manage stock properly via Inventory page</small>
                        </div>

                        {{-- Active --}}
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="isActive" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Active Product</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary px-4">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="bi bi-save"></i> Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const cost = {{ $product->cost ?? 0 }};
const priceInput = document.getElementById('priceInput');
const profitAlert = document.getElementById('profitAlert');

if (priceInput && cost > 0 && profitAlert) {
    priceInput.addEventListener('input', function () {
        const price   = parseFloat(this.value) || 0;
        const profit  = price - cost;
        const pct     = cost > 0 ? (profit / cost * 100) : 0;

        document.getElementById('profitAmount').textContent = profit.toFixed(2);
        document.getElementById('profitPct').textContent    = pct.toFixed(1);

        profitAlert.className = 'alert mb-3 ' + (profit >= 0 ? 'alert-info' : 'alert-danger');
    });
}
</script>
@endpush
@endsection