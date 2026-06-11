@extends('layouts.app')

@section('title', 'Edit Sale — ' . $sale->invoice_number)

@section('content')
<div class="container-fluid">

    <x-page-header
        title="Edit Sale"
        subtitle="{{ $sale->invoice_number }} — {{ $sale->customer_name }}"
        icon="bi-pencil">
        <x-slot name="actions">
            <a href="{{ route('sales.show', $sale) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <form action="{{ route('sales.update', $sale) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-person"></i> Customer & Sale Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" name="customer_name" class="form-control form-control-sm"
                                       value="{{ old('customer_name', $sale->customer_name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Contact</label>
                                <input type="text" name="customer_contact" class="form-control form-control-sm"
                                       value="{{ old('customer_contact', $sale->customer_contact) }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Sale Date <span class="text-danger">*</span></label>
                                <input type="date" name="sale_date" class="form-control form-control-sm"
                                       value="{{ old('sale_date', $sale->sale_date->format('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                                <select name="status" class="form-select form-select-sm" required>
                                    @foreach(['completed', 'pending', 'cancelled'] as $st)
                                        <option value="{{ $st }}" {{ old('status', $sale->status) === $st ? 'selected' : '' }}>
                                            {{ ucfirst($st) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Address</label>
                                <textarea name="customer_address" class="form-control form-control-sm" rows="2">{{ old('customer_address', $sale->customer_address) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Notes</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="3">{{ old('notes', $sale->notes) }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info border-0 py-2" style="font-size:0.85rem;">
                    <i class="bi bi-info-circle"></i>
                    Items, prices, serials, and installment amounts are not changed here. Use <strong>Installment Schedule</strong> to adjust payment plans.
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle"></i> Save Changes
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
