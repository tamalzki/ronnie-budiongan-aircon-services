@extends('layouts.app')

@section('title', 'Supplier Payments')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="mb-4">
        <h2 class="mb-1"><i class="bi bi-cash-coin text-primary"></i> Supplier Payments</h2>
        <p class="text-muted mb-0">Track all payments made to suppliers</p>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-cash-stack fs-2 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Paid</h6>
                            <h3 class="mb-0 text-success">₱{{ number_format($totalPaid, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="bi bi-exclamation-triangle fs-2 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Payments</h6>
                            <h3 class="mb-0 text-danger">₱{{ number_format($totalPending, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-receipt fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Transactions</h6>
                            <h3 class="mb-0">{{ $payments->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Payments -->
    @if($unpaidPOs->count() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-danger text-white border-0">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Pending Payments</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-4 py-3">PO Number</th>
                            <th class="border-0 px-4 py-3">Supplier</th>
                            <th class="border-0 px-4 py-3">Order Date</th>
                            <th class="border-0 px-4 py-3">Total</th>
                            <th class="border-0 px-4 py-3">Paid</th>
                            <th class="border-0 px-4 py-3">Balance</th>
                            <th class="border-0 px-4 py-3">Due Date</th>
                            <th class="border-0 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($unpaidPOs as $po)
                        <tr>
                            <td class="px-4 py-3">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="fw-bold text-decoration-none">
                                    {{ $po->po_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">{{ $po->supplier->name }}</td>
                            <td class="px-4 py-3">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3">₱{{ number_format($po->total, 2) }}</td>
                            <td class="px-4 py-3 text-success">₱{{ number_format($po->amount_paid, 2) }}</td>
                            <td class="px-4 py-3 text-danger fw-bold">₱{{ number_format($po->balance, 2) }}</td>
                            <td class="px-4 py-3">
                                {{ $po->payment_due_date->format('M d, Y') }}
                                <br>
                                <small class="text-muted">{{ $po->payment_due_date->diffForHumans() }}</small>
                                @if($po->payment_due_date < now())
                                <span class="badge bg-danger">Overdue</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-cash-coin"></i> Pay Now
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Payment History -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-clock-history"></i> Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-4 py-3">Date</th>
                            <th class="border-0 px-4 py-3">PO Number</th>
                            <th class="border-0 px-4 py-3">Supplier</th>
                            <th class="border-0 px-4 py-3">Amount</th>
                            <th class="border-0 px-4 py-3">Method</th>
                            <th class="border-0 px-4 py-3">Reference</th>
                            <th class="border-0 px-4 py-3">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
    @forelse($payments as $payment)
    <tr class="{{ str_contains(strtolower($payment->payment_number), 'downpayment') ? 'table-warning' : '' }}">
        <td class="px-4 py-3">
            <i class="bi bi-calendar3"></i> {{ $payment->payment_date->format('M d, Y') }}
            <br><small class="text-muted">{{ $payment->payment_date->diffForHumans() }}</small>
        </td>
        <td class="px-4 py-3">
            <a href="{{ route('purchase-orders.show', $payment->purchaseOrder) }}" class="fw-bold text-decoration-none">
                {{ $payment->purchaseOrder->po_number }}
            </a>
            @if(str_contains(strtolower($payment->payment_number), 'downpayment'))
                <br><span class="badge bg-warning"><i class="bi bi-cash"></i> Downpayment</span>
            @endif
        </td>
        <td class="px-4 py-3">{{ $payment->purchaseOrder->supplier->name }}</td>
        <td class="px-4 py-3">
            <strong class="text-success">₱{{ number_format($payment->amount, 2) }}</strong>
        </td>
        <td class="px-4 py-3">
            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">
                {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
            </span>
        </td>
        <td class="px-4 py-3">{{ $payment->reference_number ?? '-' }}</td>
        <td class="px-4 py-3">
            <small class="text-muted">{{ $payment->user->name }}</small>
        </td>
    </tr>
    @empty
    <!-- ... -->
    @endforelse
</tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection