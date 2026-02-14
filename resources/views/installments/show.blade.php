@extends('layouts.app')

@section('title', 'Installments - ' . $customer['name'])

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
    <ol class="breadcrumb mb-2">
        <li class="breadcrumb-item"><a href="{{ route('installments.index') }}">Installment Customers</a></li>
        <li class="breadcrumb-item active">{{ $customer['name'] }}</li>
    </ol>
</nav>
            <h2 class="mb-1"><i class="bi bi-person-fill text-primary"></i> {{ $customer['name'] }}</h2>
            <p class="text-muted mb-0">
                @if($customer['contact'])
                <i class="bi bi-telephone"></i> {{ $customer['contact'] }}
                @endif
                @if($customer['address'])
                | <i class="bi bi-geo-alt"></i> {{ $customer['address'] }}
                @endif
            </p>
        </div>
        <div>
            <a href="{{ route('installments.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Customers
            </a>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 p-3 rounded">
                                <i class="bi bi-cash-stack fs-2 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Amount</h6>
                            <h4 class="mb-0">₱{{ number_format($totalAmount, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 p-3 rounded">
                                <i class="bi bi-check-circle fs-2 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Paid</h6>
                            <h4 class="mb-0 text-success">₱{{ number_format($totalPaid, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 p-3 rounded">
                                <i class="bi bi-exclamation-triangle fs-2 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Balance</h6>
                            <h4 class="mb-0 text-danger">₱{{ number_format($totalBalance, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 p-3 rounded">
                                <i class="bi bi-calendar3 fs-2 text-info"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Installments</h6>
                            <h4 class="mb-0">{{ $installments->count() }} payments</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Installments Schedule -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Installment Schedule</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-4 py-3">#</th>
                            <th class="border-0 px-4 py-3">Invoice</th>
                            <th class="border-0 px-4 py-3">Due Date</th>
                            <th class="border-0 px-4 py-3">Amount Due</th>
                            <th class="border-0 px-4 py-3">Amount Paid</th>
                            <th class="border-0 px-4 py-3">Balance</th>
                            <th class="border-0 px-4 py-3">Status</th>
                            <th class="border-0 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($installments as $installment)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="badge bg-primary">{{ $loop->iteration }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('sales.show', $installment->sale) }}" class="text-decoration-none fw-bold">
                                    {{ $installment->sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                {{ $installment->due_date->format('M d, Y') }}
                                <br>
                                <small class="text-muted">{{ $installment->due_date->diffForHumans() }}</small>
                                @if($installment->due_date < now() && $installment->status !== 'paid')
                                    <br><span class="badge bg-danger">Overdue</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <strong>₱{{ number_format($installment->amount, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3 text-success">
                                <strong>₱{{ number_format($installment->amount_paid, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $balance = $installment->amount - $installment->amount_paid;
                                @endphp
                                <strong class="{{ $balance > 0 ? 'text-danger' : 'text-success' }}">
                                    ₱{{ number_format($balance, 2) }}
                                </strong>
                            </td>
                            <td class="px-4 py-3">
                                @if($installment->status == 'paid')
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="bi bi-check-circle"></i> Paid
                                    </span>
                                @elseif($installment->status == 'partial')
                                    <span class="badge bg-info px-3 py-2">
                                        <i class="bi bi-hourglass-split"></i> Partial
                                    </span>
                                @else
                                    <span class="badge bg-warning px-3 py-2">
                                        <i class="bi bi-clock"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($installment->status !== 'paid')
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal{{ $installment->id }}">
                                    <i class="bi bi-cash"></i> Pay Now
                                </button>
                                @else
                                <small class="text-muted">Paid on {{ $installment->paid_date->format('M d, Y') }}</small>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <p class="mb-0">No installments found</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                            <th class="border-0 px-4 py-3">Invoice</th>
                            <th class="border-0 px-4 py-3">Installment #</th>
                            <th class="border-0 px-4 py-3">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $paidInstallments = $installments->where('status', '!=', 'unpaid');
                        @endphp
                        @forelse($paidInstallments as $payment)
                        <tr>
                            <td class="px-4 py-3">
                                {{ $payment->paid_date ? $payment->paid_date->format('M d, Y h:i A') : 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('sales.show', $payment->sale) }}" class="text-decoration-none">
                                    {{ $payment->sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-primary">Installment #{{ $loop->iteration }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <strong class="text-success">₱{{ number_format($payment->amount_paid, 2) }}</strong>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                    <p class="mb-0">No payment history yet</p>
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

<!-- Payment Modals -->
@foreach($installments->where('status', '!=', 'paid') as $installment)
<div class="modal fade" id="payModal{{ $installment->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('installments.pay', $installment) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Record Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-4">
                        <strong>Invoice:</strong> {{ $installment->sale->invoice_number }}<br>
                        <strong>Due Date:</strong> {{ $installment->due_date->format('F d, Y') }}<br>
                        <strong>Amount Due:</strong> ₱{{ number_format($installment->amount, 2) }}
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount to Pay <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" name="amount_paid" 
                                   value="{{ $installment->amount - $installment->amount_paid }}" 
                                   max="{{ $installment->amount - $installment->amount_paid }}"
                                   min="0.01" required>
                        </div>
                        <small class="text-muted">Remaining balance: ₱{{ number_format($installment->amount - $installment->amount_paid, 2) }}</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control form-control-lg" name="paid_date" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select form-select-lg" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="check">Check</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference Number</label>
                        <input type="text" class="form-control" name="reference_number" placeholder="Check #, Transfer Ref, etc.">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endsection