@extends('layouts.app')
@section('title', 'Add Product')
@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-plus-circle text-primary"></i> Add Product</h2>
            <p class="text-muted mb-0">Products are identified by Brand + Model</p>
        </div>
        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-7">

            {{-- Workflow tip --}}
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <i class="bi bi-lightbulb-fill me-2"></i>
                <strong>Recommended workflow:</strong>
                Add product here → Create Purchase Order → Receive Stock (cost auto-sets) → Set Selling Price → Sell
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white border-0">
                    <h5 class="mb-0"><i class="bi bi-box-seam"></i> Product Details</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('products.store') }}" method="POST">
                        @csrf

                        {{-- Brand & Model --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Brand <span class="text-danger">*</span></label>
                                <select class="form-select @error('brand_id') is-invalid @enderror"
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
                                <label class="form-label fw-semibold">Model <span class="text-danger">*</span></label>
                                <input type="text" name="model"
                                       class="form-control @error('model') is-invalid @enderror"
                                       value="{{ old('model') }}"
                                       placeholder="e.g. Inverter 1.5HP" required>
                                @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">Brand + Model = product identity</small>
                            </div>
                        </div>

                        {{-- Supplier --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Supplier</label>
                            <select class="form-select" name="supplier_id">
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
                            <label class="form-label fw-semibold">Description</label>
                            <textarea class="form-control" name="description" rows="2"
                                      placeholder="Optional">{{ old('description') }}</textarea>
                        </div>

                        {{-- Selling Price (required) --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                Selling Price <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0.01"
                                       class="form-control @error('price') is-invalid @enderror"
                                       name="price" value="{{ old('price', '') }}"
                                       placeholder="e.g. 35000.00" required>
                            </div>
                            @error('price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Stock --}}
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Initial Stock <span class="text-danger">*</span></label>
                            <input type="number" class="form-control @error('stock_quantity') is-invalid @enderror"
                                   name="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0" required>
                            @error('stock_quantity')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        {{-- Active --}}
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active"
                                       id="isActive" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Active Product</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                            <button type="submit" class="btn btn-primary px-5">
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