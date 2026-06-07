@extends('layouts.app')

@section('title', 'Supplier Details')

@section('content')
<div class="container-fluid">

    <x-page-header title="{{ $supplier->name }}" subtitle="Supplier details" icon="bi-people">
        <x-slot name="actions">
            <a href="{{ route('suppliers.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card app-card-panel">
                <div class="card-body p-3">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal py-1">Name</dt>
                        <dd class="col-7 fw-semibold py-1 mb-0">{{ $supplier->name }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Contact Person</dt>
                        <dd class="col-7 py-1 mb-0">{{ $supplier->contact_person ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Contact Number</dt>
                        <dd class="col-7 py-1 mb-0">{{ $supplier->contact_number ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Email</dt>
                        <dd class="col-7 py-1 mb-0">{{ $supplier->email ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Address</dt>
                        <dd class="col-7 py-1 mb-0">{{ $supplier->address ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Status</dt>
                        <dd class="col-7 py-1 mb-0">
                            <span class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal py-1">Total Products</dt>
                        <dd class="col-7 fw-semibold py-1 mb-0">{{ $supplier->products->count() }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Created</dt>
                        <dd class="col-7 py-1 mb-0">{{ $supplier->created_at->format('M d, Y h:i A') }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Updated</dt>
                        <dd class="col-7 py-1 mb-0">{{ $supplier->updated_at->format('M d, Y h:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        @if($supplier->products->count() > 0)
        <div class="col-lg-7">
            <div class="card app-card-panel">
                <div class="card-header bg-white py-2 px-3">
                    <span class="fw-semibold small"><i class="bi bi-boxes text-primary me-1"></i>Products ({{ $supplier->products->count() }})</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0 app-table-compact">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-3 py-2">Product Name</th>
                                    <th class="px-3 py-2">Brand</th>
                                    <th class="px-3 py-2">Model</th>
                                    <th class="px-3 py-2 text-end">Price</th>
                                    <th class="px-3 py-2 text-center">Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($supplier->products as $product)
                                <tr>
                                    <td class="px-3 py-1 fw-semibold">{{ $product->name }}</td>
                                    <td class="px-3 py-1">{{ $product->brand->name ?? '—' }}</td>
                                    <td class="px-3 py-1">{{ $product->model ?? '—' }}</td>
                                    <td class="px-3 py-1 text-end">₱{{ number_format($product->price, 2) }}</td>
                                    <td class="px-3 py-1 text-center">{{ $product->stock_quantity }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
