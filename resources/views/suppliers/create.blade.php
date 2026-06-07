@extends('layouts.app')

@section('title', 'Create Supplier')

@section('content')
<div class="container-fluid">

    <x-page-header title="New Supplier" subtitle="Add a supplier" icon="bi-people">
        <x-slot name="actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="row">
        <div class="col-lg-8 col-xl-7">
            <div class="card app-card-panel">
                <div class="card-body p-3">
                    <form action="{{ route('suppliers.store') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label for="name" class="form-label small fw-semibold mb-1">Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="contact_person" class="form-label small fw-semibold mb-1">Contact Person</label>
                            <input type="text" class="form-control form-control-sm @error('contact_person') is-invalid @enderror"
                                   id="contact_person" name="contact_person" value="{{ old('contact_person') }}">
                            @error('contact_person')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="contact_number" class="form-label small fw-semibold mb-1">Contact Number</label>
                                <input type="text" class="form-control form-control-sm @error('contact_number') is-invalid @enderror"
                                       id="contact_number" name="contact_number" value="{{ old('contact_number') }}">
                                @error('contact_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label small fw-semibold mb-1">Email</label>
                                <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror"
                                       id="email" name="email" value="{{ old('email') }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label small fw-semibold mb-1">Address</label>
                            <textarea class="form-control form-control-sm @error('address') is-invalid @enderror"
                                      id="address" name="address" rows="3">{{ old('address') }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                   {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="is_active">Active</label>
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-sm shadow-sm">
                                <i class="bi bi-save"></i> Create Supplier
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
