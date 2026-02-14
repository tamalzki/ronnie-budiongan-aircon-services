@extends('layouts.app')

@section('title', 'Edit Service')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Service: {{ $service->name }}</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('services.update', $service) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input 
                                type="text" 
                                class="form-control @error('name') is-invalid @enderror" 
                                id="name" 
                                name="name" 
                                value="{{ old('name', $service->name) }}"
                                required
                            >
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea 
                                class="form-control @error('description') is-invalid @enderror" 
                                id="description" 
                                name="description" 
                                rows="3"
                            >{{ old('description', $service->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="default_price" class="form-label">Default Price <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    class="form-control @error('default_price') is-invalid @enderror" 
                                    id="default_price" 
                                    name="default_price" 
                                    value="{{ old('default_price', $service->default_price) }}"
                                    required
                                >
                                @error('default_price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="is_active" 
                                name="is_active"
                                {{ old('is_active', $service->is_active) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="is_active">
                                Active
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('services.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Service
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection