@extends('layouts.app')

@section('title', 'Sale Details')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Sale Details - {{ $sale->invoice_number }}</h2>
        <div>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Sales
            </a>
            <button onclick="window.print()" class="btn btn-info">
                <i class="bi bi-printer"></i> Print Invoice
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Customer Information -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200">Customer Name:</th>
                            <td>{{ $sale->customer_name }}</td>
                        </tr>
                        <tr>
                            <th>Contact Number:</th>
                            <td>{{ $sale->customer_contact ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Address:</th>
                            <td>{{ $sale->customer_address ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Sale Date:</th>
                            <td>{{ $sale->sale_date->format('F d, Y') }}</td>
                        </tr>
                        <tr>
                            <th>Created By:</th>
                            <td>{{ $sale->user->name }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Sale Items -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Sale Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->items as $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $item->item_type == 'product' ? 'primary' : 'info' }}">
                                            {{ ucfirst($item->item_type) }}
                                        </span>
                                    </td>
                                    <td>{{ $item->item_name }}</td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>₱{{ number_format($item->unit_price, 2) }}</td>
                                    <td>₱{{ number_format($item->total_price, 2) }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-light">
                                    <td colspan="4" class="text-end fw-bold">Subtotal:</td>
                                    <td class="fw-bold">₱{{ number_format($sale->subtotal, 2) }}</td>
                                </tr>
                                @if($sale->discount > 0)
                                <tr class="table-light">
                                    <td colspan="4" class="text-end fw-bold text-danger">Discount:</td>
                                    <td class="fw-bold text-danger">₱{{ number_format($sale->discount, 2) }}</td>
                                </tr>
                                @endif
                                <tr class="table-primary">
                                    <td colspan="4" class="text-end fw-bold fs-5">TOTAL:</td>
                                    <td class="fw-bold fs-5">₱{{ number_format($sale->total, 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Installment Schedule (if applicable) -->
            @if($sale->payment_type === 'installment' && $sale->installmentPayments->count() > 0)
            <div class="card mb-3">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Installment Schedule</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Payment #</th>
                                    <th>Due Date</th>
                                    <th>Amount Due</th>
                                    <th>Amount Paid</th>
                                    <th>Paid Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->installmentPayments as $payment)
                                <tr>
                                    <td>{{ $payment->payment_number }}</td>
                                    <td>{{ $payment->due_date->format('M d, Y') }}</td>
                                    <td>₱{{ number_format($payment->amount_due, 2) }}</td>
                                    <td>₱{{ number_format($payment->amount_paid, 2) }}</td>
                                    <td>{{ $payment->paid_date ? $payment->paid_date->format('M d, Y') : 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $payment->status == 'paid' ? 'success' : ($payment->status == 'overdue' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <!-- Payment Summary -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Payment Summary</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Payment Type:</th>
                            <td>
                                <span class="badge bg-{{ $sale->payment_type == 'cash' ? 'success' : 'warning' }}">
                                    {{ ucfirst($sale->payment_type) }}
                                </span>
                            </td>
                        </tr>
                        @if($sale->payment_type === 'installment')
                        <tr>
                            <th>Months:</th>
                            <td>{{ $sale->installment_months }}</td>
                        </tr>
                        <tr>
                            <th>Monthly Payment:</th>
                            <td class="fw-bold">₱{{ number_format($sale->installment_amount, 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Total Amount:</th>
                            <td class="fw-bold fs-5">₱{{ number_format($sale->total, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Paid Amount:</th>
                            <td class="fw-bold text-success">₱{{ number_format($sale->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>Balance:</th>
                            <td class="fw-bold {{ $sale->balance > 0 ? 'text-danger' : 'text-success' }}">
                                ₱{{ number_format($sale->balance, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($sale->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, nav, .navbar {
        display: none !important;
    }
}
</style>
@endsection