@extends('layouts.app')

@section('title', 'Installments - ' . $customer['name'])

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
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
                    &nbsp;·&nbsp;<i class="bi bi-geo-alt"></i> {{ $customer['address'] }}
                @endif
            </p>
        </div>
        <a href="{{ route('installments.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="bi bi-cash-stack fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Amount</div>
                        <div class="fw-bold">₱{{ number_format($totalAmount, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-success bg-opacity-10 rounded p-2">
                        <i class="bi bi-check-circle fs-4 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Paid</div>
                        <div class="fw-bold text-success">₱{{ number_format($totalPaid, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-danger bg-opacity-10 rounded p-2">
                        <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Balance</div>
                        <div class="fw-bold text-danger">₱{{ number_format($totalBalance, 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-info bg-opacity-10 rounded p-2">
                        <i class="bi bi-calendar3 fs-4 text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Installments</div>
                        <div class="fw-bold">{{ $installments->count() }} payments</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Row color legend --}}
    <div class="d-flex gap-3 mb-2" style="font-size:0.8rem;">
        <span><span class="d-inline-block rounded" style="width:12px;height:12px;background:#f8d7da;border:1px solid #f5c2c7"></span> Overdue / Unpaid past due</span>
        <span><span class="d-inline-block rounded" style="width:12px;height:12px;background:#fff3cd;border:1px solid #ffc107"></span> Due this month</span>
        <span><span class="d-inline-block rounded" style="width:12px;height:12px;background:#d1e7dd;border:1px solid #a3cfbb"></span> Paid</span>
    </div>

    {{-- Installment Schedule --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0"><i class="bi bi-calendar-check text-primary"></i> Installment Schedule</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">#</th>
                            <th class="border-0 px-3 py-2">Invoice</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Due Date</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Amount Due</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Amount Paid</th>
                            <th class="border-0 px-3 py-2">Balance</th>
                            <th class="border-0 px-3 py-2">Status</th>
                            <th class="border-0 px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($installments as $installment)
                        @php
                            $balance      = $installment->amount - $installment->amount_paid;
                            $isCurrentMonth = $installment->due_date->month === now()->month
                                              && $installment->due_date->year === now()->year;
                            $isOverdue    = $installment->due_date < now() && $installment->status !== 'paid';
                            $isPaid       = $installment->status === 'paid';

                            $rowClass = '';
                            if ($isPaid)            $rowClass = 'table-success';
                            elseif ($isOverdue)     $rowClass = 'table-danger';
                            elseif ($isCurrentMonth) $rowClass = 'table-warning';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge bg-primary">{{ $loop->iteration }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('sales.show', $installment->sale) }}"
                                   class="text-decoration-none fw-semibold text-primary">
                                    {{ $installment->sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div>{{ $installment->due_date->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $installment->due_date->diffForHumans() }}</small>
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">
                                ₱{{ number_format($installment->amount, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-success fw-semibold">₱{{ number_format($installment->amount_paid, 2) }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="{{ $balance > 0 ? 'text-danger fw-semibold' : 'text-success fw-semibold' }}">
                                    ₱{{ number_format($balance, 2) }}
                                </span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($isPaid)
                                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Paid</span>
                                @elseif($installment->status === 'partial')
                                    <span class="badge bg-info text-dark"><i class="bi bi-hourglass-split"></i> Partial</span>
                                @elseif($isOverdue)
                                    <span class="badge bg-danger"><i class="bi bi-alarm"></i> Overdue</span>
                                @elseif($isCurrentMonth)
                                    <span class="badge bg-warning text-dark"><i class="bi bi-bell-fill"></i> Due Now</span>
                                @else
                                    <span class="badge bg-secondary"><i class="bi bi-clock"></i> Upcoming</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="d-flex gap-1">
                                    @if(!$isPaid)
                                    {{-- Pay Now --}}
                                    <button class="btn btn-success"
                                            style="padding:2px 8px;font-size:0.78rem"
                                            data-bs-toggle="modal"
                                            data-bs-target="#payModal{{ $installment->id }}">
                                        <i class="bi bi-cash"></i> Pay Now
                                    </button>
                                    @else
                                    {{-- Paid: show date + Edit button --}}
                                    <span class="text-muted" style="font-size:0.78rem">
                                        <i class="bi bi-check-circle text-success"></i>
                                        {{ $installment->paid_date ? $installment->paid_date->format('M d, Y') : '—' }}
                                    </span>
                                    <button class="btn btn-outline-secondary"
                                            style="padding:2px 8px;font-size:0.78rem"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editModal{{ $installment->id }}"
                                            title="Edit payment">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No installments found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Payment History --}}
    @php $paidInstallments = $installments->where('status', '!=', 'unpaid'); @endphp
    @if($paidInstallments->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="mb-0"><i class="bi bi-clock-history text-success"></i> Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Date Paid</th>
                            <th class="border-0 px-3 py-2">Invoice</th>
                            <th class="border-0 px-3 py-2">Installment #</th>
                            <th class="border-0 px-3 py-2">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paidInstallments as $payment)
                        <tr>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                {{ $payment->paid_date ? $payment->paid_date->format('M d, Y h:i A') : '—' }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('sales.show', $payment->sale) }}" class="text-decoration-none text-primary fw-semibold">
                                    {{ $payment->sale->invoice_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2">
                                <span class="badge bg-primary">Installment #{{ $loop->iteration }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-success fw-semibold">₱{{ number_format($payment->amount_paid, 2) }}</span>
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

{{-- ═══════════════════════════════════
     PAY NOW MODALS (unpaid/partial)
════════════════════════════════════ --}}
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
                    <div class="row g-2 mb-4 text-center">
                        <div class="col-4">
                            <div class="card border-0 bg-primary bg-opacity-10 p-2">
                                <small class="text-muted">Due Date</small>
                                <strong class="small">{{ $installment->due_date->format('M d, Y') }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-warning bg-opacity-10 p-2">
                                <small class="text-muted">Amount Due</small>
                                <strong class="small">₱{{ number_format($installment->amount, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-danger bg-opacity-10 p-2">
                                <small class="text-muted">Remaining</small>
                                <strong class="small text-danger">₱{{ number_format($installment->amount - $installment->amount_paid, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info border-0 mb-3">
                        <i class="bi bi-info-circle"></i> <strong>Flexible Payment:</strong> You can enter any amount. If payment exceeds this installment, the extra will automatically apply to the next unpaid installments.
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount to Pay <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" name="amount_paid"
                                   value="{{ $installment->amount - $installment->amount_paid }}"
                                   min="0.01" required
                                   placeholder="Enter any amount">
                        </div>
                        <small class="text-muted">Enter the amount customer is paying (can be more or less than remaining balance)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="paid_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" required>
                            <option value="">-- Select Method --</option>
                            <option value="cash">💵 Cash</option>
                            <option value="gcash">📱 GCash</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                            <option value="cheque">🧾 Cheque</option>
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

{{-- ═══════════════════════════════════
     EDIT MODALS (paid — fix user error)
════════════════════════════════════ --}}
@foreach($installments->where('status', 'paid') as $installment)
<div class="modal fade" id="editModal{{ $installment->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('installments.update', $installment) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header bg-secondary text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Edit Payment — #{{ $loop->iteration }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning border-0 mb-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Invoice:</strong> {{ $installment->sale->invoice_number }} &nbsp;·&nbsp;
                        <strong>Original Amount Due:</strong> ₱{{ number_format($installment->amount, 2) }}
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Amount Paid <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" step="0.01" class="form-control" name="amount_paid"
                                   value="{{ $installment->amount_paid }}"
                                   min="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="paid_date"
                               value="{{ $installment->paid_date ? $installment->paid_date->format('Y-m-d') : '' }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash"          {{ ($installment->payment_method ?? '') == 'cash'          ? 'selected' : '' }}>💵 Cash</option>
                            <option value="gcash"         {{ ($installment->payment_method ?? '') == 'gcash'         ? 'selected' : '' }}>📱 GCash</option>
                            <option value="bank_transfer" {{ ($installment->payment_method ?? '') == 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                            <option value="cheque"        {{ ($installment->payment_method ?? '') == 'cheque'        ? 'selected' : '' }}>🧾 Cheque</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reference Number</label>
                        <input type="text" class="form-control" name="reference_number"
                               value="{{ $installment->reference_number }}" placeholder="Optional">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Notes</label>
                        <textarea class="form-control" name="notes" rows="2">{{ $installment->notes }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-secondary px-4">
                        <i class="bi bi-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@endsection