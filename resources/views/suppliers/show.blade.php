@extends('layouts.app')

@section('title', 'Supplier Details')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">{{ $supplier->name }}</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">ID</th>
                            <td>{{ $supplier->id }}</td>
                        </tr>
                        <tr>
                            <th>Name</th>
                            <td>{{ $supplier->name }}</td>
                        </tr>
                        <tr>
                            <th>Contact Person</th>
                            <td>{{ $supplier->contact_person ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Contact Number</th>
                            <td>{{ $supplier->contact_number ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td>{{ $supplier->email ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td>{{ $supplier->address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-{{ $supplier->is_active ? 'success' : 'secondary' }}">
                                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Total Products</th>
                            <td>{{ $supplier->products->count() }}</td>
                        </tr>
                        <tr>
                            <th>Created At</th>
                            <td>{{ $supplier->created_at->format('M d, Y h:i A') }}</td>
                        </tr>
                        <tr>
                            <th>Updated At</th>
                            <td>{{ $supplier->updated_at->format('M d, Y h:i A') }}</td>
                        </tr>
                    </table>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-warning">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>
                </div>
            </div>

            @if($supplier->products->count() > 0)
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Products ({{ $supplier->products->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Brand</th>
                                    <th>Model</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supplier->products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->brand->name ?? 'N/A' }}</td>
                                    <td>{{ $product->model ?? 'N/A' }}</td>
                                    <td>₱{{ number_format($product->price, 2) }}</td>
                                    <td>{{ $product->stock_quantity }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection