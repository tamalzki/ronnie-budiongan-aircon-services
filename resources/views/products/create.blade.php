@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
<div class="container-fluid">

    <x-page-header title="Add Product" subtitle="Add an indoor and outdoor unit together as one set" icon="bi-plus-circle">
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
                Add product here (indoor + outdoor as one set) → Create Purchase Order (cost auto-fills) → Receive Stock → Sell
            </div>

            <div class="card app-card-panel">
                <div class="card-header bg-white py-2 px-3">
                    <span class="fw-semibold small"><i class="bi bi-box-seam text-primary me-1"></i>Product Details</span>
                </div>
                <div class="card-body p-3">
                    <form action="{{ route('products.store') }}" method="POST">
                        @csrf

                        {{-- Brand --}}
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
                        </div>

                        {{-- Indoor & Outdoor Models --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">🏠 Indoor Unit Model <span class="text-danger">*</span></label>
                                <input type="text" name="indoor_model"
                                       class="form-control form-control-sm @error('indoor_model') is-invalid @enderror"
                                       value="{{ old('indoor_model') }}"
                                       placeholder="e.g. FTKC60BVAF" required>
                                @error('indoor_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold mb-1">🌤️ Outdoor Unit Model <span class="text-danger">*</span></label>
                                <input type="text" name="outdoor_model"
                                       class="form-control form-control-sm @error('outdoor_model') is-invalid @enderror"
                                       value="{{ old('outdoor_model') }}"
                                       placeholder="e.g. RKC60BVA" required>
                                @error('outdoor_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <small class="text-muted">
                                    Both units are created and linked as one set — inventory (serials/stock) is tracked
                                    separately for each, but they share one price in purchase orders and sales.
                                </small>
                            </div>
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
@endsection
