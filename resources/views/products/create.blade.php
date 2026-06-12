@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
<div class="container-fluid">

    <x-page-header title="Add Product" subtitle="Products are identified by Brand + Model + Unit Type" icon="bi-plus-circle">
        <x-slot name="actions">
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="row">
        <div class="col-lg-8 col-xl-7">

            {{-- Workflow tip --}}
            <div class="alert alert-info border-0 shadow-sm py-2 px-3 mb-3 small">
                <i class="bi bi-lightbulb-fill me-1"></i>
                <strong>Recommended workflow:</strong>
                Add product here → Create Purchase Order (cost auto-fills) → Receive Stock → Sell
            </div>

            <div class="card app-card-panel">
                <div class="card-header bg-white py-2 px-3">
                    <span class="fw-semibold small"><i class="bi bi-box-seam text-primary me-1"></i>Product Details</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('products.store') }}" method="POST">
                        @csrf

                        {{-- Brand & Model --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Brand <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('brand_id') is-invalid @enderror"
                                        name="brand_id" required>
                                    <option value="">-- Select Brand --</option>
                                    @foreach($brands as $brand)
                                        <option value="{{ $brand->id }}" {{ old('brand_id') == $brand->id ? 'selected' : '' }}>
                                            {{ $brand->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('brand_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Model <span class="text-danger">*</span></label>
                                <input type="text" name="model"
                                       class="form-control form-control-sm @error('model') is-invalid @enderror"
                                       value="{{ old('model') }}"
                                       placeholder="e.g. FTKC60BVAF" required>
                                @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Unit Type --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Unit Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm @error('unit_type') is-invalid @enderror"
                                        id="unit_type" name="unit_type" required>
                                    <option value="">-- Select Type --</option>
                                    <option value="indoor" {{ old('unit_type') == 'indoor' ? 'selected' : '' }}>🏠 Indoor Unit</option>
                                    <option value="outdoor" {{ old('unit_type') == 'outdoor' ? 'selected' : '' }}>🌤️ Outdoor Unit</option>
                                </select>
                                @error('unit_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        {{-- Set pairing: create the matching outdoor unit at the same time --}}
                        <div class="mb-3" id="setWrap" style="display:none;">
                            <label class="form-label small fw-semibold mb-1">
                                Outdoor Unit Model <span class="text-muted fw-normal">(creates a linked set)</span>
                            </label>
                            <input type="text" name="outdoor_model"
                                   class="form-control form-control-sm @error('outdoor_model') is-invalid @enderror"
                                   value="{{ old('outdoor_model') }}"
                                   placeholder="e.g. RKC60BVA">
                            @error('outdoor_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <small class="text-muted">
                                If filled, a matching outdoor unit is created and paired with this indoor unit automatically.
                                Stock/serials are still tracked separately for each unit, but they share one price in
                                purchase orders and sales. Leave blank to add this unit by itself.
                            </small>
                        </div>

                        {{-- Supplier --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Supplier</label>
                            <select class="form-select form-select-sm" name="supplier_id">
                                <option value="">-- Select Supplier --</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Description --}}
                        <div class="mb-3">
                            <label class="form-label small fw-semibold mb-1">Description</label>
                            <textarea class="form-control form-control-sm" name="description" rows="2"
                                      placeholder="Optional">{{ old('description') }}</textarea>
                        </div>

                        {{-- Cost --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">Cost</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control @error('cost') is-invalid @enderror"
                                           name="cost" value="{{ old('cost', 0) }}"
                                           placeholder="e.g. 28000.00">
                                </div>
                                @error('cost')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <small class="text-muted">Used as default in PO</small>
                            </div>
                        </div>

                        {{-- Active --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="isActive" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="isActive">Active Product</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                                <i class="bi bi-save"></i> Save Product
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
function toggleSetWrap() {
    const unitType = document.getElementById('unit_type');
    const wrap = document.getElementById('setWrap');
    if (unitType && wrap) wrap.style.display = unitType.value === 'indoor' ? '' : 'none';
}
document.getElementById('unit_type')?.addEventListener('change', toggleSetWrap);
toggleSetWrap();
</script>
@endpush
@endsection
