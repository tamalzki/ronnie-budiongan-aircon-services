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
        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-circle"></i> New Purchase Order
        </a>
    </div>

    {{-- Alerts --}}
    @if($overdueOrders->count())
    <div class="alert alert-danger border-0 shadow-sm mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-exclamation-octagon-fill fs-5 flex-shrink-0"></i>
        <div>
            <strong>{{ $overdueOrders->count() }} Overdue Order(s):</strong>
            @foreach($overdueOrders as $o)
                <a href="{{ route('purchase-orders.show', $o) }}" class="text-danger fw-bold">{{ $o->po_number }}</a>
                <span class="text-muted small">({{ $o->supplier->name }}, due {{ $o->payment_due_date->format('M d') }}){{ !$loop->last ? ' · ' : '' }}</span>
            @endforeach
        </div>
    </div>
    @endif

    @if($upcomingDeadlines->count())
    <div class="alert alert-warning border-0 shadow-sm mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-bell-fill fs-5 flex-shrink-0"></i>
        <div>
            <strong>{{ $upcomingDeadlines->count() }} Due within 10 days:</strong>
            @foreach($upcomingDeadlines as $u)
                <a href="{{ route('purchase-orders.show', $u) }}" class="text-warning fw-bold">{{ $u->po_number }}</a>
                <span class="text-muted small">({{ now()->diffInDays($u->payment_due_date) }}d left){{ !$loop->last ? ' · ' : '' }}</span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="bi bi-cart-check fs-4 text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Total Orders</div>
                        <div class="fw-bold fs-5">{{ $totalCount }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="bg-warning bg-opacity-10 rounded p-2">
                        <i class="bi bi-clock-history fs-4 text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Awaiting</div>
                        <div class="fw-bold fs-5">{{ $awaitingCount }}</div>
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
                        <div class="text-muted small">Received</div>
                        <div class="fw-bold fs-5">{{ $receivedCount }}</div>
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
                        <div class="text-muted small">Unpaid</div>
                        <div class="fw-bold fs-5">{{ $unpaidCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-0 border-bottom-0" id="poTabs" role="tablist" style="font-size:0.9rem;">
        <li class="nav-item">
            <button class="nav-link active px-4" data-bs-toggle="tab" data-bs-target="#tab-all-orders">
                <i class="bi bi-list-ul"></i> All Orders
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link px-4" data-bs-toggle="tab" data-bs-target="#tab-payments-due">
                <i class="bi bi-calendar-event"></i> Payments Due
                @if($paymentsDueCount > 0)
                    <span class="badge bg-danger ms-1">{{ $paymentsDueCount }}</span>
                @endif
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm mb-4" id="poTabContent">

        {{-- ═══════════════════════════════════════════════════
             TAB 1: ALL ORDERS
        ════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade show active" id="tab-all-orders">

            {{-- Search & Filters --}}
            <div class="border-bottom py-3 px-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" id="poSearch" class="form-control border-start-0"
                                   placeholder="Search supplier, PO number, DR...">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <select id="statusFilter" class="form-select form-select-sm">
                            <option value="">All Delivery</option>
                            <option value="received">Received</option>
                            <option value="pending">Awaiting</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select id="paymentFilter" class="form-select form-select-sm">
                            <option value="">All Payment</option>
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()" title="Clear">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="poTable" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Supplier</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Order Date</th>
                            <th class="border-0 px-3 py-2">Amount</th>
                            <th class="border-0 px-3 py-2">Payment</th>
                            <th class="border-0 px-3 py-2">Delivery</th>
                            <th class="border-0 px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="poTableBody">
                        @forelse($purchaseOrders as $po)
                        @php
                            $daysLeft = $po->payment_due_date ? now()->diffInDays($po->payment_due_date, false) : null;
                            $rowClass = '';
                            if ($po->payment_type === '45days' && $po->payment_status !== 'paid') {
                                if ($daysLeft !== null && $daysLeft <= 10) $rowClass = 'table-danger';
                            }
                        @endphp
                        <tr class="{{ $rowClass }} po-row"
                            data-po="{{ strtolower($po->po_number) }}"
                            data-supplier="{{ strtolower($po->supplier->name) }}"
                            data-dr="{{ strtolower($po->delivery_number ?? '') }}"
                            data-status="{{ $po->status }}"
                            data-payment="{{ $po->payment_status }}">
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="fw-semibold">{{ $po->supplier->name }}</div>
                                <small class="text-muted">
                                    <a href="{{ route('purchase-orders.show', $po) }}" class="text-muted text-decoration-none">
                                        {{ $po->po_number }}
                                    </a>
                                    @if($po->delivery_number)
                                        · <i class="bi bi-truck"></i> {{ $po->delivery_number }}
                                    @endif
                                </small>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div>{{ $po->order_date->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $po->order_date->diffForHumans() }}</small>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="fw-semibold">₱{{ number_format($po->total, 2) }}</div>
                                @if($po->balance > 0)
                                    <small class="text-danger">₱{{ number_format($po->balance, 2) }} due</small>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge {{ $po->payment_type == 'full' ? 'bg-success' : 'bg-info text-dark' }}">
                                    {{ $po->payment_type == 'full' ? 'Full' : '45-Day' }}
                                </span>
                                @if($po->payment_type === '45days')
                                    @if($po->payment_status == 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($po->payment_status == 'partial')
                                        <span class="badge bg-warning text-dark">Partial</span>
                                    @else
                                        <span class="badge bg-danger">Unpaid</span>
                                    @endif
                                    @if($po->payment_due_date && $po->payment_status !== 'paid')
                                        <br>
                                        @if($daysLeft < 0)
                                            <small class="text-danger fw-bold"><i class="bi bi-alarm"></i> Overdue {{ abs((int)$daysLeft) }}d</small>
                                        @elseif($daysLeft <= 10)
                                            <small class="text-danger fw-bold"><i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }}d left</small>
                                        @else
                                            <small class="text-muted">Due {{ $po->payment_due_date->format('M d, Y') }}</small>
                                        @endif
                                    @endif
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($po->status == 'received')
                                    <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Received</span>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock-fill"></i> Awaiting</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('purchase-orders.show', $po) }}"
                                       class="btn btn-outline-primary"
                                       style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if($po->status == 'pending')
                                    <a href="{{ route('purchase-orders.edit', $po) }}"
                                       class="btn btn-warning"
                                       style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    @endif
                                    @if($po->status == 'pending')
                                    <button class="btn btn-outline-success"
                                            data-bs-toggle="modal"
                                            data-bs-target="#receiveModal{{ $po->id }}"
                                            style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-box-arrow-in-down"></i> Receive
                                    </button>
                                    @endif
                                    @if($po->payment_type === '45days' && $po->balance > 0)
                                    <button class="btn btn-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#paymentModal{{ $po->id }}"
                                            style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-cash-coin"></i> Pay
                                    </button>
                                    @endif
                                    @if($po->status == 'pending')
                                    <form action="{{ route('purchase-orders.destroy', $po) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Delete this purchase order?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger"
                                                style="padding:2px 8px;font-size:0.78rem">
                                            <i class="bi bi-trash">delete</i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No purchase orders yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($purchaseOrders->hasPages())
            <div class="d-flex justify-content-between align-items-center px-4 py-3 border-top bg-light">
                <small class="text-muted">
                    Showing {{ $purchaseOrders->firstItem() }}–{{ $purchaseOrders->lastItem() }}
                    of {{ $purchaseOrders->total() }} orders
                </small>
                {{ $purchaseOrders->links() }}
            </div>
            @endif

        </div>{{-- end tab-all-orders --}}

        {{-- ═══════════════════════════════════════════════════
             TAB 2: PAYMENTS DUE (45-day terms only)
        ════════════════════════════════════════════════════ --}}
        <div class="tab-pane fade p-4" id="tab-payments-due">

            <h6 class="fw-semibold mb-3">
                <i class="bi bi-calendar-event text-primary"></i> Payment Schedule — 45-Day Terms Only
            </h6>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">PO Number</th>
                            <th class="border-0 px-3 py-2">Supplier</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Order Date</th>
                            <th class="border-0 px-3 py-2" style="white-space:nowrap">Due Date</th>
                            <th class="border-0 px-3 py-2">Total</th>
                            <th class="border-0 px-3 py-2">Paid</th>
                            <th class="border-0 px-3 py-2">Balance</th>
                            <th class="border-0 px-3 py-2">Status</th>
                            <th class="border-0 px-3 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($paymentsDue as $po)
                        @php
                            $daysLeft = $po->payment_due_date ? now()->diffInDays($po->payment_due_date, false) : null;
                            $rowClass = '';
                            if ($daysLeft !== null && $daysLeft < 0) $rowClass = 'table-danger';
                            elseif ($daysLeft !== null && $daysLeft <= 10) $rowClass = 'table-warning';
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="text-decoration-none fw-semibold text-primary">
                                    {{ $po->po_number }}
                                </a>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">{{ $po->supplier->name }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div>{{ $po->payment_due_date->format('M d, Y') }}</div>
                                @if($daysLeft < 0)
                                    <small class="text-danger fw-bold"><i class="bi bi-alarm"></i> Overdue {{ abs((int)$daysLeft) }}d</small>
                                @elseif($daysLeft == 0)
                                    <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> Due Today</small>
                                @elseif($daysLeft <= 10)
                                    <small class="text-warning fw-bold"><i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }}d left</small>
                                @else
                                    <small class="text-muted">{{ (int)$daysLeft }}d left</small>
                                @endif
                            </td>
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">₱{{ number_format($po->total, 2) }}</td>
                            <td class="px-3 py-2 text-success" style="white-space:nowrap">₱{{ number_format($po->amount_paid, 2) }}</td>
                            <td class="px-3 py-2 fw-semibold text-danger" style="white-space:nowrap">₱{{ number_format($po->balance, 2) }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($po->payment_status == 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($po->payment_status == 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                @if($po->balance > 0)
                                <button class="btn btn-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#paymentModal{{ $po->id }}"
                                        style="padding:2px 8px;font-size:0.78rem">
                                    <i class="bi bi-cash-coin"></i> Pay
                                </button>
                                @else
                                <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Paid</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                                No outstanding payments — all settled!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>{{-- end tab-payments-due --}}

    </div>{{-- end tab-content --}}

</div>{{-- end container-fluid --}}

{{-- Receive Stock Modals --}}
@foreach($purchaseOrders->getCollection()->where('status', 'pending') as $po)
<div class="modal fade" id="receiveModal{{ $po->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.receive', $po) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Receive Stock — {{ $po->po_number }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="received_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold"><i class="bi bi-truck"></i> DR Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="delivery_number" placeholder="e.g. DR-2026-00123" required>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center" width="90">Ordered</th>
                                    <th class="text-center" width="90">Received</th>
                                    <th class="text-center" width="90">Net Cost</th>
                                    <th width="120">Receive Now</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($po->items as $item)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $item->product->brand->name ?? 'No Brand' }} — {{ $item->product->model ?? 'No Model' }}
                                    </td>
                                    <td class="text-center"><span class="badge bg-primary">{{ $item->quantity_ordered }}</span></td>
                                    <td class="text-center"><span class="badge bg-success">{{ $item->quantity_received }}</span></td>
                                    <td class="text-center fw-semibold text-danger">₱{{ number_format($item->discounted_cost ?? $item->unit_cost, 2) }}</td>
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
                    <button type="submit" class="btn btn-success px-4"><i class="bi bi-check-circle"></i> Receive Stock</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- Payment Modals --}}
@foreach($purchaseOrders->getCollection()->where('payment_type', '45days')->where('balance', '>', 0) as $po)
<div class="modal fade" id="paymentModal{{ $po->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.payment', $po) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Record Payment — {{ $po->po_number }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-2 mb-4">
                        <div class="col-4">
                            <div class="card border-0 bg-primary bg-opacity-10 text-center p-2">
                                <small class="text-muted">Total</small>
                                <strong class="text-primary small">₱{{ number_format($po->total, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-success bg-opacity-10 text-center p-2">
                                <small class="text-muted">Paid</small>
                                <strong class="text-success small">₱{{ number_format($po->amount_paid, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="card border-0 bg-danger bg-opacity-10 text-center p-2">
                                <small class="text-muted">Balance</small>
                                <strong class="text-danger small">₱{{ number_format($po->balance, 2) }}</strong>
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
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-circle"></i> Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
function filterTable() {
    const search  = document.getElementById('poSearch').value.toLowerCase();
    const status  = document.getElementById('statusFilter').value;
    const payment = document.getElementById('paymentFilter').value;
    const rows    = document.querySelectorAll('.po-row');
    let visible   = 0;

    rows.forEach(row => {
        const matchSearch  = !search  || row.dataset.po.includes(search)
                                      || row.dataset.supplier.includes(search)
                                      || row.dataset.dr.includes(search);
        const matchStatus  = !status  || row.dataset.status === status;
        const matchPayment = !payment || row.dataset.payment === payment;

        if (matchSearch && matchStatus && matchPayment) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    let noResults = document.getElementById('noResultsRow');
    if (visible === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('poTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

function clearFilters() {
    document.getElementById('poSearch').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('paymentFilter').value = '';
    filterTable();
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('poSearch').addEventListener('input', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    document.getElementById('paymentFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@endsection