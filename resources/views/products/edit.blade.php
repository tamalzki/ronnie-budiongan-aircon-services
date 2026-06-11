@extends('layouts.app')

@section('title', 'Edit Product')

@section('content')
<div class="container-fluid">

    <x-page-header title="Edit Product" subtitle="{{ $product->brand->name ?? '' }} {{ $product->model }}" icon="bi-pencil">
        <x-slot name="actions">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card app-card-panel">
                <div class="card-header bg-white py-2 px-3">
                    <span class="fw-semibold small"><i class="bi bi-box-seam text-primary me-1"></i>Product Details</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('products.update', $product) }}" method="POST">
                        @csrf @method('PUT')

                        {{-- Brand & Model --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Brand <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('brand_id') is-invalid @enderror"
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
                                <label class="form-label small fw-semibold mb-1">Model <span class="text-danger">*</span></label>
                                <input type="text"
                                       class="form-control form-control-sm @error('model') is-invalid @enderror"
                                       name="model"
                                       value="{{ old('model', $product->model) }}"
                                       placeholder="e.g. FTKC60BVAF"
                                       required>
                                @error('model')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        {{-- Unit Type & Serial Number --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Unit Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('unit_type') is-invalid @enderror"
                                        name="unit_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="indoor" {{ old('unit_type', $product->unit_type) == 'indoor' ? 'selected' : '' }}>🏠 Indoor Unit</option>
                                    <option value="outdoor" {{ old('unit_type', $product->unit_type) == 'outdoor' ? 'selected' : '' }}>🌤️ Outdoor Unit</option>
                                </select>
                                @error('unit_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Serial Number</label>
                                <input type="text"
                                       class="form-control form-control-sm @error('serial_number') is-invalid @enderror"
                                       name="serial_number"
                                       value="{{ old('serial_number', $product->serial_number) }}"
                                       placeholder="">
                                @error('serial_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">For inventory tracking</small>
                            </div>
                        </div>

                        {{-- Paired outdoor unit (sets) --}}
                        <div class="mb-3" id="pairedWrap" style="{{ old('unit_type', $product->unit_type) === 'indoor' ? '' : 'display:none;' }}">
                            <label class="form-label small fw-semibold mb-1">Paired Outdoor Unit <span class="text-muted fw-normal">(sold as one set, one price)</span></label>
                            <select class="form-select form-select-sm @error('paired_product_id') is-invalid @enderror" name="paired_product_id">
                                <option value="">-- Not paired (sold individually) --</option>
                                @foreach($outdoorUnits ?? [] as $o)
                                    <option value="{{ $o->id }}" {{ old('paired_product_id', $product->paired_product_id) == $o->id ? 'selected' : '' }}>
                                        {{ $o->brand->name ?? '' }} {{ $o->model }}
                                    </option>
                                @endforeach
                            </select>
                            @error('paired_product_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">When paired, this indoor unit and the outdoor unit appear as a single line in inventory, purchase orders, and sales.</small>
                        </div>

                        {{-- Supplier --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Supplier</label>
                            <select class="form-select form-select-sm @error('supplier_id') is-invalid @enderror" name="supplier_id">
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
                            <label class="form-label small fw-semibold mb-1">Description</label>
                            <textarea class="form-control form-control-sm" name="description" rows="2">{{ old('description', $product->description) }}</textarea>
                        </div>

                        {{-- Price & Cost --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Selling Price <span class="text-danger">*</span></label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01"
                                           class="form-control @error('price') is-invalid @enderror"
                                           name="price" value="{{ old('price', $product->price) }}"
                                           required min="0.01" id="priceInput">
                                </div>
                                @error('price')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">What customers pay</small>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Cost</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('cost') is-invalid @enderror"
                                           name="cost" value="{{ old('cost', $product->cost) }}"
                                           id="costInput">
                                </div>
                                @error('cost')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <small class="text-muted">Manual entry or auto-updated from PO</small>
                            </div>
                        </div>

                        {{-- Live profit display --}}
                        @if($product->cost > 0 || old('cost'))
                        <div class="alert alert-info mb-3" id="profitAlert">
                            <i class="bi bi-bar-chart"></i>
                            <strong>Profit Margin:</strong>
                            ₱<span id="profitAmount">{{ number_format($product->price - $product->cost, 2) }}</span>
                            (<span id="profitPct">{{ $product->cost > 0 ? number_format((($product->price - $product->cost) / $product->cost) * 100, 1) : '0.0' }}</span>%)
                        </div>
                        @endif

                        {{-- Stock --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Stock Quantity</label>
                            <input type="number" class="form-control form-control-sm" name="stock_quantity"
                                   value="{{ old('stock_quantity', $product->stock_quantity) }}" min="0">
                            <small class="text-muted">Use Products &amp; Stock to add serials (Stock in) or open the full inventory tools for this item.</small>
                        </div>

                        {{-- Active --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="isActive" {{ old('is_active', $product->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="isActive">Active Product</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-sm shadow-sm">
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
const priceInput = document.getElementById('priceInput');
const costInput = document.getElementById('costInput');
const profitAlert = document.getElementById('profitAlert');

if (priceInput && costInput) {
    function updateProfit() {
        const price   = parseFloat(priceInput.value) || 0;
        const cost    = parseFloat(costInput.value) || 0;
        const profit  = price - cost;
        const pct     = cost > 0 ? (profit / cost * 100) : 0;

        if (profitAlert) {
            document.getElementById('profitAmount').textContent = profit.toFixed(2);
            document.getElementById('profitPct').textContent    = pct.toFixed(1);
            profitAlert.className = 'alert mb-3 ' + (profit >= 0 ? 'alert-info' : 'alert-danger');
        }
    }

    priceInput.addEventListener('input', updateProfit);
    costInput.addEventListener('input', updateProfit);
}

document.querySelector('select[name="unit_type"]')?.addEventListener('change', function () {
    const wrap = document.getElementById('pairedWrap');
    if (wrap) wrap.style.display = this.value === 'indoor' ? '' : 'none';
});
</script>
@endpush
@endsection
