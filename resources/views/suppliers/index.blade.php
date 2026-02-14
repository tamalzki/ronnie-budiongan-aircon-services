@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Suppliers Management</h2>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Supplier
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
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th>Products</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->id }}</td>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->contact_person ?? 'N/A' }}</td>
                            <td>{{ $supplier->contact_number ?? 'N/A' }}</td>
                            <td>{{ $supplier->email ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-info">{{ $supplier->products_count }} products</span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">
                                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
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
                            <td colspan="8" class="text-center">No suppliers found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection