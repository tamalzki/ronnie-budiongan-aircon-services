@extends('layouts.app')

@section('title', 'Purchase Orders')

@push('styles')
<style>
    /* Payments Due tab — amber */
    #tab-payments-due-btn {
        color: #92400e !important;
        background: #fffbeb !important;
        border-color: #fcd34d #fcd34d transparent !important;
    }
    #tab-payments-due-btn:hover { background: #fef3c7 !important; }
    #tab-payments-due-btn.active {
        color: #92400e !important;
        background: #fff !important;
        border-color: #f59e0b #f59e0b #fff !important;
        border-top-width: 2px !important;
    }
    #tab-payments-due-btn .badge.bg-secondary { background: #d97706 !important; }

    /* Goods Receipts tab — green */
    #tab-receipts-btn {
        color: #14532d !important;
        background: #f0fdf4 !important;
        border-color: #86efac #86efac transparent !important;
    }
    #tab-receipts-btn:hover { background: #dcfce7 !important; }
    #tab-receipts-btn.active {
        color: #14532d !important;
        background: #fff !important;
        border-color: #22c55e #22c55e #fff !important;
        border-top-width: 2px !important;
    }
    #tab-receipts-btn .badge.bg-secondary { background: #16a34a !important; }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <x-page-header title="Purchase Orders" subtitle="Manage orders from suppliers" icon="bi-cart-plus">
        <x-slot name="actions">
            <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> New Purchase Order
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

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
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card app-card-panel">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-primary bg-opacity-10 rounded p-2">
                        <i class="bi bi-cart-check text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.72rem;">Total Orders</div>
                        <div class="fw-bold fs-6">{{ $totalCount }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card-panel">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-warning bg-opacity-10 rounded p-2">
                        <i class="bi bi-clock-history text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.72rem;">Awaiting</div>
                        <div class="fw-bold fs-6">{{ $awaitingCount }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card-panel">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-success bg-opacity-10 rounded p-2">
                        <i class="bi bi-check-circle text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.72rem;">Received</div>
                        <div class="fw-bold fs-6">{{ $receivedCount }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card app-card-panel">
                <div class="card-body d-flex align-items-center gap-2 py-2 px-3">
                    <div class="bg-danger bg-opacity-10 rounded p-2">
                        <i class="bi bi-exclamation-triangle text-danger"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.72rem;">Unpaid</div>
                        <div class="fw-bold fs-6">{{ $unpaidCount }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="d-flex align-items-end gap-2 mb-0">
        <ul class="nav nav-tabs flex-grow-1" id="poTabs" role="tablist" style="font-size:0.875rem; border-bottom: none;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active px-4 py-2" id="tab-all-orders-btn"
                        type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tab-all-orders"
                        aria-controls="tab-all-orders" aria-selected="true">
                    <i class="bi bi-list-ul me-1"></i> All Orders
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-4 py-2" id="tab-receipts-btn"
                        type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tab-receipts"
                        aria-controls="tab-receipts" aria-selected="false"
                        style="font-weight:600;">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Goods Receipts
                    @if($pendingToReceive->count() > 0)
                        <span class="badge bg-danger ms-1">{{ $pendingToReceive->count() }}</span>
                    @else
                        <span class="badge bg-secondary ms-1">0</span>
                    @endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link px-4 py-2 position-relative" id="tab-payments-due-btn"
                        type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tab-payments-due"
                        aria-controls="tab-payments-due" aria-selected="false"
                        style="font-weight:600;">
                    <i class="bi bi-cash-stack me-1"></i> Payments Due
                    @if($paymentsDueCount > 0)
                        <span class="badge bg-danger ms-1">{{ $paymentsDueCount }}</span>
                    @else
                        <span class="badge bg-secondary ms-1">{{ $paymentsDue->count() }}</span>
                    @endif
                </button>
            </li>
        </ul>
    </div>

    <div class="tab-content border border-top-0 rounded-bottom bg-white shadow-sm mb-4" id="poTabContent">

        {{-- ═══ TAB 1: ALL ORDERS ═══ --}}
        <div class="tab-pane fade show active" id="tab-all-orders" role="tabpanel" aria-labelledby="tab-all-orders-btn">

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
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="poTable" style="font-size:0.82rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Supplier / PO</th>
                            <th class="border-0 px-2 py-2" style="white-space:nowrap">Order Date</th>
                            <th class="border-0 px-2 py-2">Amount</th>
                            <th class="border-0 px-2 py-2">Payment</th>
                            <th class="border-0 px-2 py-2">Delivery</th>
                            <th class="border-0 px-2 py-2 text-center">Actions</th>
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
                            <td class="px-3 py-1" style="white-space:nowrap">
                                <div class="fw-semibold" style="font-size:0.83rem;">{{ $po->supplier->name }}</div>
                                <div class="text-muted" style="font-size:0.72rem;">
                                    <a href="{{ route('purchase-orders.show', $po) }}" class="text-muted text-decoration-none">
                                        {{ $po->po_number }}
                                    </a>
                                    @if($po->delivery_number)
                                        &middot; <i class="bi bi-truck"></i> {{ $po->delivery_number }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div>{{ $po->order_date->format('M d, Y') }}</div>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $po->order_date->diffForHumans() }}</div>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div class="fw-semibold">₱{{ number_format($po->total, 2) }}</div>
                                @if($po->balance > 0)
                                    <div class="text-danger" style="font-size:0.72rem;">₱{{ number_format($po->balance, 2) }} due</div>
                                @endif
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <span class="badge {{ $po->payment_type == 'full' ? 'bg-success' : 'bg-info text-dark' }}" style="font-size:0.7rem;">
                                        {{ $po->payment_type == 'full' ? 'Full' : '45-Day' }}
                                    </span>
                                    @if($po->payment_type === '45days')
                                        @if($po->payment_status == 'paid')
                                            <span class="badge bg-success" style="font-size:0.7rem;">Paid</span>
                                        @elseif($po->payment_status == 'partial')
                                            <span class="badge bg-warning text-dark" style="font-size:0.7rem;">Partial</span>
                                        @else
                                            <span class="badge bg-danger" style="font-size:0.7rem;">Unpaid</span>
                                        @endif
                                    @endif
                                </div>
                                @if($po->payment_type === '45days' && $po->payment_due_date && $po->payment_status !== 'paid')
                                    @if($daysLeft < 0)
                                        <div class="text-danger fw-bold" style="font-size:0.72rem;"><i class="bi bi-alarm"></i> Overdue {{ abs((int)$daysLeft) }}d</div>
                                    @elseif($daysLeft <= 10)
                                        <div class="text-danger fw-bold" style="font-size:0.72rem;"><i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }}d left</div>
                                    @else
                                        <div class="text-muted" style="font-size:0.72rem;">Due {{ $po->payment_due_date->format('M d') }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                @if($po->status == 'received')
                                    <span class="badge bg-success" style="font-size:0.7rem;"><i class="bi bi-check-circle-fill"></i> Received</span>
                                @else
                                    <span class="badge bg-warning text-dark" style="font-size:0.7rem;"><i class="bi bi-clock-fill"></i> Awaiting</span>
                                @endif
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div class="d-flex gap-1 flex-wrap">
                                    <a href="{{ route('purchase-orders.show', $po) }}"
                                       class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size:0.75rem;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    @if($po->status == 'pending')
                                    <a href="{{ route('purchase-orders.edit', $po) }}"
                                       class="btn btn-warning btn-sm py-0 px-2" style="font-size:0.75rem;">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <button type="button" class="btn btn-outline-success btn-sm py-0 px-2"
                                            data-bs-toggle="modal" data-bs-target="#receiveModal{{ $po->id }}"
                                            style="font-size:0.75rem;">
                                        <i class="bi bi-box-arrow-in-down"></i> Receive
                                    </button>
                                    @endif
                                    @if($po->payment_type === '45days' && $po->balance > 0)
                                    <button type="button" class="btn btn-primary btn-sm py-0 px-2"
                                            data-bs-toggle="modal" data-bs-target="#paymentModal{{ $po->id }}"
                                            style="font-size:0.75rem;">
                                        <i class="bi bi-cash-coin"></i> Pay
                                    </button>
                                    @endif
                                    @if($po->status == 'pending')
                                    <form action="{{ route('purchase-orders.destroy', $po) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Delete this purchase order?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" style="font-size:0.75rem;">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
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

        {{-- ═══ TAB 2: GOODS RECEIPTS ═══ --}}
        <div class="tab-pane fade" id="tab-receipts" role="tabpanel" aria-labelledby="tab-receipts-btn">

            @if($pendingToReceive->count() > 0)
            <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                <p class="text-muted mb-0" style="font-size:0.82rem;">
                    <i class="bi bi-box-arrow-in-down text-success"></i>
                    <strong class="text-success">{{ $pendingToReceive->count() }}</strong> order(s) waiting to be received
                </p>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.82rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">PO / Supplier</th>
                            <th class="border-0 px-2 py-2" style="white-space:nowrap">Order Date</th>
                            <th class="border-0 px-2 py-2" style="white-space:nowrap">Expected Delivery</th>
                            <th class="border-0 px-2 py-2">Items Ordered</th>
                            <th class="border-0 px-2 py-2 text-end">Value</th>
                            <th class="border-0 px-2 py-2">Payment</th>
                            <th class="border-0 px-2 py-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingToReceive as $po)
                        @php
                            $daysSinceOrder = $po->order_date->diffInDays(now());
                            $isLate = $po->expected_delivery_date && now()->gt($po->expected_delivery_date);
                        @endphp
                        <tr class="{{ $isLate ? 'table-warning' : '' }}">
                            <td class="px-3 py-1" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}"
                                   class="fw-semibold text-primary text-decoration-none" style="font-size:0.83rem;">
                                    {{ $po->po_number }}
                                </a>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $po->supplier->name }}</div>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div>{{ $po->order_date->format('M d, Y') }}</div>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $daysSinceOrder }}d ago</div>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                @if($po->expected_delivery_date)
                                    <div>{{ $po->expected_delivery_date->format('M d, Y') }}</div>
                                    @if($isLate)
                                        <div class="text-warning fw-bold" style="font-size:0.72rem;">
                                            <i class="bi bi-exclamation-triangle-fill"></i> Overdue
                                        </div>
                                    @else
                                        <div class="text-muted" style="font-size:0.72rem;">
                                            {{ now()->diffInDays($po->expected_delivery_date) }}d remaining
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-2 py-1">
                                @foreach($po->items as $item)
                                <div style="font-size:0.78rem;">
                                    <span class="fw-semibold">{{ $item->quantity_ordered }}</span>×
                                    {{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}
                                    @if($item->product->unit_type)
                                        <span class="badge ms-1" style="font-size:0.65rem;
                                            {{ $item->product->unit_type === 'indoor'
                                                ? 'background:#e8f0fe;color:#1a56db;'
                                                : 'background:#dcfce7;color:#166534;' }}">
                                            {{ ucfirst($item->product->unit_type) }}
                                        </span>
                                    @endif
                                </div>
                                @endforeach
                            </td>
                            <td class="px-2 py-1 text-end fw-semibold" style="white-space:nowrap">
                                ₱{{ number_format($po->total, 2) }}
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <span class="badge {{ $po->payment_type == 'full' ? 'bg-success' : 'bg-info text-dark' }}" style="font-size:0.7rem;">
                                    {{ $po->payment_type == 'full' ? 'Full' : '45-Day' }}
                                </span>
                                @if($po->payment_type === '45days' && $po->balance > 0)
                                    <div class="text-muted" style="font-size:0.72rem;">₱{{ number_format($po->balance, 2) }} due</div>
                                @endif
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div class="d-flex gap-1">
                                    <button type="button"
                                            class="btn btn-success btn-sm py-0 px-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#receiveModal{{ $po->id }}"
                                            style="font-size:0.75rem;">
                                        <i class="bi bi-box-arrow-in-down"></i> Receive
                                    </button>
                                    <a href="{{ route('purchase-orders.show', $po) }}"
                                       class="btn btn-outline-secondary btn-sm py-0 px-2"
                                       style="font-size:0.75rem;">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
                <p class="mb-0 fw-semibold">All orders have been received!</p>
                <p class="small">No pending deliveries at this time.</p>
            </div>
            @endif

        </div>{{-- end tab-receipts --}}

        {{-- ═══ TAB 3: PAYMENTS DUE ═══ --}}
        <div class="tab-pane fade p-3" id="tab-payments-due" role="tabpanel" aria-labelledby="tab-payments-due-btn">

            <p class="text-muted mb-2" style="font-size:0.82rem;">
                <i class="bi bi-calendar-event text-primary"></i> 45-Day payment terms only
            </p>

            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:0.82rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">PO / Supplier</th>
                            <th class="border-0 px-2 py-2" style="white-space:nowrap">Ordered</th>
                            <th class="border-0 px-2 py-2" style="white-space:nowrap">Due Date</th>
                            <th class="border-0 px-2 py-2">Total</th>
                            <th class="border-0 px-2 py-2">Paid</th>
                            <th class="border-0 px-2 py-2">Balance</th>
                            <th class="border-0 px-2 py-2">Status</th>
                            <th class="border-0 px-2 py-2 text-center">Action</th>
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
                            <td class="px-3 py-1" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="text-decoration-none fw-semibold text-primary" style="font-size:0.83rem;">
                                    {{ $po->po_number }}
                                </a>
                                <div class="text-muted" style="font-size:0.72rem;">{{ $po->supplier->name }}</div>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <div>{{ $po->payment_due_date->format('M d, Y') }}</div>
                                @if($daysLeft < 0)
                                    <div class="text-danger fw-bold" style="font-size:0.72rem;"><i class="bi bi-alarm"></i> Overdue {{ abs((int)$daysLeft) }}d</div>
                                @elseif($daysLeft == 0)
                                    <div class="text-danger fw-bold" style="font-size:0.72rem;"><i class="bi bi-exclamation-circle"></i> Due Today</div>
                                @elseif($daysLeft <= 10)
                                    <div class="text-warning fw-bold" style="font-size:0.72rem;"><i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }}d left</div>
                                @else
                                    <div class="text-muted" style="font-size:0.72rem;">{{ (int)$daysLeft }}d left</div>
                                @endif
                            </td>
                            <td class="px-2 py-1 fw-semibold" style="white-space:nowrap">₱{{ number_format($po->total, 2) }}</td>
                            <td class="px-2 py-1 text-success" style="white-space:nowrap">₱{{ number_format($po->amount_paid, 2) }}</td>
                            <td class="px-2 py-1 fw-semibold text-danger" style="white-space:nowrap">₱{{ number_format($po->balance, 2) }}</td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                @if($po->payment_status == 'paid')
                                    <span class="badge bg-success" style="font-size:0.7rem;">Paid</span>
                                @elseif($po->payment_status == 'partial')
                                    <span class="badge bg-warning text-dark" style="font-size:0.7rem;">Partial</span>
                                @else
                                    <span class="badge bg-danger" style="font-size:0.7rem;">Unpaid</span>
                                @endif
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                @if($po->balance > 0)
                                <button type="button" class="btn btn-primary btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#paymentModal{{ $po->id }}"
                                        style="font-size:0.78rem;">
                                    <i class="bi bi-cash-coin"></i> Pay
                                </button>
                                @else
                                <span class="text-success small"><i class="bi bi-check-circle-fill"></i> Paid</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
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

{{-- ═══════════════════════════════════════════════════
     RECEIVE STOCK MODALS — with serial number inputs
════════════════════════════════════════════════════ --}}
@foreach($pendingToReceive as $po)
<div class="modal fade" id="receiveModal{{ $po->id }}" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.receive', $po) }}" method="POST"
                  id="receiveForm{{ $po->id }}">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title">
                        <i class="bi bi-box-arrow-in-down"></i> Receive Stock — {{ $po->po_number }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">

                    <div class="alert alert-info border-0 mb-4" style="font-size:0.875rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Enter all serial numbers for each item. Serial count
                        <strong>must match</strong> the quantity received exactly.
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Received Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="received_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-truck"></i> DR / Delivery Receipt Number
                            </label>
                            <input type="text" class="form-control" name="delivery_number"
                                   placeholder="e.g. DR-2026-00123">
                        </div>
                    </div>

                    {{-- One card per item --}}
                    @foreach($po->items as $item)
                    @php
                        $remaining      = $item->quantity_ordered - $item->quantity_received;
                        $pendingSerials = $po->serials
                            ->where('product_id', $item->product_id)
                            ->where('status', 'pending')
                            ->pluck('serial_number')
                            ->toArray();
                    @endphp
                    <div class="card border shadow-sm mb-3">
                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="fw-semibold">
                                    {{ trim(($item->product->brand->name ?? '') . ' ' . $item->product->model) }}
                                </span>
                                @if($item->product->unit_type === 'indoor')
                                    <span class="badge ms-2" style="background:#e8f0fe;color:#1a56db;border:1px solid #93c5fd;font-size:0.72rem;">❄️ Indoor</span>
                                @elseif($item->product->unit_type === 'outdoor')
                                    <span class="badge ms-2" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.72rem;">🌀 Outdoor</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-3" style="font-size:0.82rem;">
                                <span>Ordered: <strong>{{ $item->quantity_ordered }}</strong></span>
                                <span>Received: <strong>{{ $item->quantity_received }}</strong></span>
                                <span class="d-flex align-items-center gap-1">
                                    Receiving now:
                                    <input type="hidden"
                                           name="items[{{ $loop->index }}][id]"
                                           value="{{ $item->id }}">
                                    <input type="number"
                                           class="form-control form-control-sm d-inline-block"
                                           name="items[{{ $loop->index }}][quantity_received]"
                                           value="{{ $remaining }}"
                                           min="0" max="{{ $remaining }}"
                                           style="width:65px;"
                                           onchange="rebuildIndexSerials('{{ $po->id }}', {{ $loop->index }}, this.value)">
                                </span>
                                <span class="badge bg-secondary"
                                      id="idx-serial-count-{{ $po->id }}-{{ $loop->index }}">
                                    0 / {{ $remaining }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-upc-scan text-primary"></i>
                                <span class="small fw-semibold text-primary">
                                    Serial Numbers <span class="text-danger">*</span>
                                </span>
                                <span class="text-muted small">— must match quantity above exactly</span>
                            </div>
                            <div class="row g-1"
                                 id="idx-serials-{{ $po->id }}-{{ $loop->index }}">
                                @for($s = 0; $s < $remaining; $s++)
                                <div class="col-md-3 col-sm-4 col-6">
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text text-muted"
                                              style="font-size:0.72rem;min-width:36px;">#{{ $s + 1 }}</span>
                                        <input type="text"
                                               class="form-control form-control-sm idx-serial-input"
                                               name="items[{{ $loop->index }}][serials][]"
                                               value="{{ $pendingSerials[$s] ?? '' }}"
                                               placeholder="S/N #{{ $s + 1 }}"
                                               style="font-family:monospace;font-size:0.82rem;"
                                               required
                                               oninput="updateIdxSerialCount('{{ $po->id }}', {{ $loop->index }})">
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
                    <button type="submit" class="btn btn-success px-4">
                        <i class="bi bi-check-circle"></i> Confirm & Receive Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

{{-- PAYMENT MODALS --}}
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
@endforeach

@push('scripts')
<script>
/* ── Serial helpers for index receive modals ── */
function rebuildIndexSerials(poId, itemIdx, qty) {
    qty = parseInt(qty) || 0;
    const container = document.getElementById(`idx-serials-${poId}-${itemIdx}`);
    const existing  = [...container.querySelectorAll('.idx-serial-input')].map(i => i.value);
    container.innerHTML = '';

    for (let s = 0; s < qty; s++) {
        container.insertAdjacentHTML('beforeend', `
            <div class="col-md-3 col-sm-4 col-6">
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text text-muted" style="font-size:0.72rem;min-width:36px;">#${s+1}</span>
                    <input type="text"
                           class="form-control form-control-sm idx-serial-input"
                           name="items[${itemIdx}][serials][]"
                           value="${existing[s] || ''}"
                           placeholder="S/N #${s+1}"
                           style="font-family:monospace;font-size:0.82rem;"
                           required
                           oninput="updateIdxSerialCount('${poId}', ${itemIdx})">
                </div>
            </div>`);
    }
    updateIdxSerialCount(poId, itemIdx);
}

function updateIdxSerialCount(poId, itemIdx) {
    const container = document.getElementById(`idx-serials-${poId}-${itemIdx}`);
    const inputs    = container.querySelectorAll('.idx-serial-input');
    const filled    = [...inputs].filter(i => i.value.trim() !== '').length;
    const total     = inputs.length;
    const counter   = document.getElementById(`idx-serial-count-${poId}-${itemIdx}`);
    if (counter) {
        counter.textContent = `${filled} / ${total}`;
        counter.className   = filled === total && total > 0
            ? 'badge bg-success'
            : filled > 0 ? 'badge bg-warning text-dark' : 'badge bg-secondary';
    }
}

/* ── Submit guard for each receive form ── */
document.addEventListener('DOMContentLoaded', function () {
    // Initialize serial counters
    document.querySelectorAll('[id^="idx-serials-"]').forEach(container => {
        const parts   = container.id.replace('idx-serials-', '').split('-');
        const itemIdx = parts[parts.length - 1];
        const poId    = parts.slice(0, -1).join('-');
        updateIdxSerialCount(poId, itemIdx);
    });

    // Attach submit guards to all receive forms
    document.querySelectorAll('[id^="receiveForm"]').forEach(form => {
        form.addEventListener('submit', function (e) {
            const poId      = form.id.replace('receiveForm', '');
            let   allValid  = true;

            form.querySelectorAll('[id^="idx-serials-"]').forEach(container => {
                const parts   = container.id.replace('idx-serials-', '').split('-');
                const itemIdx = parts[parts.length - 1];
                const qtyEl   = form.querySelector(`input[name="items[${itemIdx}][quantity_received]"]`);
                const qty     = parseInt(qtyEl?.value) || 0;
                const inputs  = container.querySelectorAll('.idx-serial-input');
                const filled  = [...inputs].filter(i => i.value.trim() !== '').length;

                if (qty > 0 && filled !== qty) {
                    allValid = false;
                    const counter = document.getElementById(`idx-serial-count-${poId}-${itemIdx}`);
                    if (counter) counter.className = 'badge bg-danger';
                }
            });

            if (!allValid) {
                e.preventDefault();
                alert('All serial numbers must be filled and match the quantity received for each item.');
            }
        });
    });

    // Table filter
    document.getElementById('poSearch').addEventListener('input', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    document.getElementById('paymentFilter').addEventListener('change', filterTable);
});

/* ── Table filter ── */
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
        const matchStatus  = !status  || row.dataset.status  === status;
        const matchPayment = !payment || row.dataset.payment === payment;

        const show = matchSearch && matchStatus && matchPayment;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
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
    document.getElementById('poSearch').value        = '';
    document.getElementById('statusFilter').value    = '';
    document.getElementById('paymentFilter').value   = '';
    filterTable();
}
</script>
@endpush

@endsection