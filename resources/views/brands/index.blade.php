@extends('layouts.app')

@section('title', 'Brands')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Brands Management</h2>
        <a href="{{ route('brands.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Brand
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($brands as $brand)
                        <tr>
                            <td>{{ $brand->id }}</td>
                            <td>{{ $brand->name }}</td>
                            <td>{{ Str::limit($brand->description, 50) }}</td>
                            <td>
                                <span class="badge bg-info">{{ $brand->products_count }} products</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $brand->is_active ? 'success' : 'secondary' }}">
                                    {{ $brand->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('brands.show', $brand) }}" class="btn btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('brands.edit', $brand) }}" class="btn btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No brands found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection