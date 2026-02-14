@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Services Management</h2>
        <a href="{{ route('services.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Add New Service
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th>Default Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                        <tr>
                            <td>{{ $service->id }}</td>
                            <td>{{ $service->name }}</td>
                            <td>{{ Str::limit($service->description, 50) }}</td>
                            <td>₱{{ number_format($service->default_price, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}">
                                    {{ $service->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('services.show', $service) }}" class="btn btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('services.edit', $service) }}" class="btn btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('services.destroy', $service) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?')">
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
                            <td colspan="6" class="text-center">No services found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection