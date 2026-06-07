@extends('layouts.app')

@section('title', 'Service Details')

@section('content')
<div class="container-fluid">

    <x-page-header title="{{ $service->name }}" subtitle="Service details" icon="bi-tools">
        <x-slot name="actions">
            <a href="{{ route('services.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('services.edit', $service) }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="row">
        <div class="col-lg-6">
            <div class="card app-card-panel">
                <div class="card-body p-3">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted fw-normal py-1">Name</dt>
                        <dd class="col-7 fw-semibold py-1 mb-0">{{ $service->name }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Description</dt>
                        <dd class="col-7 py-1 mb-0">{{ $service->description ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Default Price</dt>
                        <dd class="col-7 fw-semibold text-success py-1 mb-0">₱{{ number_format($service->default_price, 2) }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Status</dt>
                        <dd class="col-7 py-1 mb-0">
                            <span class="badge {{ $service->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $service->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal py-1">Created</dt>
                        <dd class="col-7 py-1 mb-0">{{ $service->created_at->format('M d, Y h:i A') }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Updated</dt>
                        <dd class="col-7 py-1 mb-0">{{ $service->updated_at->format('M d, Y h:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
