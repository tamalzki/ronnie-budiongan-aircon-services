@extends('layouts.app')

@section('title', 'Aircon Part Details')

@section('content')
<div class="container-fluid">

    <x-page-header title="{{ $part->name }}" subtitle="Aircon part details & stock history" icon="bi-nut">
        <x-slot name="actions">
            <a href="{{ route('parts.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <button type="button" class="btn btn-success btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#stockInModal">
                <i class="bi bi-box-arrow-in-down"></i> Stock In
            </button>
            <a href="{{ route('parts.edit', $part) }}" class="btn btn-primary btn-sm shadow-sm">
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
                        <dd class="col-7 fw-semibold py-1 mb-0">{{ $part->name }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Linked Model / Set</dt>
                        <dd class="col-7 py-1 mb-0">{{ $part->linked_model_label ?? 'General / Unlinked' }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Description</dt>
                        <dd class="col-7 py-1 mb-0">{{ $part->description ?? '—' }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Cost</dt>
                        <dd class="col-7 fw-semibold text-success py-1 mb-0">₱{{ number_format($part->cost, 2) }}</dd>

                        <dt class="col-5 text-muted fw-normal py-1">Current Stock</dt>
                        <dd class="col-7 py-1 mb-0">
                            <span class="badge {{ $part->stock_quantity <= 0 ? 'bg-danger' : 'bg-light text-dark border' }}">
                                {{ $part->stock_quantity }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal py-1">Status</dt>
                        <dd class="col-7 py-1 mb-0">
                            <span class="badge {{ $part->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $part->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>

                        <dt class="col-5 text-muted fw-normal py-1">Created</dt>
                        <dd class="col-7 py-1 mb-0">{{ $part->created_at->format('M d, Y h:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card app-card-panel">
                <div class="card-header bg-white py-2">
                    <span class="fw-semibold small">Recent Stock Movements</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 app-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Stock After</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($movements as $movement)
                                <tr>
                                    <td class="px-3 py-2" style="white-space:nowrap">{{ $movement->created_at->format('M d, Y') }}</td>
                                    <td class="px-3 py-2" style="white-space:nowrap">{!! $movement->type_badge !!}</td>
                                    <td class="px-3 py-2 text-end">{{ $movement->quantity }}</td>
                                    <td class="px-3 py-2 text-end">{{ $movement->stock_after }}</td>
                                    <td class="px-3 py-2 text-muted">{{ $movement->notes ?? '—' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        No stock movements yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Stock In Modal --}}
<div class="modal fade" id="stockInModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('parts.stock-in', $part) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Stock In — {{ $part->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-info border-0 mb-3 py-2 small">
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $part->stock_quantity }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" name="quantity" min="1" required placeholder="Enter quantity">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Notes</label>
                        <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle"></i> Add Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
