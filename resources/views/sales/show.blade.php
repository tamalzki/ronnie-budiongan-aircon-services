@extends('layouts.app')

@section('title', 'Sale — {{ $sale->invoice_number }}')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
                    <li class="breadcrumb-item active">{{ $sale->invoice_number }}</li>
                </ol>
            </nav>
            <h2 class="mb-1"><i class="bi bi-receipt text-primary"></i> {{ $sale->invoice_number }}</h2>
            <p class="text-muted mb-0">{{ $sale->sale_date->format('F d, Y') }} &mdash; {{ $sale->customer_name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Sales
            </a>
            @if($sale->payment_type === 'installment')
            <a href="{{ route('installments.show', $sale->id) }}"
               class="btn {{ $sale->balance > 0 ? 'btn-warning' : 'btn-success' }} btn-sm">
                <i class="bi bi-calendar-check"></i> Installment Schedule
            </a>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row g-3">

        {{-- LEFT: Customer + Items + Installment Schedule --}}
        <div class="col-md-8">

            {{-- Customer Info --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-2">
                    <h6 class="mb-0"><i class="bi bi-person"></i> Customer Information</h6>
                </div>
                <div class="card-body" style="font-size:0.875rem;">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Name</span>
                            <div class="fw-semibold">{{ $sale->customer_name }}</div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Contact</span>
                            <div>{{ $sale->customer_contact ?? '—' }}</div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">Sale Date</span>
                            <div>{{ $sale->sale_date->format('M d, Y') }}</div>
                        </div>
                        @if($sale->customer_address)
                        <div class="col-12">
                            <span class="text-muted small d-block">Address</span>
                            <div>{{ $sale->customer_address }}</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Items --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-cart"></i> Items Purchased</h6>
                    <span class="badge bg-secondary">{{ $sale->items->count() }} item(s)</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size:0.875rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-2 border-0">#</th>
                                <th class="px-3 py-2 border-0">Item</th>
                                <th class="px-3 py-2 border-0">Unit Type</th>
                                <th class="px-3 py-2 border-0">Serial No.</th>
                                <th class="px-3 py-2 border-0 text-center">Qty</th>
                                <th class="px-3 py-2 border-0 text-end">Unit Price</th>
                                <th class="px-3 py-2 border-0 text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($sale->items as $item)
                            <tr>
                                <td class="px-3 py-2 text-muted">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2">
                                    <span class="fw-semibold">{{ $item->item_name }}</span>
                                    @if(!$item->product_id)
                                        <span class="badge bg-info text-dark ms-1" style="font-size:0.65rem;">Service</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($item->product && $item->product->unit_type === 'indoor')
                                        <span class="badge" style="background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;font-size:0.75rem;">❄️ Indoor</span>
                                    @elseif($item->product && $item->product->unit_type === 'outdoor')
                                        <span class="badge" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.75rem;">🌀 Outdoor</span>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    @if($item->product && $item->product->serial_number)
                                        <code class="text-dark bg-light px-2 py-1 rounded" style="font-size:0.78rem;">
                                            {{ $item->product->serial_number }}
                                        </code>
                                    @else
                                        <span class="text-muted small">—</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="badge bg-primary rounded-pill">{{ $item->quantity }}</span>
                                </td>
                                <td class="px-3 py-2 text-end">₱{{ number_format($item->unit_price, 2) }}</td>
                                <td class="px-3 py-2 text-end fw-semibold">₱{{ number_format($item->total_price, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-light">
                            <tr>
                                <td colspan="6" class="px-3 py-2 text-end text-muted">Subtotal</td>
                                <td class="px-3 py-2 text-end fw-semibold">₱{{ number_format($sale->subtotal, 2) }}</td>
                            </tr>
                            @if(($sale->discount ?? 0) > 0)
                            <tr>
                                <td colspan="6" class="px-3 py-1 text-end text-danger">Discount</td>
                                <td class="px-3 py-1 text-end text-danger fw-semibold">-₱{{ number_format($sale->discount, 2) }}</td>
                            </tr>
                            @endif
                            <tr class="border-top">
                                <td colspan="6" class="px-3 py-2 text-end fw-bold">TOTAL</td>
                                <td class="px-3 py-2 text-end fw-bold text-primary" style="font-size:1.1rem;">
                                    ₱{{ number_format($sale->total, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Installment Schedule --}}
            @if($sale->payment_type === 'installment' && $sale->installmentPayments->count())
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="bi bi-calendar-check text-primary"></i> Installment Schedule</h6>
                    <div class="d-flex gap-2" style="font-size:0.8rem;">
                        <span class="badge bg-success">Paid: ₱{{ number_format($sale->paid_amount, 2) }}</span>
                        @if($sale->balance > 0)
                        <span class="badge bg-danger">Balance: ₱{{ number_format($sale->balance, 2) }}</span>
                        @endif
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" style="font-size:0.855rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-2 border-0">#</th>
                                <th class="px-3 py-2 border-0">Due Date</th>
                                <th class="px-3 py-2 border-0 text-end">Amount</th>
                                <th class="px-3 py-2 border-0 text-end">Paid</th>
                                <th class="px-3 py-2 border-0">Method</th>
                                <th class="px-3 py-2 border-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($sale->installmentPayments->sortBy('installment_number') as $inst)
                            @php
                                $isDownpayment = $inst->installment_number === 1 && $inst->status === 'paid' && $inst->notes === 'Downpayment';
                                $rowClass = '';
                                if ($inst->status === 'paid') $rowClass = 'table-success';
                                elseif($inst->due_date && $inst->due_date->isPast()) $rowClass = 'table-danger';
                                elseif($inst->due_date && $inst->due_date->diffInDays(now()) <= 7) $rowClass = 'table-warning';
                            @endphp
                            <tr class="{{ $rowClass }}">
                                <td class="px-3 py-2">
                                    {{ $inst->installment_number }}
                                    @if($isDownpayment)
                                        <span class="badge bg-primary ms-1" style="font-size:0.68rem;">Down</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2" style="white-space:nowrap">
                                    {{ $inst->due_date ? $inst->due_date->format('M d, Y') : '—' }}
                                </td>
                                <td class="px-3 py-2 text-end fw-semibold">₱{{ number_format($inst->amount, 2) }}</td>
                                <td class="px-3 py-2 text-end text-success fw-semibold">
                                    {{ $inst->amount_paid > 0 ? '₱' . number_format($inst->amount_paid, 2) : '—' }}
                                </td>
                                <td class="px-3 py-2" style="white-space:nowrap">
                                    @php
                                        $icons = ['cash'=>'💵','gcash'=>'📱','bank_transfer'=>'🏦','cheque'=>'🧾'];
                                        $m = $inst->payment_method;
                                    @endphp
                                    {{ $m ? ($icons[$m] ?? '') . ' ' . ucwords(str_replace('_',' ',$m)) : '—' }}
                                </td>
                                <td class="px-3 py-2" style="white-space:nowrap">
                                    @if($inst->status === 'paid')
                                        <span class="badge bg-success"><i class="bi bi-check-circle"></i> Paid</span>
                                    @elseif($inst->due_date && $inst->due_date->isPast())
                                        <span class="badge bg-danger">Overdue</span>
                                    @elseif($inst->due_date && $inst->due_date->diffInDays(now()) <= 7)
                                        <span class="badge bg-warning text-dark">Due Soon</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>

        {{-- RIGHT: Payment Summary --}}
        <div class="col-md-4">

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-primary text-white border-0 py-2">
                    <h6 class="mb-0"><i class="bi bi-credit-card"></i> Payment Summary</h6>
                </div>
                <div class="card-body" style="font-size:0.875rem;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Type</span>
                        <span class="badge {{ $sale->payment_type === 'cash' ? 'bg-success' : 'bg-warning text-dark' }}">
                            {{ $sale->payment_type === 'cash' ? 'Cash (Full)' : 'Installment' }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Method</span>
                        <span>
                            @php $icons = ['cash'=>'💵','gcash'=>'📱','bank_transfer'=>'🏦','cheque'=>'🧾']; @endphp
                            {{ ($icons[$sale->payment_method] ?? '') . ' ' . ucwords(str_replace('_',' ',$sale->payment_method ?? '—')) }}
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Status</span>
                        <span class="badge bg-{{ $sale->status == 'completed' ? 'success' : ($sale->status == 'pending' ? 'warning text-dark' : 'danger') }}">
                            {{ ucfirst($sale->status) }}
                        </span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Subtotal</span>
                        <span>₱{{ number_format($sale->subtotal, 2) }}</span>
                    </div>
                    @if(($sale->discount ?? 0) > 0)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-danger">Discount</span>
                        <span class="text-danger">-₱{{ number_format($sale->discount, 2) }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2 border-top pt-2">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold text-primary" style="font-size:1.1rem;">₱{{ number_format($sale->total, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Amount Paid</span>
                        <span class="text-success fw-semibold">₱{{ number_format($sale->paid_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Balance</span>
                        @if($sale->balance > 0)
                            <span class="text-danger fw-bold">₱{{ number_format($sale->balance, 2) }}</span>
                        @else
                            <span class="text-success fw-bold"><i class="bi bi-check-circle"></i> Fully Paid</span>
                        @endif
                    </div>

                    @if($sale->payment_type === 'installment')
                    <hr class="my-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Installment Plan</span>
                        <span class="fw-semibold">{{ $sale->installmentPayments->count() }} months</span>
                    </div>
                    @php
                        $paidMonths   = $sale->installmentPayments->where('status', 'paid')->count();
                        $unpaidMonths = $sale->installmentPayments->where('status', '!=', 'paid')->count();
                    @endphp
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Months Paid</span>
                        <span class="text-success fw-semibold">{{ $paidMonths }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Months Remaining</span>
                        <span class="{{ $unpaidMonths > 0 ? 'text-danger' : 'text-success' }} fw-semibold">{{ $unpaidMonths }}</span>
                    </div>
                    @endif
                </div>
            </div>

            @if($sale->notes)
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-light border-0 py-2">
                    <h6 class="mb-0"><i class="bi bi-sticky"></i> Notes</h6>
                </div>
                <div class="card-body" style="font-size:0.875rem;">{{ $sale->notes }}</div>
            </div>
            @endif

            <div class="card border-0 shadow-sm">
                <div class="card-body" style="font-size:0.78rem;color:#aaa;">
                    <i class="bi bi-person-circle me-1"></i> Created by {{ $sale->user->name ?? '—' }}<br>
                    <i class="bi bi-clock me-1"></i> {{ $sale->created_at->format('M d, Y h:i A') }}
                </div>
            </div>

        </div>
    </div>
</div>
@endsection