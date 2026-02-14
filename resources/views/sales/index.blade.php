@extends('layouts.app')

@section('title', 'Sales')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Sales Management</h2>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New Sale
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Sale Date</th>
                            <th>Total</th>
                            <th>Payment Type</th>
                            <th>Balance</th>
                            <th>Status</th>
                            <th width="250">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sales as $sale)
                        <tr>
                            <td>
                                <a href="{{ route('sales.show', $sale) }}" class="text-decoration-none fw-bold">
                                    {{ $sale->invoice_number }}
                                </a>
                            </td>
                            <td>{{ $sale->customer_name }}</td>
                            <td>{{ $sale->customer_contact ?? 'N/A' }}</td>
                            <td>{{ $sale->sale_date->format('M d, Y') }}</td>
                            <td class="fw-bold">₱{{ number_format($sale->total, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $sale->payment_type == 'cash' ? 'success' : 'warning' }}">
                                    {{ ucfirst($sale->payment_type) }}
                                </span>
                            </td>
                            <td>
                                @if($sale->balance > 0)
                                    <span class="text-danger fw-bold">₱{{ number_format($sale->balance, 2) }}</span>
                                @else
                                    <span class="text-success fw-bold">Fully Paid</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <!-- View Button -->
                                    <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    
                                    <!-- Installments Button (only for installment sales) -->
                                    @if($sale->payment_type === 'installment')
                                    <a href="{{ route('installments.show', $sale->id) }}" class="btn btn-sm btn-{{ $sale->balance > 0 ? 'warning' : 'success' }}">
                                        <i class="bi bi-calendar-check"></i> Installments
                                    </a>
                                    @endif
                                    
                                    <!-- Delete Button -->
                                    <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will restore product stock.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <p class="mb-0">No sales found</p>
                                    <small>Create your first sale to get started</small>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection