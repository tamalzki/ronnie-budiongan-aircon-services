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
                    <div class="bg-info bg-opacity-10 rounded p-2">
                        <i class="bi bi-upc-scan text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size:0.72rem;">Total Units</div>
                        <div class="fw-bold fs-6">{{ $totalUnits }}</div>
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
                <button class="nav-link px-4 py-2" id="tab-receiving-btn"
                        type="button" role="tab"
                        data-bs-toggle="tab" data-bs-target="#tab-receiving"
                        aria-controls="tab-receiving" aria-selected="false">
                    <i class="bi bi-box-arrow-in-down me-1"></i> Order Receiving
                    @if($toReceive->count() > 0)
                        <span class="badge bg-warning text-dark ms-1">{{ $toReceive->count() }}</span>
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
                <table class="table table-hover table-sm align-middle mb-0 app-table" id="poTable">
                    <thead>
                        <tr>
                            <th>Order Date</th>
                            <th>PO No.</th>
                            <th>Supplier</th>
                            <th class="text-center">Units</th>
                            <th class="text-end">Amount</th>
                            <th>Payment</th>
                            <th class="text-center">Actions</th>
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
                            $poUnits   = $po->serials_count ?? 0;
                            $poSold    = $po->sold_serials_count ?? 0;
                            $poPairs   = intdiv($poUnits, 2);
                        @endphp
                        <tr class="{{ $rowClass }} po-row"
                            data-po="{{ strtolower($po->po_number . ' ' . ($po->supplier_po_number ?? '')) }}"
                            data-supplier="{{ strtolower($po->supplier->name) }}"
                            data-dr="{{ strtolower($po->delivery_number ?? '') }}"
                            data-status="{{ $po->status }}"
                            data-payment="{{ $po->payment_status }}"
                            data-href="{{ route('purchase-orders.show', $po) }}"
                            style="cursor:pointer;">

                            {{-- Order Date --}}
                            <td style="white-space:nowrap">
                                <span class="fw-semibold">{{ $po->order_date->format('M d, Y') }}</span>
                            </td>

                            {{-- PO No. --}}
                            <td style="white-space:nowrap">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-bold"
                                      style="font-family:monospace;font-size:0.78rem;">
                                    {{ $po->display_po_number }}
                                </span>
                            </td>

                            {{-- Supplier --}}
                            <td style="white-space:nowrap">
                                <span class="fw-semibold">{{ $po->supplier->name }}</span>
                            </td>

                            {{-- Units --}}
                            <td class="px-2 py-1 text-center" style="white-space:nowrap">
                                @if($po->status === 'pending')
                                    <span class="badge bg-warning text-dark" style="font-size:0.65rem;">
                                        <i class="bi bi-hourglass-split"></i> Awaiting{{ $poPairs > 0 ? ' · ' . $poPairs . ' in' : '' }}
                                    </span>
                                @else
                                    <span class="fw-bold">{{ $poPairs }}</span>
                                    <small class="text-muted">{{ $poPairs == 1 ? 'pair' : 'pairs' }}</small>
                                @endif
                            </td>

                            {{-- Amount --}}
                            <td class="px-2 py-1 text-end" style="white-space:nowrap">
                                <span class="fw-semibold">₱{{ number_format($po->total, 2) }}</span>
                                @if($po->balance > 0)
                                    <small class="text-danger">· ₱{{ number_format($po->balance, 2) }} due</small>
                                @endif
                            </td>

                            {{-- Payment --}}
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <span class="badge {{ $po->payment_type == 'full' ? 'bg-success' : 'bg-info text-dark' }}" style="font-size:0.65rem;">
                                    {{ $po->payment_type == 'full' ? 'Full' : '45-Day' }}
                                </span>
                                @if($po->payment_type === '45days')
                                    @if($po->payment_status == 'paid')
                                        <span class="badge bg-success" style="font-size:0.65rem;">Paid</span>
                                    @elseif($po->payment_status == 'partial')
                                        <span class="badge bg-warning text-dark" style="font-size:0.65rem;">Partial</span>
                                    @else
                                        <span class="badge bg-danger" style="font-size:0.65rem;">Unpaid</span>
                                    @endif
                                    @if($po->payment_due_date && $po->payment_status !== 'paid')
                                        @if($daysLeft < 0)
                                            <small class="text-danger fw-bold"><i class="bi bi-alarm"></i> {{ abs((int)$daysLeft) }}d overdue</small>
                                        @elseif($daysLeft <= 10)
                                            <small class="text-danger"><i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }}d left</small>
                                        @else
                                            <small class="text-muted">due {{ $po->payment_due_date->format('M d') }}</small>
                                        @endif
                                    @endif
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td style="white-space:nowrap">
                                <div class="app-act-wrap">
                                    <a href="{{ route('purchase-orders.pdf', $po) }}" class="btn btn-light border app-act text-danger"
                                       title="Download PO as PDF">
                                        <i class="bi bi-file-earmark-pdf"></i> PO DOC
                                    </a>
                                    @if($po->payment_type === '45days' && $po->balance > 0)
                                    <button type="button" class="btn btn-light border app-act text-primary"
                                            onclick="goToPayment({{ $po->id }})"
                                            title="Record Payment (opens Payments Due)">
                                        <i class="bi bi-cash-coin"></i> Pay
                                    </button>
                                    @endif
                                    <a href="{{ route('purchase-orders.edit', $po) }}"
                                       class="btn btn-light border app-act"
                                       title="{{ ($po->sold_serials_count ?? 0) > 0 ? 'Some units sold — edit may be blocked' : 'Edit' }}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('purchase-orders.destroy', $po) }}" method="POST" class="app-act-form"
                                          onsubmit="return confirm('{{ ($po->sold_serials_count ?? 0) > 0 ? 'This PO has sold units and cannot be deleted.' : 'Delete this PO? Stock and serials will be removed.' }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light border app-act text-danger" title="Delete">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
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

        {{-- ═══ TAB 2: ORDER RECEIVING (POs awaiting stock / serial encoding) ═══ --}}
        <div class="tab-pane fade p-3" id="tab-receiving" role="tabpanel" aria-labelledby="tab-receiving-btn">

            <p class="text-muted mb-2" style="font-size:0.82rem;">
                <i class="bi bi-box-arrow-in-down text-warning"></i>
                Purchase orders waiting for delivery — open one to encode serial numbers and receive the stock into inventory.
            </p>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table">
                    <thead>
                        <tr>
                            <th>Order Date</th>
                            <th>PO / Doc No.</th>
                            <th>Supplier</th>
                            <th>Items</th>
                            <th class="text-center">Units Remaining</th>
                            <th>Expected</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($toReceive as $po)
                        @php
                            $remainingUnits = $po->items->sum(fn($i) => max(0, $i->quantity_ordered - $i->quantity_received));
                        @endphp
                        <tr>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <span class="fw-semibold">{{ $po->order_date->format('M d, Y') }}</span>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}"
                                   class="fw-semibold text-primary text-decoration-none"
                                   style="font-family:monospace;font-size:0.8rem;">
                                    PO {{ $po->display_po_number }}{{ $po->delivery_number ? ' · ' . $po->delivery_number : '' }}
                                </a>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">{{ $po->supplier->name }}</td>
                            <td class="px-2 py-1" style="font-size:0.78rem;">
                                @foreach($po->items->take(3) as $item)
                                    <div>{{ $item->quantity_ordered }}× {{ $item->is_set ? $item->product->set_model_label : $item->product->model }}</div>
                                @endforeach
                                @if($po->items->count() > 3)
                                    <div class="text-muted">+{{ $po->items->count() - 3 }} more…</div>
                                @endif
                            </td>
                            <td class="px-2 py-1 text-center">
                                <span class="badge bg-warning text-dark">{{ $remainingUnits }}</span>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                {{ $po->expected_delivery_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td class="px-2 py-1 text-center" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}#receive"
                                   class="btn btn-warning btn-sm">
                                    <i class="bi bi-upc-scan"></i> Receive
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
                                Nothing to receive — all purchase orders are in stock.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>{{-- end tab-receiving --}}

        {{-- ═══ TAB 3: PAYMENTS DUE ═══ --}}
        <div class="tab-pane fade p-3" id="tab-payments-due" role="tabpanel" aria-labelledby="tab-payments-due-btn">

            <p class="text-muted mb-2" style="font-size:0.82rem;">
                <i class="bi bi-calendar-event text-primary"></i> 45-Day payment terms only
            </p>

            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table">
                    <thead>
                        <tr>
                            <th>PO / Supplier</th>
                            <th>Ordered</th>
                            <th>Due Date</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
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
                        <tr class="{{ $rowClass }}" id="due-row-{{ $po->id }}">
                            <td class="px-3 py-1" style="white-space:nowrap">
                                <a href="{{ route('purchase-orders.show', $po) }}" class="text-decoration-none fw-semibold text-primary" style="font-size:0.83rem;">
                                    PO {{ $po->display_po_number }}
                                </a>
                                <span class="text-muted" style="font-size:0.75rem;">· {{ $po->supplier->name }}</span>
                            </td>
                            <td class="px-2 py-1" style="white-space:nowrap">{{ $po->order_date->format('M d, Y') }}</td>
                            <td class="px-2 py-1" style="white-space:nowrap">
                                {{ $po->payment_due_date->format('M d, Y') }}
                                @if($daysLeft < 0)
                                    <small class="text-danger fw-bold"><i class="bi bi-alarm"></i> {{ abs((int)$daysLeft) }}d overdue</small>
                                @elseif($daysLeft == 0)
                                    <small class="text-danger fw-bold"><i class="bi bi-exclamation-circle"></i> due today</small>
                                @elseif($daysLeft <= 10)
                                    <small class="text-warning fw-bold"><i class="bi bi-bell-fill"></i> {{ (int)$daysLeft }}d left</small>
                                @else
                                    <small class="text-muted">{{ (int)$daysLeft }}d left</small>
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
                                <button type="button" class="btn btn-light border app-act text-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#paymentModal{{ $po->id }}">
                                    <i class="bi bi-cash-coin"></i><span class="act-label"> Pay</span>
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


{{-- PAYMENT MODALS (rendered for every payable PO on this page and in Payments Due) --}}
@foreach($purchaseOrders->getCollection()->merge($paymentsDue)->unique('id')->where('payment_type', '45days')->where('balance', '>', 0) as $po)
<div class="modal fade" id="paymentModal{{ $po->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('purchase-orders.payment', $po) }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-cash-coin"></i> Record Payment — PO {{ $po->display_po_number }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    {{-- Basic details --}}
                    <div class="border rounded bg-light px-3 py-2 mb-3" style="font-size:0.82rem;">
                        <div class="d-flex flex-wrap gap-3">
                            <span><span class="text-muted">Supplier:</span> <strong>{{ $po->supplier->name }}</strong></span>
                            <span><span class="text-muted">Ordered:</span> <strong>{{ $po->order_date->format('M d, Y') }}</strong></span>
                            @if($po->delivery_number)
                                <span><span class="text-muted">DR:</span> <strong style="font-family:monospace;">{{ $po->delivery_number }}</strong></span>
                            @endif
                            @if($po->payment_due_date)
                                <span><span class="text-muted">Due:</span> <strong class="{{ $po->payment_due_date->isPast() ? 'text-danger' : '' }}">{{ $po->payment_due_date->format('M d, Y') }}</strong></span>
                            @endif
                        </div>
                    </div>
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
document.addEventListener('DOMContentLoaded', function () {
    // Table filter
    document.getElementById('poSearch').addEventListener('input', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    document.getElementById('paymentFilter').addEventListener('change', filterTable);

    // Whole row opens the purchase order (except clicks on buttons/links/forms)
    document.querySelectorAll('.po-row[data-href]').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form, input, select, .modal')) return;
            window.location.href = this.dataset.href;
        });
    });
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
            noResults.innerHTML = '<td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
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

/* ── Record payment: jump to Payments Due tab, highlight the entry, open its modal ── */
function goToPayment(id) {
    const tabBtn = document.getElementById('tab-payments-due-btn');

    const openModal = () => {
        const row = document.getElementById('due-row-' + id);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('table-active');
            setTimeout(() => row.classList.remove('table-active'), 2500);
        }
        const modalEl = document.getElementById('paymentModal' + id);
        if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).show();
    };

    if (tabBtn.classList.contains('active')) {
        openModal();
        return;
    }

    tabBtn.addEventListener('shown.bs.tab', function handler() {
        tabBtn.removeEventListener('shown.bs.tab', handler);
        setTimeout(openModal, 150);
    });
    bootstrap.Tab.getOrCreateInstance(tabBtn).show();
}
</script>
@endpush

@endsection