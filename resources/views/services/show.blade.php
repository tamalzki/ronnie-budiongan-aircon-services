@extends('layouts.app')

@section('title', 'Service Details')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ $service->name }}</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">ID</th>
                            <td>{{ $service->id }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $service->name }}</td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td>{{ $service->description ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Default Price</th>
                            <td>₱{{ number_format($service->default_price, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}">
                                    {{ $service->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $service->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $service->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    </table>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('services.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <a href="{{ route('services.edit', $service) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection