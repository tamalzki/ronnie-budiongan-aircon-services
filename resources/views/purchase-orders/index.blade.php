@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-cart-plus text-primary"></i> Purchase Orders</h2>
            <p class="text-muted mb-0">Manage orders from suppliers</p>
        </div>
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-lg shadow-sm">
            <i class="bi bi-plus-circle"></i> New Purchase Order
        </a>
    </div>

    {{-- ⚠️ Overdue alert --}}
    @if($overdueOrders->count())
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-octagon-fill fs-3 me-3"></i>
            <div>
                <strong>{{ $overdueOrders->count() }} Overdue Purchase Order(s)!</strong>
                <p class="mb-0 small">
                    @foreach($overdueOrders as $o)
                        <a href="{{ route('purchase-orders.show', $o) }}" class="text-danger fw-bold">{{ $o->po_number }}</a>
                        ({{ $o->supplier->name }} — due {{ $o->payment_due_date->format('M d, Y') }}){{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- 🔔 10-day warning --}}
    @if($upcomingDeadlines->count())
    <div class="alert alert-warning border-0 shadow-sm mb-3">
        <div class="d-flex align-items-center">
            <i class="bi bi-bell-fill fs-3 me-3"></i>
            <div>
                <strong>{{ $upcomingDeadlines->count() }} Payment Deadline(s) within 10 days!</strong>
                <p class="mb-0 small">
                    @foreach($upcomingDeadlines as $u)
                        <a href="{{ route('purchase-orders.show', $u) }}" class="text-warning fw-bold">{{ $u->po_number }}</a>
                        ({{ $u->supplier->name }} — due {{ $u->payment_due_date->format('M d, Y') }},
                        {{ now()->diffInDays($u->payment_due_date) }} days left){{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Summary cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-cart-check fs-2 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Orders</div>
                        <h3 class="mb-0">{{ $purchaseOrders->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-clock-history fs-2 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Awaiting Delivery</div>
                        <h3 class="mb-0">{{ $purchaseOrders->where('status', 'pending')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Received</div>
                        <h3 class="mb-0">{{ $purchaseOrders->where('status', 'received')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="bg-danger bg-opacity-10 p-3 rounded">
                        <i class="bi bi-exclamation-triangle fs-2 text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Unpaid</div>
                        <h3 class="mb-0">{{ $purchaseOrders->where('payment_status', 'unpaid')->count() }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-4 py-3">PO Details</th>
                            <th class="border-0 px-4 py-3">Supplier</th>
                            <th class="border-0 px-4 py-3">Amount</th>
                            <th class="border-0 px-4 py-3">Payment</th>
                            <th class="border-0 px-4 py-3">Delivery Status</th>
                            <th class="border-0 px-4 py-3">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchaseOrders as $po)
                        @php
                            $daysLeft = $po->payment_due_date ? now()->diffInDays($po->payment_due_date, false) : null;
                            $rowClass = '';
                            if ($po->payment_type === '45days' && $po->payment_status !== 'paid') {
                                if ($daysLeft !== null && $daysLeft < 0) $rowClass = 'table-danger';
                                elseif ($daysLeft !== null && $daysLeft <= 10) $rowClass = 'table-warning';
                            }
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-4 py-3">
                                <a href="{{ route('purchase-orders.show', $po) }}"
                                   class="text-decoration-none fw-bold text-primary">
                                    {{ $po->po_number }}
                                </a>
                                @if($po->delivery_number)
                                    <br><small class="text-muted"><i class="bi bi-truck"></i> DR# {{ $po->delivery_number }}</small>
                                @endif
                                <br><small class="text-muted"><i class="bi bi-calendar3"></i> {{ $po->order_date->format('M d, Y') }}</small>
                            </td>
                            <td class="px-4 py-3">
                                <div class="fw-medium">{{ $po->supplier->name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="fw-bold">₱{{ number_format($po->total, 2) }}</div>
                                @if($po->balance > 0)
                                    <small class="text-danger">₱{{ number_format($po->balance, 2) }} due</small>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge rounded-pill {{ $po->payment_type == 'full' ? 'bg-success' : 'bg-info text-dark' }}">
                                    {{ $po->payment_type == 'full' ? 'Full Payment' : '45-Day Term' }}
                                </span>

                                @if($po->payment_type === '45days')
                                    <br>
                                    @if($po->payment_status == 'paid')
                                        <span class="badge bg-success mt-1"><i class="bi bi-check-circle"></i> Paid</span>
                                    @elseif($po->payment_status == 'partial')
                                        <span class="badge bg-warning mt-1"><i class="bi bi-hourglass-split"></i> Partial</span>
                                    @else
                                        <span class="badge bg-danger mt-1"><i class="bi bi-exclamation-triangle"></i> Unpaid</span>
                                    @endif

                                    @if($po->payment_due_date && $po->payment_status !== 'paid')
                                        <br>
                                        @if($daysLeft < 0)
                                            <small class="text-danger fw-bold">
                                                <i class="bi bi-alarm"></i> Overdue by {{ abs((int)$daysLeft) }} day(s)
                                            </small>
                                        @elseif($daysLeft <= 10)
                                            <small class="text-warning fw-bold">
                                                <i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }} day(s) left!
                                            </small>
                                        @else
                                            <small class="text-muted">Due {{ $po->payment_due_date->format('M d, Y') }}</small>
                                        @endif
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($po->status == 'received')
                                    <span class="badge bg-success px-3 py-2">
                                        <i class="bi bi-check-circle-fill"></i> Received
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark px-3 py-2">
                                        <i class="bi bi-clock-fill"></i> Awaiting Delivery
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="{{ route('purchase-orders.show', $po) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>

                                    @if($po->status == 'pending')
                                    <button class="btn btn-sm btn-outline-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#receiveModal{{ $po->id }}">
                                        <i class="bi bi-box-arrow-in-down"></i> Receive
                                    </button>
                                    @endif

                                    @if($po->payment_type === '45days' && $po->balance > 0)
                                    <button class="btn btn-sm btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paymentModal{{ $po->id }}">
                                        <i class="bi bi-cash-coin"></i> Pay
                                    </button>
                                    @endif

                                    @if($po->status == 'pending')
                                    <form action="{{ route('purchase-orders.destroy', $po) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Delete this purchase order?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                No purchase orders yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Receive Stock Modals --}}
@foreach($purchaseOrders->where('status', 'pending') as $po)
<div class="modal fade" id="receiveModal{{ $po->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.receive', $po) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-in-down"></i> Receive Stock — {{ $po->po_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="received_date"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-truck"></i> Delivery Receipt / DR Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="delivery_number"
                                   placeholder="e.g. DR-2026-00123" required>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Product (Brand — Model)</th>
                                    <th width="90">Ordered</th>
                                    <th width="90">Received</th>
                                    <th width="90">Net Cost</th>
                                    <th width="140">Receive Now</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($po->items as $item)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $item->product->brand->name ?? 'No Brand' }}
                                        — {{ $item->product->model ?? 'No Model' }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary">{{ $item->quantity_ordered }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $item->quantity_received }}</span>
                                    </td>
                                    <td class="text-center">
                                        <strong class="text-danger">
                                            ₱{{ number_format($item->discounted_cost ?? $item->unit_cost, 2) }}
                                        </strong>
                                    </td>
                                    <td>
                                        <input type="hidden" name="items[{{ $loop->index }}][id]"
                                               value="{{ $item->id }}">
                                        <input type="number" class="form-control form-control-sm"
                                               name="items[{{ $loop->index }}][quantity_received]"
                                               value="{{ $item->quantity_ordered - $item->quantity_received }}"
                                               min="0"
                                               max="{{ $item->quantity_ordered - $item->quantity_received }}"
                                               required>
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
@endforeach

{{-- Payment Modals --}}
@foreach($purchaseOrders->where('payment_type', '45days')->where('balance', '>', 0) as $po)
<div class="modal fade" id="paymentModal{{ $po->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.payment', $po) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-cash-coin"></i> Record Payment — {{ $po->po_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-4">
                            <div class="card border-0 bg-primary bg-opacity-10 text-center p-2">
                                <small class="text-muted">Total</small>
                                <strong class="text-primary">₱{{ number_format($po->total, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-success bg-opacity-10 text-center p-2">
                                <small class="text-muted">Paid</small>
                                <strong class="text-success">₱{{ number_format($po->amount_paid, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-danger bg-opacity-10 text-center p-2">
                                <small class="text-muted">Balance</small>
                                <strong class="text-danger">₱{{ number_format($po->balance, 2) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control" name="amount"
                                       value="{{ $po->balance }}" max="{{ $po->balance }}" min="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="payment_date"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
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
@endforeach
@endsection