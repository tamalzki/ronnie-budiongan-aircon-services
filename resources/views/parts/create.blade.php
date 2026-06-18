@extends('layouts.app')

@section('title', 'Add Aircon Part')

@section('content')
<div class="container-fluid">

    <x-page-header title="New Aircon Part" subtitle="Add a spare aircon part to the catalog" icon="bi-nut">
        <x-slot name="actions">
            <a href="{{ route('parts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="row">
        <div class="col-lg-7 col-xl-6">
            <div class="card app-card-panel">
                <div class="card-body p-3">
                    <form action="{{ route('parts.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold mb-1">Aircon Part Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @include('parts.partials.product-model-combobox', [
                            'productOptions' => $productOptions,
                            'selectedId' => old('product_id'),
                            'hasError' => $errors->has('product_id'),
                        ])

                        <div class="mb-3">
                            <label for="description" class="form-label small fw-semibold mb-1">Description</label>
                            <textarea class="form-control form-control-sm @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="cost" class="form-label small fw-semibold mb-1">Cost</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0"
                                       class="form-control @error('cost') is-invalid @enderror"
                                       id="cost" name="cost" value="{{ old('cost', 0) }}" required>
                                @error('cost')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-text">Latest known unit cost — updated automatically when received on a purchase order.</div>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="is_active">Active</label>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <a href="{{ route('parts.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                                <i class="bi bi-save"></i> Create Aircon Part
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
