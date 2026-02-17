@extends('layouts.app')

@section('title', 'Purchase Order Details')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('purchase-orders.index') }}">Purchase Orders</a></li>
                    <li class="breadcrumb-item active">{{ $purchaseOrder->po_number }}</li>
                </ol>
            </nav>
            <h2 class="mb-0"><i class="bi bi-cart-plus text-primary"></i> {{ $purchaseOrder->po_number }}</h2>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            @if($purchaseOrder->status === 'pending')
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#receiveModal">
                <i class="bi bi-box-arrow-in-down"></i> Receive Stock
            </button>
            @endif
            @if($purchaseOrder->payment_type === '45days' && $purchaseOrder->payment_status !== 'paid' && $purchaseOrder->balance > 0)
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="bi bi-cash-coin"></i> Record Payment
            </button>
            @endif
        </div>
    </div>

    {{-- Flash message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
            {!! session('success') !!}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Deadline alerts --}}
    @if($deadlineAlert === 'overdue')
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <i class="bi bi-exclamation-octagon-fill fs-5 me-2"></i>
        <strong>Payment Overdue!</strong> Due {{ $purchaseOrder->payment_due_date->format('M d, Y') }}.
        Balance: <strong>₱{{ number_format($purchaseOrder->balance, 2) }}</strong>
    </div>
    @elseif($deadlineAlert === 'warning')
    <div class="alert alert-warning border-0 shadow-sm mb-3">
        <i class="bi bi-bell-fill fs-5 me-2"></i>
        <strong>Payment Due in {{ (int)$daysRemaining }} day(s)!</strong>
        Deadline: {{ $purchaseOrder->payment_due_date->format('M d, Y') }}.
        Balance: <strong>₱{{ number_format($purchaseOrder->balance, 2) }}</strong>
    </div>
    @endif

    {{-- Set price alert (after receiving stock) --}}
    @if($purchaseOrder->status === 'received')
    @php $noPriceItems = $purchaseOrder->items->filter(fn($item) => $item->product->price == 0); @endphp
    @if($noPriceItems->count() > 0)
    <div class="card border-warning border-2 shadow-sm mb-4">
        <div class="card-header bg-warning text-dark border-0">
            <h5 class="mb-0"><i class="bi bi-lock-fill"></i> {{ $noPriceItems->count() }} product(s) need a selling price before they can be sold</h5>
        </div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-4 py-2">Product</th>
                        <th class="px-4 py-2">Cost (from this PO)</th>
                        <th class="px-4 py-2" width="300">Set Selling Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($noPriceItems as $item)
                    <tr>
                        <td class="px-4 py-3 fw-semibold">{{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}</td>
                        <td class="px-4 py-3">
                            <strong class="text-danger">₱{{ number_format($item->discounted_cost ?? $item->unit_cost, 2) }}</strong>
                            <br><small class="text-muted">Your cost — set price above this</small>
                        </td>
                        <td class="px-4 py-3">
                            <form action="{{ route('products.set-price', $item->product) }}" method="POST" class="d-flex align-items-center gap-2">
                                @csrf
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="{{ $item->discounted_cost ?? $item->unit_cost }}"
                                           class="form-control" name="price"
                                           placeholder="{{ number_format(($item->discounted_cost ?? $item->unit_cost) * 1.2, 2) }}" required>
                                </div>
                                <button type="submit" class="btn btn-warning btn-sm fw-semibold px-3">
                                    <i class="bi bi-check"></i> Set
                                </button>
                            </form>
                            <small class="text-muted">Suggested: ₱{{ number_format(($item->discounted_cost ?? $item->unit_cost) * 1.2, 2) }} (+20%)</small>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
    @endif

    {{-- ═══════════════════════════════════════════════════════
         ROW 1: Supplier Info (left) + Payment Summary (right)
    ════════════════════════════════════════════════════════ --}}
    <div class="row g-4 mb-4">

        {{-- Supplier & Order Info --}}
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white border-0">
                    <h5 class="mb-0"><i class="bi bi-building"></i> Supplier & Order Info</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Supplier</small>
                            <strong>{{ $purchaseOrder->supplier->name }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Order Date</small>
                            <strong>{{ $purchaseOrder->order_date->format('F d, Y') }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">DR / Delivery Receipt No.</small>
                            @if($purchaseOrder->delivery_number)
                                <strong><i class="bi bi-truck text-success"></i> {{ $purchaseOrder->delivery_number }}</strong>
                            @else
                                <span class="text-warning"><i class="bi bi-clock"></i> Pending — set when receiving stock</span>
                            @endif
                        </div>
                        @if($purchaseOrder->expected_delivery_date)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Expected Delivery</small>
                            <strong>{{ $purchaseOrder->expected_delivery_date->format('F d, Y') }}</strong>
                        </div>
                        @endif
                        @if($purchaseOrder->received_date)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Received Date</small>
                            <strong class="text-success"><i class="bi bi-check-circle"></i> {{ $purchaseOrder->received_date->format('F d, Y') }}</strong>
                        </div>
                        @endif
                        @if($purchaseOrder->notes)
                        <div class="col-12">
                            <small class="text-muted d-block">Notes</small>
                            <span>{{ $purchaseOrder->notes }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Summary --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning border-0">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Summary</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0">
                        <tr class="border-bottom">
                            <td class="px-4 py-2 text-muted small fw-semibold">DR Number</td>
                            <td class="px-4 py-2">
                                @if($purchaseOrder->delivery_number)
                                    <strong class="text-success"><i class="bi bi-truck"></i> {{ $purchaseOrder->delivery_number }}</strong>
                                @else
                                    <span class="text-warning small"><i class="bi bi-clock"></i> Not yet received</span>
                                @endif
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="px-4 py-2 text-muted small fw-semibold">Payment Type</td>
                            <td class="px-4 py-2">
                                <span class="badge {{ $purchaseOrder->payment_type == 'full' ? 'bg-success' : 'bg-info text-dark' }}">
                                    {{ $purchaseOrder->payment_type == 'full' ? 'Full Payment' : '45-Day Term' }}
                                </span>
                            </td>
                        </tr>
                        @if($purchaseOrder->payment_type === '45days')
                        <tr class="border-bottom">
                            <td class="px-4 py-2 text-muted small fw-semibold">Due Date</td>
                            <td class="px-4 py-2">
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span id="dueDateDisplay">
                                        {{ $purchaseOrder->payment_due_date ? $purchaseOrder->payment_due_date->format('M d, Y') : '—' }}
                                    </span>
                                    @if($daysRemaining !== null && $purchaseOrder->payment_status !== 'paid')
                                        @if($daysRemaining < 0)
                                            <span class="badge bg-danger">Overdue {{ abs((int)$daysRemaining) }}d</span>
                                        @elseif($daysRemaining <= 10)
                                            <span class="badge bg-warning text-dark">{{ (int)$daysRemaining }}d left</span>
                                        @else
                                            <small class="text-muted">{{ (int)$daysRemaining }}d left</small>
                                        @endif
                                    @endif
                                    @if($purchaseOrder->payment_status !== 'paid')
                                    <button class="btn btn-outline-secondary btn-sm" style="padding:1px 7px;font-size:0.75rem"
                                            onclick="document.getElementById('editDueDateForm').classList.toggle('d-none')">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    @endif
                                </div>
                                {{-- Inline edit form --}}
                                <form id="editDueDateForm" class="d-none mt-2 d-flex gap-2 align-items-center"
                                      action="{{ route('purchase-orders.update-due-date', $purchaseOrder) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="date" name="payment_due_date" class="form-control form-control-sm"
                                           value="{{ optional($purchaseOrder->payment_due_date)->format('Y-m-d') }}"
                                           style="max-width:160px" required>
                                    <button type="submit" class="btn btn-primary btn-sm" style="padding:2px 10px;font-size:0.8rem">
                                        <i class="bi bi-check"></i> Save
                                    </button>
                                    <button type="button" class="btn btn-light btn-sm" style="padding:2px 10px;font-size:0.8rem"
                                            onclick="document.getElementById('editDueDateForm').classList.add('d-none')">
                                        Cancel
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endif
                        <tr class="border-bottom">
                            <td class="px-4 py-2 text-muted small fw-semibold">Total</td>
                            <td class="px-4 py-2"><strong class="text-primary fs-5">₱{{ number_format($purchaseOrder->total, 2) }}</strong></td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="px-4 py-2 text-muted small fw-semibold">Paid</td>
                            <td class="px-4 py-2"><strong class="text-success">₱{{ number_format($purchaseOrder->amount_paid, 2) }}</strong></td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="px-4 py-2 text-muted small fw-semibold">Balance</td>
                            <td class="px-4 py-2">
                                <strong class="{{ $purchaseOrder->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    ₱{{ number_format($purchaseOrder->balance, 2) }}
                                </strong>
                            </td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="px-4 py-2 text-muted small fw-semibold">Payment Status</td>
                            <td class="px-4 py-2">
                                <span class="badge bg-{{ $purchaseOrder->payment_status == 'paid' ? 'success' : ($purchaseOrder->payment_status == 'partial' ? 'info text-dark' : 'danger') }}">
                                    {{ ucfirst($purchaseOrder->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-muted small fw-semibold">Delivery Status</td>
                            <td class="px-4 py-2">
                                <span class="badge bg-{{ $purchaseOrder->status == 'received' ? 'success' : 'warning text-dark' }}">
                                    {{ $purchaseOrder->status == 'received' ? 'Received' : 'Pending' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

    </div>{{-- end row 1 --}}

    {{-- ═══════════════════════════════════════════════════════
         ROW 2: Order Items (full width)
    ════════════════════════════════════════════════════════ --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-0 bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-box-seam"></i> Order Items</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3 text-center" width="90">Ordered</th>
                            <th class="px-4 py-3 text-center" width="90">Received</th>
                            <th class="px-4 py-3 text-center" width="140">Unit Cost</th>
                            <th class="px-4 py-3 text-center" width="90">Disc %</th>
                            <th class="px-4 py-3 text-center" width="140">Net Cost</th>
                            <th class="px-4 py-3 text-end" width="140">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="fw-semibold">{{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}</span>
                                @if($item->product->price == 0)
                                    <span class="badge bg-warning text-dark ms-2 align-middle">
                                        <i class="bi bi-lock-fill"></i> No Price
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge bg-primary rounded-pill px-3">{{ $item->quantity_ordered }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($item->quantity_received >= $item->quantity_ordered)
                                    <span class="badge bg-success rounded-pill px-3">{{ $item->quantity_received }}</span>
                                @elseif($item->quantity_received > 0)
                                    <span class="badge bg-warning text-dark rounded-pill px-3">{{ $item->quantity_received }}</span>
                                @else
                                    <span class="badge bg-secondary rounded-pill px-3">0</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-muted">₱{{ number_format($item->unit_cost, 2) }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($item->discount_percent > 0)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">
                                        {{ $item->discount_percent }}% off
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <strong class="text-danger">₱{{ number_format($item->discounted_cost ?? $item->unit_cost, 2) }}</strong>
                            </td>
                            <td class="px-4 py-3 text-end fw-bold fs-6">₱{{ number_format($item->total_cost, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <td colspan="6" class="text-end fw-bold px-4 py-3 fs-6">Grand Total:</td>
                            <td class="text-end fw-bold text-primary fs-5 px-4 py-3">
                                ₱{{ number_format($purchaseOrder->total, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         ROW 3: Payment History (full width, if any)
    ════════════════════════════════════════════════════════ --}}
    @if($purchaseOrder->payments->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info text-white border-0">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Amount</th>
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3">Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->payments as $payment)
                        <tr class="{{ str_contains(strtolower($payment->payment_number), 'downpayment') ? 'table-warning' : '' }}">
                            <td class="px-4 py-3">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3">
                                @if(str_contains(strtolower($payment->payment_number), 'downpayment'))
                                    <span class="badge bg-warning text-dark"><i class="bi bi-cash"></i> Downpayment</span>
                                @else
                                    <span class="badge bg-info text-dark"><i class="bi bi-cash-coin"></i> Payment</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 fw-bold text-success">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                            <td class="px-4 py-3 text-muted">{{ $payment->reference_number ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold px-4 py-3">Total Paid:</td>
                            <td class="fw-bold text-success px-4 py-3">₱{{ number_format($purchaseOrder->amount_paid, 2) }}</td>
                            <td colspan="2"></td>
                        </tr>
                        @if($purchaseOrder->balance > 0)
                        <tr class="table-danger">
                            <td colspan="3" class="text-end fw-bold px-4 py-2">Balance Due:</td>
                            <td class="fw-bold text-danger px-4 py-2">₱{{ number_format($purchaseOrder->balance, 2) }}</td>
                            <td colspan="2">
                                <small class="text-muted">Due: {{ $purchaseOrder->payment_due_date->format('M d, Y') }}</small>
                            </td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>{{-- end container-fluid --}}

{{-- ═══════════════════════════════════════════════
     RECEIVE STOCK MODAL
════════════════════════════════════════════════ --}}
@if($purchaseOrder->status === 'pending')
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Receive Stock — {{ $purchaseOrder->po_number }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 mb-3">
                        <i class="bi bi-info-circle"></i>
                        Receiving stock will <strong>auto-update product cost</strong> from PO unit price.
                        You will be prompted to set selling prices after.
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="received_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-truck"></i> DR / Delivery Receipt Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="delivery_number" placeholder="e.g. DR-2026-00123" required>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center" width="90">Ordered</th>
                                    <th class="text-center" width="90">Received</th>
                                    <th class="text-center" width="120">Net Cost</th>
                                    <th width="140">Receive Now</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($purchaseOrder->items as $item)
                                <tr>
                                    <td class="fw-medium">{{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}</td>
                                    <td class="text-center"><span class="badge bg-primary">{{ $item->quantity_ordered }}</span></td>
                                    <td class="text-center"><span class="badge bg-success">{{ $item->quantity_received }}</span></td>
                                    <td class="text-center">
                                        <strong class="text-danger">₱{{ number_format($item->discounted_cost ?? $item->unit_cost, 2) }}</strong>
                                        <br><small class="text-muted">→ updates cost</small>
                                    </td>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                        <input type="number" class="form-control form-control-sm"
                                               name="items[{{ $loop->index }}][quantity_received]"
                                               value="{{ $item->quantity_ordered - $item->quantity_received }}"
                                               min="0" max="{{ $item->quantity_ordered - $item->quantity_received }}" required>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Receive Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- ═══════════════════════════════════════════════
     PAYMENT MODAL
════════════════════════════════════════════════ --}}
@if($purchaseOrder->payment_type === '45days' && $purchaseOrder->balance > 0)
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.payment', $purchaseOrder) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Record Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4 text-center">
                        <div class="col-4">
                            <div class="card border-0 bg-primary bg-opacity-10 p-2">
                                <small class="text-muted">Total</small>
                                <strong class="text-primary">₱{{ number_format($purchaseOrder->total, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-success bg-opacity-10 p-2">
                                <small class="text-muted">Paid</small>
                                <strong class="text-success">₱{{ number_format($purchaseOrder->amount_paid, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-danger bg-opacity-10 p-2">
                                <small class="text-muted">Balance</small>
                                <strong class="text-danger">₱{{ number_format($purchaseOrder->balance, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control" name="amount"
                                       value="{{ $purchaseOrder->balance }}" max="{{ $purchaseOrder->balance }}" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">-- Select Method --</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">💵 Cash</option>
                                <option value="gcash">📱 GCash</option>
                                <option value="bank_transfer">🏦 Bank Transfer</option>
                                <option value="cheque">🧾 Cheque</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Reference #</label>
                            <input type="text" class="form-control" name="reference_number" placeholder="Optional">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle"></i> Record Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@endsection