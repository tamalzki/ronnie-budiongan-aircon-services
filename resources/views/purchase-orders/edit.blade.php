@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@push('styles')
<style>
    .po-form-main { min-width: 0; }
    .po-form-sidebar { min-width: 0; }

    @media (min-width: 992px) {
        .po-sidebar-sticky {
            position: sticky;
            top: 12px;
            z-index: 5;
        }
    }

    .po-items-scroll {
        overflow-x: visible;
        width: 100%;
    }
    @media (max-width: 575px) {
        .po-items-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
    }

    #itemsTable {
        font-size: 0.75rem;
        table-layout: fixed;
        width: 100%;
    }
    #itemsTable th,
    #itemsTable td {
        vertical-align: top;
        padding: 0.25rem 0.2rem;
    }
    #itemsTable .po-col-num { width: 3%; white-space: nowrap; }
    #itemsTable .po-col-product { width: 24%; max-width: 10.5rem; }
    #itemsTable .po-col-qty { width: 7%; }
    #itemsTable .po-col-cost { width: 13%; }
    #itemsTable .po-col-disc { width: 15%; }
    #itemsTable .po-col-total { width: 13%; }
    #itemsTable .po-col-action { width: 4%; }

    .po-item-product-cell { min-width: 0; max-width: 10.5rem; }

    .po-num-input {
        font-size: 0.72rem !important;
        padding: 0.15rem 0.2rem !important;
    }
    .po-disc-pair {
        display: flex;
        gap: 2px;
    }
    .po-disc-pair .disc-input { flex: 0 0 42%; min-width: 0; }
    .po-disc-pair .discount-amount-input { flex: 1; min-width: 0; }
    #itemsTable .po-col-total {
        font-size: 0.72rem;
        white-space: nowrap;
    }

    .po-combobox-trigger {
        min-height: 28px;
        padding: 0.2rem 0.45rem;
        align-items: center !important;
        height: auto !important;
    }
    .po-combobox-display {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.25;
        flex: 1;
        min-width: 0;
        font-size: 0.78rem !important;
    }
    .po-combobox-panel .po-combobox-display,
    .po-combobox-panel .cb-option {
        white-space: normal;
        word-break: break-word;
        overflow: visible;
        text-overflow: unset;
    }
    .po-combobox-panel {
        display: none;
        min-width: 10rem;
        width: max-content;
        max-width: min(20rem, 92vw);
        background: #fff;
    }
    .po-combobox-panel.is-floating {
        display: block !important;
        position: fixed !important;
        z-index: 1060;
        box-shadow: 0 8px 28px rgba(15, 23, 42, 0.18);
        max-height: min(360px, calc(100vh - 16px));
        overflow: hidden;
    }
    .po-combobox-panel .po-combobox-list-inner {
        max-height: min(280px, calc(100vh - 120px));
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    .po-combobox-panel .cb-option span {
        white-space: normal;
        word-break: break-word;
    }

    .po-part-cell { min-width: 0; }
    .po-part-group {
        display: flex;
        border: 1px solid #fed7aa;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }
    .po-part-group-badge {
        flex: 0 0 1.35rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, #fff7ed 0%, #ffedd5 100%);
        border-right: 1px solid #fed7aa;
        color: #c2410c;
        font-size: 0.56rem;
        font-weight: 700;
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        letter-spacing: 0.04em;
        user-select: none;
    }
    .po-part-stack {
        flex: 1;
        min-width: 0;
        padding: 3px 5px 4px;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }
    .po-part-line {
        width: 100%;
        min-width: 0;
    }
    .po-part-line-part {
        border-top: 1px dashed #fed7aa;
        padding-top: 3px;
    }
    .po-part-line.is-locked {
        opacity: 0.45;
        pointer-events: none;
    }
    .po-part-line.is-locked .pacb-display-placeholder {
        color: #adb5bd;
        font-style: italic;
    }
    .po-part-line-model .po-combobox-display {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .po-part-line-part .po-combobox-trigger {
        align-items: flex-start !important;
        min-height: 26px;
        height: auto !important;
    }
    .po-part-line-part .po-combobox-display,
    .po-new-part-input {
        white-space: normal;
        word-break: break-word;
        overflow: visible;
        text-overflow: unset;
        line-height: 1.25;
    }
    .po-part-new-name {
        width: 100%;
        min-width: 0;
    }
    .po-new-part-input {
        width: 100%;
        min-width: 0;
        font-size: 0.78rem;
    }

    #itemsTable .input-group-sm > .form-control,
    #itemsTable .form-control-sm {
        font-size: 0.78rem;
        padding: 0.2rem 0.35rem;
    }
    #itemsTable .input-group-sm > .input-group-text {
        font-size: 0.72rem;
        padding: 0.2rem 0.35rem;
    }

    .btn-add-aircon-part {
        background: #e67e22;
        border-color: #d35400;
        color: #fff;
        font-weight: 600;
    }
    .btn-add-aircon-part:hover {
        background: #d35400;
        border-color: #ba4a00;
        color: #fff;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <x-page-header title="Edit Purchase Order" subtitle="{{ $purchaseOrder->display_po_number }}" icon="bi-pencil">
        <x-slot name="actions">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </x-slot>
    </x-page-header>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <div class="alert alert-info border-0 shadow-sm py-2" style="font-size:0.85rem;">
        <i class="bi bi-info-circle"></i>
        Document No. (DR) and serial numbers are entered in <strong>Order Receiving</strong>. Editing here updates order details and keeps received serials in stock.
    </div>

    {{-- ── SOLD TO / DELIVERED TO header ── --}}
    <form action="{{ route('purchase-orders.update', $purchaseOrder) }}" method="POST" id="poForm">
        @csrf
        @method('PUT')

        <div class="row g-2 mb-3">
            <div class="col-md-6">
                <div class="border rounded bg-white px-2 py-1 h-100" style="font-size:0.72rem;line-height:1.35;">
                    <span class="fw-bold text-uppercase text-primary" style="font-size:0.66rem;letter-spacing:.5px;"><i class="bi bi-person-badge"></i> Sold To</span>
                    <div class="text-muted">Customer No. : 1378</div>
                    <div class="fw-semibold">RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC</div>
                    <div>DOOR 7 SORONGON BUILDING QUEZON AVE. TRES DE MAYO DIGOS DAVAO DEL SUR 8002 PH 11</div>
                    <div>TIN: 123-962-440-00000</div>
                </div>
            </div>
            <div class="col-md-6">
                @include('purchase-orders.partials.delivered-to-card', [
                    'deliveredToCustomerNo' => $purchaseOrder->delivered_to_customer_no,
                    'deliveredToName' => $purchaseOrder->delivered_to_name,
                    'deliveredToAddress' => $purchaseOrder->delivered_to_address,
                ])
            </div>
        </div>

        <div class="row g-3">

            {{-- Left Column --}}
            <div class="col-lg-9 col-xl-9 po-form-main">

                {{-- Supplier & Document Reference --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-building"></i> Supplier & Order Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Row 1: Supplier + Dates --}}
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Supplier <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="supplier_id" required>
                                    <option value="">-- Select Supplier --</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id', $purchaseOrder->supplier_id) == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Order / DR Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="order_date"
                                       value="{{ old('order_date', $purchaseOrder->order_date->format('Y-m-d')) }}" required id="orderDate">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Expected Delivery</label>
                                <input type="date" class="form-control form-control-sm" name="expected_delivery_date"
                                       value="{{ old('expected_delivery_date', optional($purchaseOrder->expected_delivery_date)->format('Y-m-d')) }}">
                            </div>

                            {{-- Row 2: PO reference (DR + serials via Order Receiving) --}}
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">PO No. <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="supplier_po_number"
                                       value="{{ old('supplier_po_number', $purchaseOrder->supplier_po_number) }}"
                                       placeholder="e.g. 698"
                                       style="font-family:monospace;" required>
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <div class="alert alert-info py-2 px-3 mb-0 w-100" style="font-size:0.78rem;">
                                    <i class="bi bi-info-circle"></i>
                                    Document No. (DR) and serial numbers are managed in <strong>Order Receiving</strong> when stock arrives.
                                    @if($purchaseOrder->delivery_number)
                                        <span class="ms-1">Current DR: <strong style="font-family:monospace;">{{ $purchaseOrder->delivery_number }}</strong></span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Items --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Order Items</h6>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-success btn-sm" onclick="addItem()">
                                <i class="bi bi-plus-circle"></i> Add Product
                            </button>
                            <button type="button" class="btn btn-sm btn-add-aircon-part" onclick="addPartRow()">
                                <i class="bi bi-nut"></i> Add Aircon Part
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="po-items-scroll">
                        <table class="table table-sm align-middle mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="po-col-num text-center">#</th>
                                    <th class="po-col-product">Product <span class="text-danger">*</span></th>
                                    <th class="po-col-qty text-center">Qty</th>
                                    <th class="po-col-cost text-center">Cost</th>
                                    <th class="po-col-disc text-center" title="Percent or fixed amount (use one)">Disc</th>
                                    <th class="po-col-total text-end">Total</th>
                                    <th class="po-col-action"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr id="emptyState">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                        No products added yet. Click "Add Product" above.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-sticky"></i> Additional Notes</h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control form-control-sm" name="notes" rows="2"
                                  placeholder="Optional notes about this purchase order">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                    </div>
                </div>

            </div>

            {{-- Right Column --}}
            <div class="col-lg-3 col-xl-3 po-form-sidebar">
                <div class="po-sidebar-sticky">

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-primary text-white border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-calculator"></i> Order Summary</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0" style="font-size:0.9rem;">
                            <tr class="border-top">
                                <td class="fw-bold pt-2">TOTAL:</td>
                                <td class="text-end fw-bold pt-2" style="font-size:1.3rem;color:#0d6efd;">
                                    ₱<span id="grandTotal">0.00</span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Payment Terms --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-credit-card"></i> Payment Terms</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Payment Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="payment_type" id="paymentType" required>
                                <option value="full"   {{ old('payment_type', $purchaseOrder->payment_type) == 'full'   ? 'selected' : '' }}>Full Payment</option>
                                <option value="45days" {{ old('payment_type', $purchaseOrder->payment_type) == '45days' ? 'selected' : '' }}>45-Day Term</option>
                            </select>
                        </div>
                        <div class="small text-muted" id="paymentNote45" style="display:none;">
                            <i class="bi bi-info-circle"></i> Amounts already paid are kept. Manage the due date and record
                            further payments on the order page.
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 shadow mb-3"
                        style="font-size:1.05rem;padding:12px 0;">
                    <i class="bi bi-check-circle-fill"></i> UPDATE PURCHASE ORDER
                </button>

                </div>{{-- /.po-sidebar-sticky --}}
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const products = {!! json_encode($productsJson) !!};
const parts = {!! json_encode($partsJson) !!};
let rowIndex = 0;

/* ── Floating combobox: panels render on document.body so table overflow cannot clip them ── */
function poComboIsOpen(panel) {
    return panel && panel.classList.contains('is-floating');
}

function poComboReposition(trigger, panel) {
    if (!trigger || !panel) return;
    const rect = trigger.getBoundingClientRect();
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const width = Math.min(Math.max(rect.width, 320), 480, vw - 16);
    let left = Math.min(Math.max(8, rect.left), vw - width - 8);
    const spaceBelow = vh - rect.bottom - 8;
    const spaceAbove = rect.top - 8;
    const panelMaxH = Math.min(360, vh - 16);
    let top;
    let listMaxH;
    if (spaceBelow >= 160 || spaceBelow >= spaceAbove) {
        top = rect.bottom + 4;
        listMaxH = Math.min(280, spaceBelow - 52);
    } else {
        listMaxH = Math.min(280, spaceAbove - 52);
        top = Math.max(8, rect.top - 4 - Math.min(panelMaxH, listMaxH + 52));
    }
    panel.style.position = 'fixed';
    panel.style.left = left + 'px';
    panel.style.top = top + 'px';
    panel.style.width = width + 'px';
    panel.style.minWidth = width + 'px';
    panel.style.maxWidth = width + 'px';
    panel.style.zIndex = '1060';
    const list = panel.querySelector('.po-combobox-list-inner');
    if (list) list.style.maxHeight = Math.max(120, listMaxH) + 'px';
}

function poComboOpen(trigger, panel) {
    if (!trigger || !panel) return;
    const combobox = trigger.closest('.combobox');
    if (combobox && !panel.dataset.poComboHome) {
        panel.dataset.poComboHome = combobox.id || '';
    }
    if (panel.parentElement !== document.body) {
        document.body.appendChild(panel);
    }
    panel.classList.add('is-floating');
    panel.style.display = 'block';
    poComboReposition(trigger, panel);
}

function poComboClose(panel) {
    if (!panel) return;
    panel.classList.remove('is-floating');
    panel.style.display = 'none';
    panel.style.position = '';
    panel.style.left = '';
    panel.style.top = '';
    panel.style.width = '';
    panel.style.minWidth = '';
    panel.style.maxWidth = '';
    panel.style.zIndex = '';
    const list = panel.querySelector('.po-combobox-list-inner');
    if (list) list.style.maxHeight = '';
    const homeId = panel.dataset.poComboHome;
    if (homeId) {
        const home = document.getElementById(homeId);
        if (home) home.appendChild(panel);
    }
}

function closeAllPoComboPanels() {
    document.querySelectorAll('.po-combobox-panel').forEach(poComboClose);
}

function unitTypeBadge(unitType) {
    if (!unitType || unitType === 'set') return '';
    const isIndoor = unitType === 'indoor';
    const color    = isIndoor ? '#0d6efd' : '#198754';
    const icon     = isIndoor ? '❄️' : '🌀';
    return `<span style="font-size:0.7rem;padding:1px 6px;border-radius:20px;background:${color}15;color:${color};border:1px solid ${color}40;font-weight:600;white-space:nowrap;">${icon} ${isIndoor ? 'Indoor' : 'Outdoor'}</span>`;
}

function productById(id) {
    return products.find(p => String(p.id) === String(id)) || null;
}

function addItem(prefill) {
    document.getElementById('emptyState')?.remove();
    rowIndex++;
    const idx = rowIndex;

    const cbOpts = products.map(p => {
        const badgeHtml = p.unit_type ? unitTypeBadge(p.unit_type) : '';
        return `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                     data-value="${p.id}" data-cost="${p.cost}" data-label="${escAttr(p.label)}"
                     data-unit-type="${p.unit_type || ''}"
                     onmouseenter="this.style.background='#f0f4ff'"
                     onmouseleave="this.style.background=''"
                     onclick="pickPOCombo(${idx}, '${p.id}', '${p.cost}', this.getAttribute('data-label'), this.getAttribute('data-unit-type'))">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span>${escAttr(p.label)}</span>${badgeHtml}
                  </div>
                </div>`;
    }).join('');

    const html = `
    <tr class="item-row" id="row-${idx}" data-item="${idx}">
        <td class="text-center text-muted fw-semibold" id="row-label-${idx}">${idx}</td>

        {{-- Product --}}
        <td class="po-item-product-cell po-col-product">
            <input type="hidden" name="items[${idx}][item_type]" value="product">
            <select name="items[${idx}][product_id]" class="product-select d-none" data-row="${idx}" required>
                <option value="">-- Select --</option>
                ${products.map(p => `<option value="${p.id}" data-cost="${p.cost}">${p.label}</option>`).join('')}
            </select>
            <div class="combobox position-relative" id="pocb-${idx}">
                <div class="form-control form-control-sm po-combobox-trigger d-flex justify-content-between align-items-center gap-1"
                     style="cursor:pointer;user-select:none;background:#fff;"
                     onclick="togglePOCombo(${idx})">
                    <div class="d-flex align-items-center gap-1" style="flex:1;min-width:0;">
                        <span class="pocb-display-${idx} text-muted po-combobox-display" style="font-size:0.82rem;">Model…</span>
                        <span class="pocb-badge-${idx} flex-shrink-0"></span>
                    </div>
                    <i class="bi bi-chevron-down flex-shrink-0" style="font-size:0.7rem;color:#888;"></i>
                </div>
                <div class="pocb-panel-${idx} border rounded po-combobox-panel">
                    <div class="p-2 border-bottom">
                        <input type="text" class="form-control form-control-sm pocb-search-${idx}"
                               placeholder="🔍 Search product…"
                               oninput="searchPOCombo(${idx})"
                               onclick="event.stopPropagation()">
                    </div>
                    <div class="pocb-list-${idx} po-combobox-list-inner">
                        ${cbOpts}
                    </div>
                </div>
            </div>
        </td>

        {{-- Qty --}}
        <td class="po-col-qty">
            <input type="number" class="form-control form-control-sm qty-input text-center" name="items[${idx}][quantity]"
                   value="1" min="1" required oninput="onQtyChange(${idx})" onchange="onQtyChange(${idx})">
        </td>

        {{-- Unit Cost (optional) --}}
        <td class="po-col-cost">
            <input type="number" step="0.01" class="form-control form-control-sm cost-input po-num-input text-end"
                   name="items[${idx}][unit_cost]" value="" min="0" placeholder="₱" title="Unit cost" required
                   oninput="calcRow(${idx})" onchange="calcRow(${idx})">
        </td>

        {{-- Discount (% and fixed ₱ per unit — applied per qty in total) --}}
        <td class="po-col-disc">
            <div class="po-disc-pair">
                <input type="number" step="0.01" class="form-control form-control-sm disc-input po-num-input text-center"
                       name="items[${idx}][discount_percent]" value="" min="0" max="100" placeholder="%" title="Discount % (per unit)"
                       oninput="calcRow(${idx})" onchange="calcRow(${idx})">
                <input type="number" step="0.01" class="form-control form-control-sm discount-amount-input po-num-input text-end"
                       name="items[${idx}][discount_amount]" value="" min="0" placeholder="₱" title="Fixed discount per unit"
                       oninput="calcRow(${idx})" onchange="calcRow(${idx})">
            </div>
        </td>

        {{-- Total --}}
        <td class="po-col-total text-end fw-bold text-primary">₱<span id="total-${idx}" class="total-display">0.00</span></td>

        {{-- Action --}}
        <td class="po-col-action text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(${idx})" style="padding:0 4px;line-height:1.2;">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>`;

    document.getElementById('itemsTableBody').insertAdjacentHTML('beforeend', html);
    refreshDropdowns();

    // Pre-fill (editing or restoring after a validation error)
    if (prefill) {
        const sel = document.querySelector(`select[name="items[${idx}][product_id]"]`);
        sel.value = prefill.product_id;

        const disp = document.querySelector(`.pocb-display-${idx}`);
        if (disp && prefill.label) {
            disp.textContent = prefill.label;
            disp.title = prefill.label;
            disp.style.color = '#212529';
        }

        const badge = document.querySelector(`.pocb-badge-${idx}`);
        if (badge && prefill.unit_type) badge.innerHTML = unitTypeBadge(prefill.unit_type);

        const row = document.getElementById(`row-${idx}`);
        row.querySelector('.qty-input').value = prefill.quantity;
        if (prefill.unit_cost !== '' && prefill.unit_cost != null) {
            row.querySelector('.cost-input').value = parseFloat(prefill.unit_cost).toFixed(2);
        }

        setRowDiscounts(row, prefill);

        calcRow(idx);
        refreshDropdowns();
    }
}

function onQtyChange(idx) {
    calcRow(idx);
}

function partById(id) {
    return parts.find(p => String(p.id) === String(id)) || null;
}

function escAttr(s) {
    return String(s ?? '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

function partsForModel(modelProductId) {
    if (modelProductId === '' || modelProductId === null || modelProductId === undefined) {
        return parts.filter(p => !p.product_id);
    }
    return parts.filter(p => String(p.product_id) === String(modelProductId));
}

function partModelOptionsHtml(idx) {
    const generalOpt = `<div class="cb-option px-3 py-2 border-bottom" style="cursor:pointer;font-size:0.82rem;"
         onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''"
         onclick="pickPartModel(${idx}, '', 'General / Unlinked')">
         General / Unlinked
    </div>`;
    const productOpts = products.map(p =>
        `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
              data-value="${p.id}" data-label="${escAttr(p.label)}"
              onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''"
              onclick="pickPartModel(${idx}, '${p.id}', this.getAttribute('data-label'))">
            ${p.label}
        </div>`
    ).join('');
    return generalOpt + productOpts;
}

function buildPartOptionsForRow(idx, modelProductId) {
    const list = document.querySelector(`.pacb-list-${idx}`);
    if (!list) return;

    const filtered = partsForModel(modelProductId);
    const newPartOpt = `<div class="cb-option px-3 py-2 border-bottom" style="cursor:pointer;font-size:0.82rem;font-weight:600;color:#198754;"
         onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''"
         onclick="pickNewPart(${idx})">➕ New Aircon Part…</div>`;

    if (filtered.length === 0) {
        list.innerHTML = newPartOpt + `<div class="px-3 py-2 text-muted small">No existing parts for this model. Choose “New Aircon Part…” above.</div>`;
        return;
    }

    const partOpts = filtered.map(p =>
        `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
             data-value="${p.id}" data-cost="${p.cost}" data-label="${escAttr(p.name)}"
             data-product-id="${p.product_id ?? ''}"
             onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''"
             onclick="pickPartCombo(${idx}, '${p.id}', '${p.cost}', this.getAttribute('data-label'))">
            ${escAttr(p.name)}
        </div>`
    ).join('');

    list.innerHTML = newPartOpt + partOpts;
}

function showPartPicker(idx) {
    const picker = document.querySelector(`#pacb-${idx}`);
    if (picker) picker.style.display = '';
    const fields = document.querySelector(`.new-part-fields-${idx}`);
    if (fields) fields.style.display = 'none';
}

function showNewPartInput(idx) {
    const picker = document.querySelector(`#pacb-${idx}`);
    if (picker) picker.style.display = 'none';
    const fields = document.querySelector(`.new-part-fields-${idx}`);
    if (fields) fields.style.display = '';
}

function clearPartSelection(idx) {
    document.querySelector(`#row-${idx} .part-id-input`).value = '';
    const disp = document.querySelector(`.pacb-display-${idx}`);
    if (disp) {
        disp.textContent = 'Part…';
        disp.title = '';
        disp.style.color = '#6c757d';
        disp.classList.add('pacb-display-placeholder');
    }
    const nameInput = document.querySelector(`input[name="items[${idx}][new_part_name]"]`);
    if (nameInput) nameInput.value = '';
    showPartPicker(idx);
}

function isPartModelSet(idx) {
    return !document.querySelector(`.part-step2-${idx}`)?.classList.contains('is-locked');
}

function addPartRow(prefill) {
    document.getElementById('emptyState')?.remove();
    rowIndex++;
    const idx = rowIndex;

    const html = `
    <tr class="item-row part-row" id="row-${idx}" data-item="${idx}">
        <td class="text-center text-muted fw-semibold" id="row-label-${idx}">${idx}</td>

        <td class="po-item-product-cell po-col-product">
            <input type="hidden" name="items[${idx}][item_type]" value="part">
            <input type="hidden" name="items[${idx}][part_id]" class="part-id-input">
            <input type="hidden" name="items[${idx}][new_part_product_id]" class="new-part-product-input">

            <div class="po-part-cell">
                <div class="po-part-group">
                    <div class="po-part-group-badge" title="Aircon part">Part</div>
                    <div class="po-part-stack">
                        <div class="po-part-line po-part-line-model">
                            <div class="combobox position-relative" id="pmcb-${idx}">
                                <div class="form-control form-control-sm po-combobox-trigger d-flex justify-content-between align-items-center gap-1"
                                     style="cursor:pointer;user-select:none;background:#fff;"
                                     onclick="togglePartModelCombo(${idx})">
                                    <span class="pmcb-display-${idx} text-muted po-combobox-display">Model…</span>
                                    <i class="bi bi-chevron-down flex-shrink-0" style="font-size:0.7rem;color:#888;"></i>
                                </div>
                                <div class="pmcb-panel-${idx} border rounded po-combobox-panel">
                                    <div class="p-2 border-bottom">
                                        <input type="text" class="form-control form-control-sm pmcb-search-${idx}"
                                               placeholder="🔍 Search model…"
                                               oninput="searchPartModelCombo(${idx})"
                                               onclick="event.stopPropagation()">
                                    </div>
                                    <div class="pmcb-list-${idx} po-combobox-list-inner">
                                        ${partModelOptionsHtml(idx)}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="part-step2-${idx} po-part-line po-part-line-part is-locked">
                            <div class="combobox position-relative" id="pacb-${idx}">
                                <div class="form-control form-control-sm po-combobox-trigger d-flex justify-content-between align-items-center gap-1"
                                     style="cursor:pointer;user-select:none;background:#fff;"
                                     onclick="togglePartCombo(${idx})">
                                    <span class="pacb-display-${idx} text-muted po-combobox-display pacb-display-placeholder">Select model first</span>
                                    <i class="bi bi-chevron-down flex-shrink-0" style="font-size:0.7rem;color:#888;margin-top:2px;"></i>
                                </div>
                                <div class="pacb-panel-${idx} border rounded po-combobox-panel">
                                    <div class="p-2 border-bottom">
                                        <input type="text" class="form-control form-control-sm pacb-search-${idx}"
                                               placeholder="🔍 Search part…"
                                               oninput="searchPartCombo(${idx})"
                                               onclick="event.stopPropagation()">
                                    </div>
                                    <div class="pacb-list-${idx} po-combobox-list-inner"></div>
                                </div>
                            </div>
                            <div class="new-part-fields-${idx} po-part-new-name" style="display:none;">
                                <input type="text" class="form-control form-control-sm po-new-part-input" name="items[${idx}][new_part_name]"
                                       placeholder="Enter new part name">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </td>

        <td class="po-col-qty">
            <input type="number" class="form-control form-control-sm qty-input text-center" name="items[${idx}][quantity]"
                   value="1" min="1" required oninput="onQtyChange(${idx})" onchange="onQtyChange(${idx})">
        </td>

        <td class="po-col-cost">
            <input type="number" step="0.01" class="form-control form-control-sm cost-input po-num-input text-end"
                   name="items[${idx}][unit_cost]" value="" min="0" placeholder="₱" title="Unit cost" required
                   oninput="calcRow(${idx})" onchange="calcRow(${idx})">
        </td>

        <td class="po-col-disc">
            <div class="po-disc-pair">
                <input type="number" step="0.01" class="form-control form-control-sm disc-input po-num-input text-center"
                       name="items[${idx}][discount_percent]" value="" min="0" max="100" placeholder="%" title="Discount % (per unit)"
                       oninput="calcRow(${idx})" onchange="calcRow(${idx})">
                <input type="number" step="0.01" class="form-control form-control-sm discount-amount-input po-num-input text-end"
                       name="items[${idx}][discount_amount]" value="" min="0" placeholder="₱" title="Fixed discount per unit"
                       oninput="calcRow(${idx})" onchange="calcRow(${idx})">
            </div>
        </td>

        <td class="po-col-total text-end fw-bold text-primary">₱<span id="total-${idx}" class="total-display">0.00</span></td>

        <td class="po-col-action text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(${idx})" style="padding:0 4px;line-height:1.2;">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>`;

    document.getElementById('itemsTableBody').insertAdjacentHTML('beforeend', html);

    if (prefill) {
        const row = document.getElementById(`row-${idx}`);

        if (prefill.part_id) {
            const p = partById(prefill.part_id);
            const modelId = p?.product_id ?? '';
            const modelLabel = modelId
                ? (products.find(pr => String(pr.id) === String(modelId))?.label || p?.linked_model_label || 'Linked model')
                : 'General / Unlinked';
            pickPartModel(idx, modelId, modelLabel, true);
            buildPartOptionsForRow(idx, modelId);
            pickPartCombo(idx, prefill.part_id, prefill.unit_cost ?? (p ? p.cost : 0), prefill.label || (p ? p.name : ('Part #' + prefill.part_id)), true);
        } else if (prefill.new_part_name) {
            const modelId = prefill.new_part_product_id ?? '';
            const modelLabel = modelId
                ? (products.find(pr => String(pr.id) === String(modelId))?.label || 'Linked model')
                : 'General / Unlinked';
            pickPartModel(idx, modelId, modelLabel, true);
            pickNewPart(idx, true);
            row.querySelector(`input[name="items[${idx}][new_part_name]"]`).value = prefill.new_part_name;
        }

        row.querySelector('.qty-input').value = prefill.quantity || 1;
        if (prefill.unit_cost !== '' && prefill.unit_cost != null) {
            row.querySelector('.cost-input').value = parseFloat(prefill.unit_cost).toFixed(2);
        }

        setRowDiscounts(row, prefill);

        calcRow(idx);
    }
}

/* ── PART MODEL + PART COMBOBOX FUNCTIONS ── */
function togglePartModelCombo(idx) {
    const panel   = document.querySelector(`.pmcb-panel-${idx}`);
    const trigger = document.querySelector(`#pmcb-${idx} .po-combobox-trigger`);
    const wasOpen = poComboIsOpen(panel);
    closeAllCombos();
    if (!wasOpen) {
        poComboOpen(trigger, panel);
        document.querySelector(`.pmcb-search-${idx}`)?.focus();
    }
}

function searchPartModelCombo(idx) {
    const term = document.querySelector(`.pmcb-search-${idx}`).value.toLowerCase();
    const panel = document.querySelector(`.pmcb-panel-${idx}`);
    (panel ? panel : document).querySelectorAll('.cb-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function pickPartModel(idx, value, label, skipPartRebuild) {
    document.querySelector(`#row-${idx} .new-part-product-input`).value = value;

    const disp = document.querySelector(`.pmcb-display-${idx}`);
    disp.textContent = label;
    disp.title = label;
    disp.style.color = '#212529';

    document.querySelector(`.part-step2-${idx}`)?.classList.remove('is-locked');
    const partDisp = document.querySelector(`.pacb-display-${idx}`);
    if (partDisp) partDisp.classList.remove('pacb-display-placeholder');
    if (!skipPartRebuild) {
        clearPartSelection(idx);
        buildPartOptionsForRow(idx, value);
    }

    poComboClose(document.querySelector(`.pmcb-panel-${idx}`));
    const search = document.querySelector(`.pmcb-search-${idx}`);
    if (search) { search.value = ''; searchPartModelCombo(idx); }
}

function togglePartCombo(idx) {
    if (!isPartModelSet(idx)) {
        alert('Please select an AC model first.');
        return;
    }
    const panel   = document.querySelector(`.pacb-panel-${idx}`);
    const trigger = document.querySelector(`#pacb-${idx} .po-combobox-trigger`);
    const wasOpen = poComboIsOpen(panel);
    closeAllCombos();
    if (!wasOpen) {
        poComboOpen(trigger, panel);
        document.querySelector(`.pacb-search-${idx}`)?.focus();
    }
}

function searchPartCombo(idx) {
    const term = document.querySelector(`.pacb-search-${idx}`).value.toLowerCase();
    const panel = document.querySelector(`.pacb-panel-${idx}`);
    (panel ? panel : document).querySelectorAll('.cb-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function pickPartCombo(idx, value, cost, label, skipCalc) {
    document.querySelector(`#row-${idx} .part-id-input`).value = value;

    const disp = document.querySelector(`.pacb-display-${idx}`);
    disp.textContent = label;
    disp.title = label;
    disp.style.color = '#212529';
    disp.classList.remove('pacb-display-placeholder');

    showPartPicker(idx);
    document.querySelector(`.new-part-fields-${idx}`).style.display = 'none';
    const row = document.getElementById(`row-${idx}`);
    row.querySelector(`input[name="items[${idx}][new_part_name]"]`).value = '';

    if (cost !== null && cost !== undefined && parseFloat(cost) > 0 && row.querySelector('.cost-input').value === '') {
        row.querySelector('.cost-input').value = parseFloat(cost).toFixed(2);
    }

    poComboClose(document.querySelector(`.pacb-panel-${idx}`));
    const search = document.querySelector(`.pacb-search-${idx}`);
    if (search) { search.value = ''; searchPartCombo(idx); }

    if (!skipCalc) calcRow(idx);
}

function pickNewPart(idx, skipFocus) {
    if (!isPartModelSet(idx)) {
        alert('Please select an AC model first.');
        return;
    }

    document.querySelector(`#row-${idx} .part-id-input`).value = '';

    showNewPartInput(idx);
    poComboClose(document.querySelector(`.pacb-panel-${idx}`));

    if (!skipFocus) {
        document.querySelector(`input[name="items[${idx}][new_part_name]"]`)?.focus();
    }
}

function closeAllPartCombos() {
    document.querySelectorAll('[class*="pacb-panel-"]').forEach(poComboClose);
}

function closeAllPartModelCombos() {
    document.querySelectorAll('[class*="pmcb-panel-"]').forEach(poComboClose);
}

function closeAllCombos() {
    closeAllPoComboPanels();
}

/* ── PO COMBOBOX FUNCTIONS ── */
function togglePOCombo(idx) {
    const panel   = document.querySelector(`.pocb-panel-${idx}`);
    const trigger = document.querySelector(`#pocb-${idx} .po-combobox-trigger`);
    const wasOpen = poComboIsOpen(panel);
    closeAllCombos();
    if (!wasOpen) {
        poComboOpen(trigger, panel);
        document.querySelector(`.pocb-search-${idx}`)?.focus();
    }
}

function searchPOCombo(idx) {
    const term = document.querySelector(`.pocb-search-${idx}`).value.toLowerCase();
    const panel = document.querySelector(`.pocb-panel-${idx}`);
    (panel ? panel : document).querySelectorAll('.cb-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function pickPOCombo(idx, value, cost, label, unitType) {
    const sel = document.querySelector(`select[name="items[${idx}][product_id]"]`);
    sel.value = value;

    const disp = document.querySelector(`.pocb-display-${idx}`);
    disp.textContent = label;
    disp.title = label;
    disp.style.color = '#212529';

    const badge = document.querySelector(`.pocb-badge-${idx}`);
    badge.innerHTML = unitType ? unitTypeBadge(unitType) : '';

    const row = document.getElementById(`row-${idx}`);
    row.querySelector('.cost-input').value = '';

    poComboClose(document.querySelector(`.pocb-panel-${idx}`));
    document.querySelector(`.pocb-search-${idx}`).value = '';
    searchPOCombo(idx);

    calcRow(idx);
    refreshDropdowns();
}

function closeAllPOCombos() {
    document.querySelectorAll('[class*="pocb-panel-"]').forEach(poComboClose);
}

function setRowDiscounts(row, prefill) {
    if (!row || !prefill) return;

    let pct = prefill.discount_percent ?? prefill.discount ?? '';
    let amt = prefill.discount_amount ?? '';
    if (prefill.unit_discounts?.length) {
        pct = prefill.unit_discounts[0].discount_percent ?? pct;
        amt = prefill.unit_discounts[0].discount_amount ?? amt;
    }

    const pctInput = row.querySelector('.disc-input');
    const amtInput = row.querySelector('.discount-amount-input');
    if (pctInput) pctInput.value = pct === 0 || pct === '' ? '' : pct;
    if (amtInput) amtInput.value = amt === 0 || amt === '' ? '' : amt;
}

function calcRow(idx) {

    const row = document.getElementById(`row-${idx}`);
    if (!row) return;

    const qty  = parseInt(row.querySelector('.qty-input').value) || 0;
    const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
    const discPct = parseFloat(row.querySelector('.disc-input')?.value) || 0;
    const discAmt = parseFloat(row.querySelector('.discount-amount-input')?.value) || 0;
    const netUnit = Math.max(0, cost * (1 - discPct / 100) - discAmt);
    const total   = qty * netUnit;

    document.getElementById(`total-${idx}`).textContent = formatMoney(total);

    calcGrandTotal();
}

function formatMoney(value) {
    return parseFloat(value || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.total-display').forEach(el => {
        grand += parseFloat(el.textContent.replace(/,/g, '')) || 0;
    });
    document.getElementById('grandTotal').textContent = formatMoney(grand);
}

function updateBalancePreview() {}

function removeRow(idx) {
    document.getElementById(`row-${idx}`)?.remove();
    if (!document.querySelector('.item-row')) {
        document.getElementById('itemsTableBody').innerHTML = `
            <tr id="emptyState">
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                    No products added yet. Click "Add Product" above.
                </td>
            </tr>`;
    }
    refreshDropdowns();
    calcGrandTotal();
}

function refreshDropdowns() {
    const usedIds = new Set();
    document.querySelectorAll('.item-row').forEach(row => {
        const sel = row.querySelector('.product-select');
        if (sel?.value) usedIds.add(sel.value);
    });
    document.querySelectorAll('.item-row').forEach(row => {
        const sel = row.querySelector('.product-select');
        const cur = sel?.value;
        const idx = row.id.replace('row-', '');
        document.querySelectorAll(`#pocb-${idx} .cb-option`).forEach(opt => {
            const val   = opt.getAttribute('data-value');
            const taken = val !== cur && usedIds.has(val);
            opt.style.opacity        = taken ? '0.3' : '1';
            opt.style.textDecoration = taken ? 'line-through' : '';
            opt.style.pointerEvents  = taken ? 'none' : '';
        });
    });
}

document.addEventListener('click', e => {
    if (!e.target.closest('.combobox') && !e.target.closest('.po-combobox-panel.is-floating')) {
        closeAllCombos();
    }
});

window.addEventListener('resize', closeAllCombos);
document.addEventListener('scroll', e => {
    if (e.target.closest && e.target.closest('.po-combobox-panel.is-floating')) return;
    if (document.querySelector('.po-combobox-panel.is-floating')) closeAllCombos();
}, true);

/* ── Payment type note ── */
function togglePaymentNote() {
    const is45 = document.getElementById('paymentType').value === '45days';
    document.getElementById('paymentNote45').style.display = is45 ? '' : 'none';
}
document.getElementById('paymentType').addEventListener('change', togglePaymentNote);

document.getElementById('poForm').addEventListener('submit', function (e) {
    if (!document.querySelector('.item-row')) {
        e.preventDefault(); alert('Please add at least one product.'); return;
    }

    let invalidPart = false;
    document.querySelectorAll('.part-row').forEach(row => {
        const idx       = row.id.replace('row-', '');
        const modelSet  = isPartModelSet(idx);
        const partId    = row.querySelector('.part-id-input')?.value;
        const newName   = row.querySelector(`input[name="items[${idx}][new_part_name]"]`)?.value.trim();
        if (!modelSet) { invalidPart = true; return; }
        if (!partId && !newName) invalidPart = true;
    });
    if (invalidPart) {
        e.preventDefault();
        alert('For each aircon part row: select an AC model first, then choose an existing part or enter a new part name.');
        return;
    }
});

/* ── Prefill existing items (or repopulate after a validation error) ── */
@php
    $prefillItems = [];
    if (old('items')) {
        foreach (array_values(old('items')) as $oi) {
            if (($oi['item_type'] ?? 'product') === 'part') {
                $prefillItems[] = [
                    'item_type'           => 'part',
                    'part_id'             => $oi['part_id'] ?? '',
                    'new_part_name'       => $oi['new_part_name'] ?? '',
                    'new_part_product_id' => $oi['new_part_product_id'] ?? '',
                    'quantity'            => (int) ($oi['quantity'] ?? 1),
                    'unit_cost'           => $oi['unit_cost'] ?? '',
                    'discount_percent'    => $oi['discount_percent'] ?? 0,
                    'discount_amount'     => $oi['discount_amount'] ?? 0,
                    'unit_discounts'      => $oi['unit_discounts'] ?? null,
                ];
                continue;
            }
            if (empty($oi['product_id'])) continue;
            $prefillItems[] = [
                'item_type'        => 'product',
                'product_id'       => $oi['product_id'],
                'quantity'         => (int) ($oi['quantity'] ?? 1),
                'unit_cost'        => $oi['unit_cost'] ?? '',
                'discount_percent' => $oi['discount_percent'] ?? 0,
                'discount_amount'  => $oi['discount_amount'] ?? 0,
                'unit_discounts'   => $oi['unit_discounts'] ?? null,
            ];
        }
    } else {
        foreach ($purchaseOrder->items as $it) {
            if ($it->is_part) {
                $prefillItems[] = [
                    'item_type'           => 'part',
                    'part_id'             => $it->part_id,
                    'label'               => $it->part->name,
                    'new_part_product_id' => $it->part->product_id,
                    'quantity'            => $it->quantity_ordered,
                    'unit_cost'           => $it->unit_cost,
                    'discount_percent'    => $it->discount_percent ?? 0,
                    'discount_amount'     => $it->discount_amount ?? 0,
                    'unit_discounts'      => $it->unit_discounts ?? null,
                ];
                continue;
            }
            $prefillItems[] = [
                'item_type'        => 'product',
                'product_id'       => $it->product_id,
                'quantity'         => $it->quantity_ordered,
                'unit_cost'        => $it->unit_cost,
                'discount_percent' => $it->discount_percent ?? 0,
                'discount_amount'  => $it->discount_amount ?? 0,
                'unit_discounts'   => $it->unit_discounts ?? null,
            ];
        }
    }
@endphp
const prefillItems = @json($prefillItems);
const productMap = {};
products.forEach(p => productMap[p.id] = p);

prefillItems.forEach(it => {
    if (it.item_type === 'part') {
        addPartRow({
            part_id:             it.part_id || '',
            new_part_name:       it.new_part_name || '',
            new_part_product_id: it.new_part_product_id || '',
            label:               it.label || '',
            quantity:            parseInt(it.quantity) || 1,
            unit_cost:           it.unit_cost ?? '',
            discount_percent:    it.discount_percent ?? 0,
            discount_amount:     it.discount_amount ?? 0,
            unit_discounts:      it.unit_discounts ?? null,
        });
        return;
    }

    const p = productMap[it.product_id] || {};
    addItem({
        product_id:       it.product_id,
        label:            p.label || '',
        unit_type:        p.unit_type || '',
        quantity:         parseInt(it.quantity) || 1,
        unit_cost:        it.unit_cost ?? '',
        discount_percent: it.discount_percent ?? 0,
        discount_amount:  it.discount_amount ?? 0,
        unit_discounts:   it.unit_discounts ?? null,
    });
});

togglePaymentNote();
</script>
@endpush
@endsection
