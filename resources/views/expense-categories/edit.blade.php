@extends('layouts.app')

@section('title', 'Edit expense category')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <x-page-header title="Edit category" :subtitle="$category->name" icon="bi-pencil" marginClass="mb-3" />
            <x-flash />
            <div class="card app-card-panel">
                <div class="card-body">
                    <form action="{{ route('expense-categories.update', $category) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $category->name) }}" required maxlength="255"
                                   class="form-control @error('name') is-invalid @enderror">
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Sort order</label>
                            <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}" min="0" max="65535"
                                   class="form-control @error('sort_order') is-invalid @enderror">
                            @error('sort_order')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
