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
            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
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

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3">
        {!! session('success') !!}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-3">
        {!! session('error') !!}
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

    {{-- Set price alert --}}
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
                        <th class="px-4 py-2">Unit Type</th>
                        <th class="px-4 py-2">Cost (from this PO)</th>
                        <th class="px-4 py-2" width="300">Set Selling Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($noPriceItems as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <span class="fw-semibold">{{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($item->product->unit_type === 'indoor')
                                <span class="badge" style="background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;">❄️ Indoor</span>
                            @elseif($item->product->unit_type === 'outdoor')
                                <span class="badge" style="background:#dcfce7;color:#166534;border:1px solid #86efac;">🌀 Outdoor</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <strong class="text-danger">₱{{ number_format($item->discounted_cost ?? $item->unit_cost, 2) }}</strong>
                            <br><small class="text-muted">Set price above this</small>
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

    {{-- Row 1: Supplier Info + Payment Summary --}}
    <div class="row g-4 mb-4">
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
                            <small class="text-muted d-block">PO Number</small>
                            <strong class="text-primary">{{ $purchaseOrder->po_number }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Order Date</small>
                            <strong>{{ $purchaseOrder->order_date->format('F d, Y') }}</strong>
                        </div>
                        @if($purchaseOrder->expected_delivery_date)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Expected Delivery</small>
                            <strong>{{ $purchaseOrder->expected_delivery_date->format('F d, Y') }}</strong>
                        </div>
                        @endif
                        <div class="col-sm-6">
                            <small class="text-muted d-block">DR / Delivery Receipt No.</small>
                            @if($purchaseOrder->delivery_number)
                                <strong><i class="bi bi-truck text-success"></i> {{ $purchaseOrder->delivery_number }}</strong>
                            @else
                                <span class="text-warning small"><i class="bi bi-clock"></i> Pending</span>
                            @endif
                        </div>
                        @if($purchaseOrder->received_date)
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Received Date</small>
                            <strong class="text-success"><i class="bi bi-check-circle"></i> {{ $purchaseOrder->received_date->format('F d, Y') }}</strong>
                        </div>
                        @endif
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Created By</small>
                            <strong>{{ optional($purchaseOrder->user)->name ?? 'System' }}</strong>
                        </div>
                        <div class="col-sm-6">
                            <small class="text-muted d-block">Created At</small>
                            <strong>{{ $purchaseOrder->created_at->format('M d, Y h:i A') }}</strong>
                        </div>
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

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-warning border-0">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> Payment Summary</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-borderless mb-0">
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
                                    <span>{{ $purchaseOrder->payment_due_date ? $purchaseOrder->payment_due_date->format('M d, Y') : '—' }}</span>
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
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @endif
                                </div>
                                <form id="editDueDateForm" class="d-none mt-2 d-flex gap-2 align-items-center"
                                      action="{{ route('purchase-orders.update-due-date', $purchaseOrder) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="date" name="payment_due_date" class="form-control form-control-sm"
                                           value="{{ optional($purchaseOrder->payment_due_date)->format('Y-m-d') }}"
                                           style="max-width:160px" required>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                    <button type="button" class="btn btn-light btn-sm"
                                            onclick="document.getElementById('editDueDateForm').classList.add('d-none')">Cancel</button>
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
                                <span class="badge bg-{{ $purchaseOrder->payment_status == 'paid' ? 'success' : ($purchaseOrder->payment_status == 'partial' ? 'warning text-dark' : 'danger') }}">
                                    {{ ucfirst($purchaseOrder->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 text-muted small fw-semibold">Delivery Status</td>
                            <td class="px-4 py-2">
                                <span class="badge bg-{{ $purchaseOrder->status == 'received' ? 'success' : 'warning text-dark' }}">
                                    {{ $purchaseOrder->status == 'received' ? '✓ Received' : '⏳ Pending' }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Order Items + Serials --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header border-0 bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-box-seam"></i> Order Items & Serial Numbers</h5>
            <span class="badge bg-white text-success">{{ $purchaseOrder->items->count() }} item(s)</span>
        </div>
        <div class="card-body p-0">
            @foreach($purchaseOrder->items as $item)
            @php
                $itemSerials = $purchaseOrder->serials->where('product_id', $item->product_id);
                $pendingSerials  = $itemSerials->where('status', 'pending');
                $inStockSerials  = $itemSerials->where('status', 'in_stock');
                $soldSerials     = $itemSerials->where('status', 'sold');
            @endphp
            <div class="border-bottom {{ $loop->last ? 'border-0' : '' }}">

                {{-- Item header row --}}
                <div class="px-4 py-3 d-flex flex-wrap align-items-center gap-3 bg-light">
                    <div style="flex:1;min-width:200px;">
                        <span class="fw-semibold fs-6">{{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}</span>
                        @if($item->product->unit_type === 'indoor')
                            <span class="badge ms-2" style="background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;">❄️ Indoor</span>
                        @elseif($item->product->unit_type === 'outdoor')
                            <span class="badge ms-2" style="background:#dcfce7;color:#166534;border:1px solid #86efac;">🌀 Outdoor</span>
                        @endif
                        @if($item->product->price == 0)
                            <span class="badge bg-warning text-dark ms-1" style="font-size:0.65rem;"><i class="bi bi-lock-fill"></i> No Price</span>
                        @endif
                    </div>
                    <div class="d-flex gap-3 text-center" style="font-size:0.82rem;">
                        <div>
                            <div class="text-muted small">Ordered</div>
                            <span class="badge bg-primary rounded-pill px-3">{{ $item->quantity_ordered }}</span>
                        </div>
                        <div>
                            <div class="text-muted small">Received</div>
                            @if($item->quantity_received >= $item->quantity_ordered)
                                <span class="badge bg-success rounded-pill px-3">{{ $item->quantity_received }}</span>
                            @elseif($item->quantity_received > 0)
                                <span class="badge bg-warning text-dark rounded-pill px-3">{{ $item->quantity_received }}</span>
                            @else
                                <span class="badge bg-secondary rounded-pill px-3">0</span>
                            @endif
                        </div>
                        <div>
                            <div class="text-muted small">Unit Cost</div>
                            <span class="text-muted">₱{{ number_format($item->unit_cost, 2) }}</span>
                        </div>
                        @if($item->discount_percent > 0)
                        <div>
                            <div class="text-muted small">Discount</div>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success">{{ $item->discount_percent }}% off</span>
                        </div>
                        @endif
                        <div>
                            <div class="text-muted small">Net Cost</div>
                            <strong class="text-danger">₱{{ number_format($item->discounted_cost ?? $item->unit_cost, 2) }}</strong>
                        </div>
                        <div>
                            <div class="text-muted small">Line Total</div>
                            <strong class="text-primary">₱{{ number_format($item->total_cost, 2) }}</strong>
                        </div>
                    </div>
                </div>

                {{-- Serial Numbers for this item --}}
                <div class="px-4 py-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-upc-scan text-primary"></i>
                        <span class="small fw-semibold text-primary">Serial Numbers</span>
                        <span class="badge bg-secondary">{{ $itemSerials->count() }} / {{ $item->quantity_ordered }} entered</span>
                        @if($itemSerials->count() === 0)
                            <span class="badge bg-warning text-dark">Not yet entered</span>
                        @elseif($inStockSerials->count() === $item->quantity_ordered)
                            <span class="badge bg-success">All In Stock</span>
                        @endif
                    </div>

                    @if($itemSerials->count() > 0)
                    <div class="row g-2">
                        @foreach($itemSerials->sortBy('serial_number') as $serial)
                        <div class="col-md-3 col-sm-4 col-6">
                            <div class="d-flex align-items-center gap-1 px-2 py-1 rounded border"
                                 style="font-size:0.78rem;
                                        background:{{ $serial->status === 'in_stock' ? '#f0fdf4' : ($serial->status === 'sold' ? '#eff6ff' : '#fffbeb') }};
                                        border-color:{{ $serial->status === 'in_stock' ? '#86efac' : ($serial->status === 'sold' ? '#93c5fd' : '#fcd34d') }} !important;">
                                <code style="font-size:0.78rem;flex:1;">{{ $serial->serial_number }}</code>
                                @if($serial->status === 'pending')
                                    <span title="Pending — not yet received" style="font-size:0.65rem;color:#b45309;">⏳</span>
                                @elseif($serial->status === 'in_stock')
                                    <span title="In Stock" style="font-size:0.65rem;color:#166534;">✅</span>
                                @elseif($serial->status === 'sold')
                                    <span title="Sold" style="font-size:0.65rem;color:#1d4ed8;">🛒</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-muted small mb-0 fst-italic">
                        @if($purchaseOrder->status === 'pending')
                            No serial numbers entered yet. You can add them when editing this PO or when receiving stock.
                        @else
                            No serial numbers were recorded for this item.
                        @endif
                    </p>
                    @endif
                </div>

            </div>
            @endforeach

            {{-- Grand Total footer --}}
            <div class="px-4 py-3 bg-light border-top d-flex justify-content-end">
                <span class="fw-bold me-3">Grand Total:</span>
                <span class="fw-bold text-primary fs-5">₱{{ number_format($purchaseOrder->total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Payment History --}}
    @if($purchaseOrder->payments->count() > 0)
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-info text-white border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-receipt"></i> Payment History</h5>
            <span class="badge bg-white text-info">{{ $purchaseOrder->payments->count() }} payment(s)</span>
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
                            <th class="px-4 py-3">Notes</th>
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
                                    <span class="badge bg-info text-dark"><i class="bi bi-cash-coin"></i> {{ $payment->payment_number }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $payment->payment_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 fw-bold text-success">₱{{ number_format($payment->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                @php $methodIcons = ['cash'=>'💵','gcash'=>'📱','bank_transfer'=>'🏦','cheque'=>'🧾']; @endphp
                                {{ $methodIcons[$payment->payment_method] ?? '' }} {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                            </td>
                            <td class="px-4 py-3 text-muted">{{ $payment->reference_number ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted small">{{ $payment->notes ?? '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="3" class="text-end fw-bold px-4 py-3">Total Paid:</td>
                            <td class="fw-bold text-success px-4 py-3">₱{{ number_format($purchaseOrder->amount_paid, 2) }}</td>
                            <td colspan="3"></td>
                        </tr>
                        @if($purchaseOrder->balance > 0)
                        <tr class="table-danger">
                            <td colspan="3" class="text-end fw-bold px-4 py-2">Balance Due:</td>
                            <td class="fw-bold text-danger px-4 py-2">₱{{ number_format($purchaseOrder->balance, 2) }}</td>
                            <td colspan="3">
                                @if($purchaseOrder->payment_due_date)
                                <small class="text-muted">Due: {{ $purchaseOrder->payment_due_date->format('M d, Y') }}</small>
                                @endif
                            </td>
                        </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

</div>

{{-- ═══════════════════════════════════════════
     RECEIVE STOCK MODAL — with serial inputs
════════════════════════════════════════════ --}}
@if($purchaseOrder->status === 'pending')
<div class="modal fade" id="receiveModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.receive', $purchaseOrder) }}" method="POST" id="receiveForm">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Receive Stock — {{ $purchaseOrder->po_number }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    <div class="alert alert-info border-0 mb-4" style="font-size:0.875rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Enter all serial numbers for each item before marking as received.
                        Serial count <strong>must match</strong> the quantity received exactly.
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="received_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-truck"></i> DR / Delivery Receipt Number</label>
                            <input type="text" class="form-control" name="delivery_number" placeholder="e.g. DR-2026-00123">
                        </div>
                    </div>

                    {{-- One card per item --}}
                    @foreach($purchaseOrder->items as $item)
                    @php
                        $remaining       = $item->quantity_ordered - $item->quantity_received;
                        $pendingSerials  = $purchaseOrder->serials
                            ->where('product_id', $item->product_id)
                            ->where('status', 'pending')
                            ->pluck('serial_number')
                            ->toArray();
                    @endphp
                    <div class="card border shadow-sm mb-3" id="receive-card-{{ $loop->index }}">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-semibold">{{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}</span>
                                @if($item->product->unit_type === 'indoor')
                                    <span class="badge ms-2" style="background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;font-size:0.72rem;">❄️ Indoor</span>
                                @elseif($item->product->unit_type === 'outdoor')
                                    <span class="badge ms-2" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.72rem;">🌀 Outdoor</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-3" style="font-size:0.82rem;">
                                <span>Ordered: <strong>{{ $item->quantity_ordered }}</strong></span>
                                <span>Already received: <strong>{{ $item->quantity_received }}</strong></span>
                                <span>Receiving now:
                                    <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                    <input type="number" class="form-control form-control-sm d-inline-block qty-receive-input"
                                           name="items[{{ $loop->index }}][quantity_received]"
                                           value="{{ $remaining }}"
                                           min="0" max="{{ $remaining }}"
                                           style="width:65px;"
                                           data-idx="{{ $loop->index }}"
                                           onchange="rebuildReceiveSerials({{ $loop->index }}, this.value)">
                                </span>
                                <span class="badge bg-secondary" id="receive-serial-count-{{ $loop->index }}">0 / {{ $remaining }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-upc-scan text-primary"></i>
                                <span class="small fw-semibold text-primary">Serial Numbers <span class="text-danger">*</span></span>
                                <span class="text-muted small">— must match quantity above exactly</span>
                            </div>
                            <div class="row g-1" id="receive-serials-{{ $loop->index }}">
                                @for($s = 0; $s < $remaining; $s++)
                                <div class="col-md-3 col-sm-4 col-6">
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text text-muted" style="font-size:0.72rem;min-width:36px;">#{{ $s+1 }}</span>
                                        <input type="text"
                                               class="form-control form-control-sm receive-serial-input"
                                               name="items[{{ $loop->index }}][serials][]"
                                               value="{{ $pendingSerials[$s] ?? '' }}"
                                               placeholder="S/N #{{ $s+1 }}"
                                               style="font-family:monospace;font-size:0.82rem;"
                                               required
                                               oninput="updateReceiveSerialCount({{ $loop->index }})">
                                    </div>
                                </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                    @endforeach

                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4" id="receiveSubmitBtn">
                        <i class="bi bi-check-circle"></i> Confirm & Receive Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- PAYMENT MODAL --}}
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

@push('scripts')
<script>
// Rebuild serial inputs in receive modal when qty changes
function rebuildReceiveSerials(idx, qty) {
    qty = parseInt(qty) || 0;
    const container = document.getElementById(`receive-serials-${idx}`);
    const existing  = [...container.querySelectorAll('.receive-serial-input')].map(i => i.value);
    container.innerHTML = '';

    for (let s = 0; s < qty; s++) {
        container.insertAdjacentHTML('beforeend', `
            <div class="col-md-3 col-sm-4 col-6">
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text text-muted" style="font-size:0.72rem;min-width:36px;">#${s+1}</span>
                    <input type="text"
                           class="form-control form-control-sm receive-serial-input"
                           name="items[${idx}][serials][]"
                           value="${existing[s] || ''}"
                           placeholder="S/N #${s+1}"
                           style="font-family:monospace;font-size:0.82rem;"
                           required
                           oninput="updateReceiveSerialCount(${idx})">
                </div>
            </div>`);
    }
    updateReceiveSerialCount(idx);
}

function updateReceiveSerialCount(idx) {
    const inputs  = document.querySelectorAll(`#receive-serials-${idx} .receive-serial-input`);
    const filled  = [...inputs].filter(i => i.value.trim() !== '').length;
    const total   = inputs.length;
    const counter = document.getElementById(`receive-serial-count-${idx}`);
    if (counter) {
        counter.textContent = `${filled} / ${total}`;
        counter.className   = filled === total && total > 0
            ? 'badge bg-success'
            : filled > 0 ? 'badge bg-warning text-dark' : 'badge bg-secondary';
    }
}

// Initialize counts on load
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[id^="receive-serials-"]').forEach(container => {
        const idx = container.id.replace('receive-serials-', '');
        updateReceiveSerialCount(idx);
    });

    // Submit guard for receive form
    const receiveForm = document.getElementById('receiveForm');
    if (receiveForm) {
        receiveForm.addEventListener('submit', function (e) {
            let allValid = true;
            document.querySelectorAll('[id^="receive-serials-"]').forEach(container => {
                const idx    = container.id.replace('receive-serials-', '');
                const qtyEl  = document.querySelector(`input[name="items[${idx}][quantity_received]"]`);
                const qty    = parseInt(qtyEl?.value) || 0;
                const inputs = container.querySelectorAll('.receive-serial-input');
                const filled = [...inputs].filter(i => i.value.trim() !== '').length;

                if (qty > 0 && filled !== qty) {
                    allValid = false;
                    const counter = document.getElementById(`receive-serial-count-${idx}`);
                    if (counter) { counter.className = 'badge bg-danger'; }
                }
            });
            if (!allValid) {
                e.preventDefault();
                alert('All serial numbers must be filled in and match the quantity received for each item.');
            }
        });
    }
});
</script>
@endpush

@endsection