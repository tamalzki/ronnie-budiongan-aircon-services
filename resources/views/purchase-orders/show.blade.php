@extends('layouts.app')
@section('title', 'PO — ' . $purchaseOrder->display_po_number)

@section('content')
<div class="container-fluid">

    {{-- Page header --}}
    <x-page-header title="PO No: {{ $purchaseOrder->display_po_number }}" subtitle="Purchase Order / Delivery Receipt" icon="bi-cart-plus">
        <x-slot name="actions">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('purchase-orders.pdf', $purchaseOrder) }}" class="btn btn-danger btn-sm">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
            @if($purchaseOrder->payment_type === '45days' && $purchaseOrder->balance > 0)
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#paymentModal">
                <i class="bi bi-cash-coin"></i> Record Payment
            </button>
            @endif
            @php $hasSold = $purchaseOrder->serials->where('status','sold')->isNotEmpty(); @endphp
            @unless($hasSold)
            <a href="{{ route('purchase-orders.edit', $purchaseOrder) }}" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <form action="{{ route('purchase-orders.destroy', $purchaseOrder) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete this purchase order? Stock and serials will be removed.')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i> Delete
                </button>
            </form>
            @else
            <span class="badge bg-secondary align-self-center" title="Some units already sold">
                <i class="bi bi-lock"></i> Locked (sold units)
            </span>
            @endunless
        </x-slot>
    </x-page-header>

    <x-flash />

    {{-- Deadline alerts --}}
    @if($deadlineAlert === 'overdue')
    <div class="alert alert-danger border-0 shadow-sm mb-3 py-2 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <span><strong>Payment Overdue!</strong> Due {{ $purchaseOrder->payment_due_date->format('M d, Y') }}.
        Balance: <strong>₱{{ number_format($purchaseOrder->balance, 2) }}</strong></span>
    </div>
    @elseif($deadlineAlert === 'warning')
    <div class="alert alert-warning border-0 shadow-sm mb-3 py-2 d-flex align-items-center gap-2">
        <i class="bi bi-bell-fill"></i>
        <span><strong>Payment due in {{ (int)$daysRemaining }} day(s).</strong>
        Deadline: {{ $purchaseOrder->payment_due_date->format('M d, Y') }}.
        Balance: <strong>₱{{ number_format($purchaseOrder->balance, 2) }}</strong></span>
    </div>
    @endif

    {{-- ── DELIVERY RECEIPT DOCUMENT CARD ── --}}
    <div class="card border-0 shadow-sm mb-3" style="font-size:0.875rem;">
        <div class="card-body p-0">

            {{-- DR header strip --}}
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2"
                 style="background:#f8fafc;">
                <div>
                    <div class="fw-bold text-uppercase" style="font-size:0.78rem;letter-spacing:.06em;color:#64748b;">
                        {{ $purchaseOrder->supplier->name }}
                    </div>
                    <div class="fw-bold" style="font-size:1.05rem;color:#1e293b;">DELIVERY RECEIPT</div>
                </div>
                <div class="d-flex flex-wrap gap-3" style="font-size:0.8rem;">
                    <div>
                        <span class="text-muted">Document No.</span>
                        <span class="fw-semibold ms-1">{{ $purchaseOrder->delivery_number ?: '—' }}</span>
                    </div>
                    <div>
                        <span class="text-muted">PO No.</span>
                        <span class="fw-semibold ms-1 text-primary">{{ $purchaseOrder->display_po_number }}</span>
                    </div>
                    <div>
                        <span class="text-muted">Date</span>
                        <span class="fw-semibold ms-1">{{ $purchaseOrder->order_date->format('m/d/Y') }}</span>
                    </div>
                    @if($purchaseOrder->received_date)
                    <div>
                        <span class="text-muted">Received</span>
                        <span class="fw-semibold ms-1 text-success">{{ $purchaseOrder->received_date->format('m/d/Y') }}</span>
                    </div>
                    @endif
                    <div>
                        @if($purchaseOrder->status === 'received')
                            <span class="badge bg-success">✓ Received</span>
                        @elseif($purchaseOrder->status === 'cancelled')
                            <span class="badge bg-secondary">Cancelled</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-hourglass-split"></i> Awaiting Receiving</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sold To / Delivered To --}}
            <div class="row g-0">
                <div class="col-md-6 px-4 py-3 border-end border-bottom">
                    <div class="fw-bold text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.07em;color:#64748b;">
                        <i class="bi bi-person-badge me-1"></i>Issued To
                    </div>
                    <div class="fw-bold">RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC</div>
                    <div class="text-muted" style="line-height:1.6;">
                        DOOR 7 SORONGON BUILDING QUEZON AVE. TRES DE MAYO<br>
                        DIGOS DAVAO DEL SUR 8002 PH 11<br>
                        TIN: 123-962-440-00000
                    </div>
                </div>
                <div class="col-md-6 px-4 py-3 border-bottom">
                    <div class="fw-bold text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.07em;color:#64748b;">
                        <i class="bi bi-truck me-1"></i>Delivered To
                    </div>
                    <div class="fw-bold">RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC</div>
                    <div class="text-muted">
                        DIGOS DAVAO DEL SUR
                    </div>
                    @if($purchaseOrder->notes)
                    <div class="mt-2 text-muted" style="font-size:0.78rem;white-space:pre-line;">{{ $purchaseOrder->notes }}</div>
                    @endif
                </div>
            </div>

            {{-- ── Items table ── --}}
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0 dr-table" style="font-size:0.82rem;">
                    <thead style="background:#f1f5f9;">
                        <tr>
                            <th class="px-3 py-2 text-center" style="width:42px;">Item</th>
                            <th class="px-3 py-2" style="width:110px;">Material</th>
                            <th class="px-3 py-2">Description</th>
                            <th class="px-3 py-2 text-center" style="width:54px;">QTY</th>
                            <th class="px-3 py-2">Serial Numbers</th>
                            <th class="px-3 py-2 text-end" style="width:100px;">Unit Cost</th>
                            <th class="px-3 py-2 text-center" style="width:90px;">Discount</th>
                            <th class="px-3 py-2 text-end" style="width:100px;">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                        @if($item->is_part)
                        @php
                            $remaining = max(0, $item->quantity_ordered - $item->quantity_received);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2">
                                <span class="fw-bold" style="font-family:monospace;font-size:0.8rem;">{{ $item->part->name }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="fw-semibold">{{ $item->part->name }}</div>
                                <div class="d-flex gap-1 mt-1 flex-wrap align-items-center">
                                    <span class="badge" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;font-size:0.63rem;">🔧 Aircon Part</span>
                                    <span class="text-muted" style="font-size:0.7rem;">For: {{ $item->part->linked_model_label ?? 'General / Unlinked' }}</span>
                                    @if($remaining > 0)
                                        <span class="badge bg-warning text-dark" style="font-size:0.63rem;">{{ $remaining }} to receive</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="fw-bold">{{ $item->quantity_ordered }}</span>
                                <div class="text-muted" style="font-size:0.68rem;">PC</div>
                            </td>
                            <td class="px-3 py-2">
                                <span class="text-muted" style="font-size:0.78rem;">Stock: {{ $item->part->stock_quantity }}</span>
                            </td>
                            <td class="px-3 py-2 text-end">₱{{ number_format($item->unit_cost, 2) }}</td>
                        @else
                        @php
                            $isSetItem      = $item->is_set && $item->product->pairedProduct;
                            $itemSerials    = $purchaseOrder->serials->where('product_id', $item->product_id)->sortBy('serial_number');
                            $outdoorSerials = $isSetItem
                                ? $purchaseOrder->serials->where('product_id', $item->product->paired_product_id)->sortBy('serial_number')
                                : collect();
                            $allItemSerials = $itemSerials->concat($outdoorSerials);
                            $inStockCount   = $allItemSerials->where('status','in_stock')->count();
                            $soldCount      = $allItemSerials->where('status','sold')->count();
                            $remaining      = max(0, $item->quantity_ordered - $item->quantity_received);
                        @endphp
                        <tr>
                            <td class="px-3 py-2 text-center text-muted fw-semibold">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2">
                                <span class="fw-bold" style="font-family:monospace;font-size:0.8rem;">{{ $isSetItem ? $item->product->set_model_label : $item->product->model }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="fw-semibold">{{ trim(($item->product->brand->name ?? '') . ' ' . ($isSetItem ? $item->product->set_model_label : $item->product->model)) }}</div>
                                <div class="d-flex gap-1 mt-1 flex-wrap">
                                    @if($isSetItem)
                                        <span class="badge" style="background:#f3e8ff;color:#7c3aed;border:1px solid #c4b5fd;font-size:0.63rem;">❄️🌀 Indoor + Outdoor Set</span>
                                    @elseif($item->product->unit_type === 'indoor')
                                        <span class="badge" style="background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;font-size:0.63rem;">❄️ Indoor</span>
                                    @elseif($item->product->unit_type === 'outdoor')
                                        <span class="badge" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.63rem;">🌀 Outdoor</span>
                                    @endif
                                    @if($remaining > 0)
                                        <span class="badge bg-warning text-dark" style="font-size:0.63rem;">{{ $remaining }} to receive</span>
                                    @endif
                                    @if($soldCount > 0)
                                        <span class="badge bg-primary" style="font-size:0.63rem;">{{ $soldCount }} sold</span>
                                    @endif
                                    @if($inStockCount > 0)
                                        <span class="badge bg-success" style="font-size:0.63rem;">{{ $inStockCount }} in stock</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="fw-bold">{{ $item->quantity_ordered }}</span>
                                <div class="text-muted" style="font-size:0.68rem;">{{ $isSetItem ? 'SET' : 'PC' }}</div>
                            </td>
                            <td class="px-3 py-2">
                                @if($allItemSerials->count() > 0)
                                    @foreach([['label' => $isSetItem ? '❄️ ' . $item->product->model : null, 'serials' => $itemSerials],
                                              ['label' => $isSetItem ? '🌀 ' . ($item->product->pairedProduct->model ?? '') : null, 'serials' => $outdoorSerials]] as $group)
                                        @if($group['serials']->count() > 0)
                                        <div class="mb-1">
                                            @if($group['label'])
                                                <div class="text-muted" style="font-size:0.65rem;font-weight:600;">{{ $group['label'] }}</div>
                                            @endif
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($group['serials'] as $serial)
                                                <span class="px-2 py-0 rounded border d-inline-flex align-items-center gap-1"
                                                      style="font-size:0.7rem;
                                                             background:{{ $serial->status === 'sold' ? '#eff6ff' : '#f0fdf4' }};
                                                             border-color:{{ $serial->status === 'sold' ? '#93c5fd' : '#86efac' }} !important;">
                                                    <code style="font-size:0.7rem;letter-spacing:.02em;">{{ $serial->serial_number }}</code>
                                                    @if($serial->status === 'sold')
                                                        <i class="bi bi-cart-check-fill text-primary" style="font-size:0.6rem;" title="Sold"></i>
                                                    @else
                                                        <i class="bi bi-check-circle-fill text-success" style="font-size:0.6rem;" title="In Stock"></i>
                                                    @endif
                                                </span>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif
                                    @endforeach
                                @else
                                <span class="text-muted fst-italic" style="font-size:0.78rem;">No serials yet — receive to encode</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-end">₱{{ number_format($item->unit_cost, 2) }}</td>
                        @endif
                            <td class="px-3 py-2 text-center">
                                @if($item->discount_percent > 0)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success" style="font-size:0.7rem;">
                                        {{ number_format($item->discount_percent, 2) }}%
                                    </span>
                                @elseif($item->discount_amount > 0)
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary" style="font-size:0.7rem;">
                                        ₱{{ number_format($item->discount_amount, 2) }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-end fw-semibold">₱{{ number_format($item->total_cost, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot style="background:#f8fafc;">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-end fw-bold text-muted" style="font-size:0.8rem;">
                                Grand Total
                            </td>
                            <td class="px-3 py-2 text-center fw-bold">
                                {{ $purchaseOrder->items->sum('quantity_ordered') }} PC
                            </td>
                            <td></td>
                            <td colspan="2" class="px-3 py-2 text-end text-muted fw-semibold" style="font-size:0.78rem;">Total Amount</td>
                            <td class="px-3 py-2 text-end fw-bold text-primary" style="font-size:1rem;">
                                ₱{{ number_format($purchaseOrder->total, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>

    {{-- ── ORDER RECEIVE (encode serials when units arrive — opens as a modal) ── --}}
    @php
        $itemsToReceive = $purchaseOrder->items->filter(fn($i) => ($i->quantity_ordered - $i->quantity_received) > 0);
        $unitsToReceive = $itemsToReceive->sum(fn($i) => $i->quantity_ordered - $i->quantity_received);
    @endphp
    @if($purchaseOrder->status !== 'cancelled' && $itemsToReceive->count() > 0)
    <div class="alert mb-3 py-2 px-3 d-flex flex-wrap align-items-center justify-content-between gap-2 border-0 shadow-sm"
         id="receive" style="background:#fffbeb;border-left:4px solid #f59e0b !important;font-size:0.875rem;">
        <span class="fw-semibold text-dark">
            <i class="bi bi-box-arrow-in-down text-warning me-1"></i>
            {{ $unitsToReceive }} unit(s) awaiting receiving — encode serial numbers to put stock into inventory.
        </span>
        <button type="button" class="btn btn-warning btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#receiveModal">
            <i class="bi bi-upc-scan"></i> Receive Stock
        </button>
    </div>
    @endif

    {{-- ── Payment Summary + History ── --}}
    <div class="row g-3">

        {{-- Payment summary --}}
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100" style="font-size:0.875rem;">
                <div class="card-header border-0 py-2 px-3 bg-light">
                    <span class="fw-semibold small"><i class="bi bi-credit-card text-primary me-1"></i>Payment Summary</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-borderless mb-0">
                        <tr class="border-bottom">
                            <td class="px-3 py-2 text-muted">Payment Type</td>
                            <td class="px-3 py-2 text-end">
                                <span class="badge {{ $purchaseOrder->payment_type == 'full' ? 'bg-success' : 'bg-info text-dark' }}">
                                    {{ $purchaseOrder->payment_type == 'full' ? 'Full Payment' : '45-Day Term' }}
                                </span>
                            </td>
                        </tr>
                        @if($purchaseOrder->payment_type === '45days')
                        <tr class="border-bottom">
                            <td class="px-3 py-2 text-muted">Due Date</td>
                            <td class="px-3 py-2 text-end">
                                <div class="d-flex align-items-center justify-content-end gap-2 flex-wrap">
                                    <span>{{ $purchaseOrder->payment_due_date?->format('M d, Y') ?? '—' }}</span>
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
                                    <button class="btn btn-outline-secondary btn-sm" style="padding:1px 6px;font-size:0.7rem"
                                            onclick="document.getElementById('editDueDateForm').classList.toggle('d-none')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    @endif
                                </div>
                                <form id="editDueDateForm" class="d-none mt-2 d-flex gap-1 align-items-center justify-content-end"
                                      action="{{ route('purchase-orders.update-due-date', $purchaseOrder) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <input type="date" name="payment_due_date" class="form-control form-control-sm"
                                           value="{{ optional($purchaseOrder->payment_due_date)->format('Y-m-d') }}"
                                           style="max-width:150px" required>
                                    <button type="submit" class="btn btn-primary btn-sm">Save</button>
                                    <button type="button" class="btn btn-light btn-sm"
                                            onclick="document.getElementById('editDueDateForm').classList.add('d-none')">✕</button>
                                </form>
                            </td>
                        </tr>
                        @endif
                        <tr class="border-bottom">
                            <td class="px-3 py-2 text-muted">Total</td>
                            <td class="px-3 py-2 text-end fw-bold text-primary">₱{{ number_format($purchaseOrder->total, 2) }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="px-3 py-2 text-muted">Amount Paid</td>
                            <td class="px-3 py-2 text-end fw-semibold text-success">₱{{ number_format($purchaseOrder->amount_paid, 2) }}</td>
                        </tr>
                        <tr class="border-bottom">
                            <td class="px-3 py-2 text-muted">Balance</td>
                            <td class="px-3 py-2 text-end fw-bold {{ $purchaseOrder->balance > 0 ? 'text-danger' : 'text-success' }}">
                                @if($purchaseOrder->balance > 0)
                                    ₱{{ number_format($purchaseOrder->balance, 2) }}
                                @else
                                    <i class="bi bi-check-circle"></i> Fully Paid
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 text-muted">Status</td>
                            <td class="px-3 py-2 text-end">
                                <span class="badge bg-{{ $purchaseOrder->payment_status == 'paid' ? 'success' : ($purchaseOrder->payment_status == 'partial' ? 'warning text-dark' : 'danger') }}">
                                    {{ ucfirst($purchaseOrder->payment_status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment history --}}
        <div class="col-md-7">
            @if($purchaseOrder->payments->count() > 0)
            <div class="card border-0 shadow-sm h-100" style="font-size:0.875rem;">
                <div class="card-header border-0 py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-semibold small"><i class="bi bi-receipt text-primary me-1"></i>Payment History</span>
                    <span class="badge bg-secondary" style="font-size:0.68rem;">{{ $purchaseOrder->payments->count() }} payment(s)</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3 py-2">#</th>
                                <th class="px-3 py-2">Date</th>
                                <th class="px-3 py-2 text-end">Amount</th>
                                <th class="px-3 py-2">Method</th>
                                <th class="px-3 py-2">Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchaseOrder->payments as $payment)
                            <tr>
                                <td class="px-3 py-2 text-muted">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2" style="white-space:nowrap">{{ $payment->payment_date->format('M d, Y') }}</td>
                                <td class="px-3 py-2 text-end fw-semibold text-success">₱{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-3 py-2" style="white-space:nowrap">
                                    @php $methodIcons = ['cash'=>'💵','gcash'=>'📱','bank_transfer'=>'🏦','cheque'=>'🧾']; @endphp
                                    {{ $methodIcons[$payment->payment_method] ?? '' }}
                                    {{ ucfirst(str_replace('_',' ',$payment->payment_method)) }}
                                </td>
                                <td class="px-3 py-2 text-muted">{{ $payment->reference_number ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td colspan="2" class="px-3 py-2 text-end text-muted fw-semibold" style="font-size:0.78rem;">Total Paid</td>
                                <td class="px-3 py-2 text-end fw-bold text-success">₱{{ number_format($purchaseOrder->amount_paid, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            @else
            <div class="card border-0 shadow-sm h-100 d-flex align-items-center justify-content-center text-muted"
                 style="font-size:0.875rem;min-height:80px;">
                <div class="text-center py-4">
                    <i class="bi bi-receipt fs-3 d-block mb-2 opacity-30"></i>
                    <small>No payment records yet.</small>
                </div>
            </div>
            @endif
        </div>

    </div>{{-- end row --}}

</div>{{-- end container --}}

{{-- ORDER RECEIVE MODAL --}}
@include('purchase-orders.partials.receive-modal', [
    'purchaseOrder' => $purchaseOrder,
    'modalId'       => 'receiveModal',
    'autoOpen'      => false,
])
@include('purchase-orders.partials.receive-modal-scripts')

{{-- PAYMENT MODAL --}}
@if($purchaseOrder->payment_type === '45days' && $purchaseOrder->balance > 0)
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.payment', $purchaseOrder) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Record Payment — {{ $purchaseOrder->display_po_number }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-2 mb-4 text-center">
                        <div class="col-4">
                            <div class="card border-0 bg-primary bg-opacity-10 p-2">
                                <small class="text-muted">Total</small>
                                <strong class="text-primary small">₱{{ number_format($purchaseOrder->total, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-success bg-opacity-10 p-2">
                                <small class="text-muted">Paid</small>
                                <strong class="text-success small">₱{{ number_format($purchaseOrder->amount_paid, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-danger bg-opacity-10 p-2">
                                <small class="text-muted">Balance</small>
                                <strong class="text-danger small">₱{{ number_format($purchaseOrder->balance, 2) }}</strong>
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
                                <option value="">-- Select --</option>
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

<style>
.dr-table thead th {
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: .06em;
    text-transform: uppercase;
    color: #64748b;
    white-space: nowrap;
    border-bottom: 2px solid #e2e8f0;
}
.dr-table tbody td {
    border-bottom: 1px solid #f1f5f9;
    vertical-align: top;
}
.dr-table tbody tr:last-child td { border-bottom: none; }
.dr-table tbody tr:hover td { background: rgba(79,70,229,.03); }
</style>

@endsection
