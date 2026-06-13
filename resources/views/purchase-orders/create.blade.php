@extends('layouts.app')

@section('title', 'Create Purchase Order')

@push('styles')
<style>
    /* Order items table: scroll horizontally only when the viewport is too
       narrow to fit it (phones); on tablets/desktop it sits flush with no
       scrollbar so the product combobox dropdown isn't clipped. */
    @media (max-width: 799.98px) {
        .po-items-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    }
    @media (min-width: 800px) {
        .po-items-scroll { overflow: visible; }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    <x-page-header title="Create Purchase Order" subtitle="Order products from supplier" icon="bi-cart-plus">
        <x-slot name="actions">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </x-slot>
    </x-page-header>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- ── SOLD TO / DELIVERED TO header (static, compact) ── --}}
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
            <div class="border rounded bg-white px-2 py-1 h-100" style="font-size:0.72rem;line-height:1.35;">
                <span class="fw-bold text-uppercase text-primary" style="font-size:0.66rem;letter-spacing:.5px;"><i class="bi bi-truck"></i> Delivered To</span>
                <div class="text-muted">Customer No. : 1378</div>
                <div class="fw-semibold">RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC</div>
            </div>
        </div>
    </div>

    <form action="{{ route('purchase-orders.store') }}" method="POST" id="poForm">
        @csrf

        <div class="row g-3">

            {{-- Left Column --}}
            <div class="col-md-8">

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
                                        <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Order / DR Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="order_date"
                                       value="{{ old('order_date', date('Y-m-d')) }}" required id="orderDate">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Expected Delivery</label>
                                <input type="date" class="form-control form-control-sm" name="expected_delivery_date"
                                       value="{{ old('expected_delivery_date', date('Y-m-d')) }}">
                            </div>

                            {{-- Row 2: PO reference (DR + serials are entered at Order Receiving) --}}
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">PO No. <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="supplier_po_number"
                                       value="{{ old('supplier_po_number') }}"
                                       placeholder="e.g. 698"
                                       style="font-family:monospace;" required>
                            </div>
                            <div class="col-md-8 d-flex align-items-end">
                                <div class="alert alert-info py-2 px-3 mb-0 w-100" style="font-size:0.78rem;">
                                    <i class="bi bi-info-circle"></i>
                                    Document No. (DR) and serial numbers are encoded later in <strong>Order Receiving</strong> when stock arrives.
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
                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="addPartRow()">
                                <i class="bi bi-nut"></i> Add Aircon Part
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="po-items-scroll">
                        <table class="table table-sm align-middle mb-0" id="itemsTable" style="font-size:0.82rem;table-layout:fixed;width:100%;min-width:760px;">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:30px;" class="text-center">#</th>
                                    <th>Product <span class="text-danger">*</span></th>
                                    <th style="width:62px;" class="text-center">QTY <span class="text-danger">*</span></th>
                                    <th style="width:118px;">Unit Cost</th>
                                    <th style="width:62px;" class="text-center">Disc %</th>
                                    <th style="width:108px;">Disc (₱)</th>
                                    <th style="width:80px;" class="text-end">Net</th>
                                    <th style="width:92px;" class="text-end">Total</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <tr id="emptyState">
                                    <td colspan="9" class="text-center text-muted py-4">
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
                                  placeholder="Optional notes about this purchase order">{{ old('notes') }}</textarea>
                    </div>
                </div>

            </div>

            {{-- Right Column --}}
            <div class="col-md-4">

                {{-- Order Summary --}}
                <div class="card border-0 shadow-sm mb-3 sticky-top" style="top:20px;">
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
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="payment_type" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>
                                <option value="full"   {{ old('payment_type') == 'full'   ? 'selected' : '' }}>Full Payment</option>
                                <option value="45days" {{ old('payment_type') == '45days' ? 'selected' : '' }}>45-Day Term</option>
                            </select>
                            <small class="text-muted">45-Day: balance due in 45 days</small>
                        </div>

                        <div id="deadlinePreview" style="display:none;" class="mb-3">
                            <div class="alert alert-warning py-2 mb-0" style="font-size:0.85rem;">
                                <div class="fw-semibold mb-1"><i class="bi bi-calendar-event"></i> Payment Due:</div>
                                <div class="fw-bold text-dark" id="deadlineDate">—</div>
                                <small class="text-muted">(45 days from order date)</small>
                            </div>
                        </div>

                        <div id="downpaymentSection" style="display:none;" class="border-top pt-3">
                            <h6 class="small fw-semibold text-muted mb-2">Downpayment (Optional)</h6>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Amount</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control" name="downpayment_amount"
                                           id="downpaymentAmount" value="{{ old('downpayment_amount', 0) }}" min="0">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Payment Date</label>
                                <input type="date" class="form-control form-control-sm" name="downpayment_date"
                                       value="{{ old('downpayment_date', date('Y-m-d')) }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Method</label>
                                <select class="form-select form-select-sm" name="downpayment_method">
                                    <option value="cash">💵 Cash</option>
                                    <option value="gcash">📱 GCash</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="cheque">🧾 Cheque</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Reference #</label>
                                <input type="text" class="form-control form-control-sm" name="downpayment_reference"
                                       value="{{ old('downpayment_reference') }}" placeholder="Optional">
                            </div>
                            <div class="alert alert-info py-2 mb-0" id="balancePreview" style="display:none;font-size:0.85rem;">
                                <div class="fw-semibold mb-1">Balance Summary:</div>
                                <div class="d-flex justify-content-between">
                                    <span>Total:</span>
                                    <span class="fw-semibold">₱<span id="previewTotal">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Downpayment:</span>
                                    <span class="fw-semibold text-success">₱<span id="previewDown">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                    <span class="fw-bold">Balance Due:</span>
                                    <span class="fw-bold text-danger">₱<span id="previewBalance">0.00</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit"
                    class="btn btn-success btn-lg w-100 shadow"
                    style="font-size:1.05rem;padding:12px 0;">
                <i class="bi bi-check-circle-fill"></i>
                CREATE PURCHASE ORDER
            </button>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const products = {!! json_encode($productsJson) !!};
const parts = {!! json_encode($partsJson) !!};
let rowIndex = 0;

function unitTypeBadge(unitType) {
    if (!unitType) return '';
    if (unitType === 'set') {
        return `<span style="font-size:0.7rem;padding:1px 6px;border-radius:20px;background:#7c3aed15;color:#7c3aed;border:1px solid #7c3aed40;font-weight:600;white-space:nowrap;">❄️🌀 Set</span>`;
    }
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
        const costStr   = p.cost > 0 ? ` — ₱${parseFloat(p.cost).toFixed(2)}` : ' — No cost set';
        const badgeHtml = p.unit_type ? unitTypeBadge(p.unit_type) : '';
        return `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                     data-value="${p.id}" data-cost="${p.cost}" data-label="${p.label}"
                     data-unit-type="${p.unit_type || ''}"
                     onmouseenter="this.style.background='#f0f4ff'"
                     onmouseleave="this.style.background=''"
                     onclick="pickPOCombo(${idx}, '${p.id}', '${p.cost}', this.getAttribute('data-label'), this.getAttribute('data-unit-type'))">
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span>${p.label}${costStr}</span>${badgeHtml}
                  </div>
                </div>`;
    }).join('');

    const html = `
    <tr class="item-row" id="row-${idx}" data-item="${idx}">
        <td class="text-center text-muted fw-semibold" id="row-label-${idx}">${idx}</td>

        {{-- Product --}}
        <td>
            <input type="hidden" name="items[${idx}][item_type]" value="product">
            <select name="items[${idx}][product_id]" class="product-select d-none" data-row="${idx}" required>
                <option value="">-- Select --</option>
                ${products.map(p => `<option value="${p.id}" data-cost="${p.cost}">${p.label}</option>`).join('')}
            </select>
            <div class="combobox position-relative" id="pocb-${idx}">
                <div class="form-control form-control-sm d-flex justify-content-between align-items-center gap-1"
                     style="cursor:pointer;user-select:none;background:#fff;overflow:hidden;"
                     onclick="togglePOCombo(${idx})">
                    <div class="d-flex align-items-center gap-1 flex-nowrap" style="flex:1;min-width:0;overflow:hidden;">
                        <span class="pocb-display-${idx} text-muted text-truncate" style="font-size:0.82rem;min-width:0;">-- Select Product --</span>
                        <span class="pocb-badge-${idx} flex-shrink-0"></span>
                    </div>
                    <i class="bi bi-chevron-down flex-shrink-0" style="font-size:0.7rem;color:#888;"></i>
                </div>
                <div class="pocb-panel-${idx} position-absolute bg-white border rounded shadow-sm"
                     style="display:none;z-index:9999;top:100%;left:0;min-width:280px;">
                    <div class="p-2 border-bottom">
                        <input type="text" class="form-control form-control-sm pocb-search-${idx}"
                               placeholder="🔍 Search product…"
                               oninput="searchPOCombo(${idx})"
                               onclick="event.stopPropagation()">
                    </div>
                    <div class="pocb-list-${idx}" style="max-height:220px;overflow-y:auto;">
                        ${cbOpts}
                    </div>
                </div>
            </div>
        </td>

        {{-- Qty --}}
        <td>
            <input type="number" class="form-control form-control-sm qty-input text-center" name="items[${idx}][quantity]"
                   value="1" min="1" required onchange="onQtyChange(${idx})">
        </td>

        {{-- Unit Cost (optional) --}}
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" class="form-control cost-input" name="items[${idx}][unit_cost]"
                       value="" min="0" placeholder="0.00" onchange="calcRow(${idx})">
            </div>
        </td>

        {{-- Disc % --}}
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm disc-input text-center"
                   name="items[${idx}][discount_percent]" value="" min="0" max="100" placeholder="0" onchange="calcRow(${idx})">
        </td>

        {{-- Disc ₱ --}}
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" class="form-control discount-amount-input"
                       name="items[${idx}][discount_amount]" value="" min="0" placeholder="0.00" onchange="calcRow(${idx})">
            </div>
        </td>

        {{-- Net --}}
        <td class="text-end">
            <input type="text" class="form-control form-control-sm text-end" id="net-${idx}" readonly value="0.00"
                   style="background:#f8f9fa;">
        </td>

        {{-- Total --}}
        <td class="text-end fw-bold text-primary">₱<span id="total-${idx}" class="total-display">0.00</span></td>

        {{-- Action --}}
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(${idx})" style="padding:1px 7px;">
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
        if (disp && prefill.label) { disp.textContent = prefill.label; disp.style.color = '#212529'; }

        const badge = document.querySelector(`.pocb-badge-${idx}`);
        if (badge && prefill.unit_type) badge.innerHTML = unitTypeBadge(prefill.unit_type);

        const row = document.getElementById(`row-${idx}`);
        row.querySelector('.qty-input').value = prefill.quantity;
        if (prefill.unit_cost !== '' && prefill.unit_cost != null) {
            row.querySelector('.cost-input').value = parseFloat(prefill.unit_cost).toFixed(2);
        }
        row.querySelector('.disc-input').value = (prefill.discount_percent ?? prefill.discount) || '';
        row.querySelector('.discount-amount-input').value = prefill.discount_amount || '';

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

function addPartRow(prefill) {
    document.getElementById('emptyState')?.remove();
    rowIndex++;
    const idx = rowIndex;

    const newPartOpt = `<div class="cb-option px-3 py-2 border-bottom" style="cursor:pointer;font-size:0.82rem;font-weight:600;color:#198754;"
                 onmouseenter="this.style.background='#f0f4ff'"
                 onmouseleave="this.style.background=''"
                 onclick="pickNewPart(${idx})">
              ➕ New Aircon Part…
            </div>`;

    const partOpts = parts.map(p => {
        const linked  = p.linked_model_label || 'Unlinked';
        const costStr = p.cost > 0 ? ` — ₱${parseFloat(p.cost).toFixed(2)}` : '';
        return `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                     data-value="${p.id}" data-cost="${p.cost}" data-label="${p.name}"
                     onmouseenter="this.style.background='#f0f4ff'"
                     onmouseleave="this.style.background=''"
                     onclick="pickPartCombo(${idx}, '${p.id}', '${p.cost}', this.getAttribute('data-label'))">
                  <div class="d-flex justify-content-between align-items-center gap-2">
                    <span class="text-truncate">${p.name}${costStr}</span>
                    <span class="text-muted text-truncate" style="font-size:0.72rem;flex-shrink:0;">${linked} · stock ${p.stock_quantity}</span>
                  </div>
                </div>`;
    }).join('');

    const html = `
    <tr class="item-row part-row" id="row-${idx}" data-item="${idx}">
        <td class="text-center text-muted fw-semibold" id="row-label-${idx}">${idx}</td>

        {{-- Part --}}
        <td>
            <input type="hidden" name="items[${idx}][item_type]" value="part">
            <input type="hidden" name="items[${idx}][part_id]" class="part-id-input">
            <div class="combobox position-relative" id="pacb-${idx}">
                <div class="form-control form-control-sm d-flex justify-content-between align-items-center gap-1"
                     style="cursor:pointer;user-select:none;background:#fff;overflow:hidden;"
                     onclick="togglePartCombo(${idx})">
                    <div class="d-flex align-items-center gap-1 flex-nowrap" style="flex:1;min-width:0;overflow:hidden;">
                        <span class="pacb-display-${idx} text-muted text-truncate" style="font-size:0.82rem;min-width:0;">-- Select Aircon Part --</span>
                        <span class="flex-shrink-0" style="font-size:0.7rem;padding:1px 6px;border-radius:20px;background:#fd7e1415;color:#fd7e14;border:1px solid #fd7e1440;font-weight:600;white-space:nowrap;">🔧 Aircon Part</span>
                    </div>
                    <i class="bi bi-chevron-down flex-shrink-0" style="font-size:0.7rem;color:#888;"></i>
                </div>
                <div class="pacb-panel-${idx} position-absolute bg-white border rounded shadow-sm"
                     style="display:none;z-index:9999;top:100%;left:0;min-width:280px;">
                    <div class="p-2 border-bottom">
                        <input type="text" class="form-control form-control-sm pacb-search-${idx}"
                               placeholder="🔍 Search part…"
                               oninput="searchPartCombo(${idx})"
                               onclick="event.stopPropagation()">
                    </div>
                    <div class="pacb-list-${idx}" style="max-height:220px;overflow-y:auto;">
                        ${newPartOpt}${partOpts}
                    </div>
                </div>
            </div>
            <div class="new-part-fields-${idx} mt-1" style="display:none;">
                <input type="text" class="form-control form-control-sm mb-1" name="items[${idx}][new_part_name]"
                       placeholder="New part name">
                <input type="hidden" name="items[${idx}][new_part_product_id]" class="new-part-product-input">
                <div class="combobox position-relative" id="nplcb-${idx}">
                    <div class="form-control form-control-sm d-flex justify-content-between align-items-center gap-1"
                         style="cursor:pointer;user-select:none;background:#fff;overflow:hidden;"
                         onclick="toggleNewPartLinkCombo(${idx})">
                        <span class="nplcb-display-${idx} text-muted text-truncate" style="font-size:0.8rem;min-width:0;" title="-- Linked model (optional) --">-- Linked model (optional) --</span>
                        <i class="bi bi-chevron-down flex-shrink-0" style="font-size:0.7rem;color:#888;"></i>
                    </div>
                    <div class="nplcb-panel-${idx} position-absolute bg-white border rounded shadow-sm"
                         style="display:none;z-index:9999;top:100%;left:0;min-width:280px;max-width:380px;">
                        <div class="p-2 border-bottom">
                            <input type="text" class="form-control form-control-sm nplcb-search-${idx}"
                                   placeholder="🔍 Search model…"
                                   oninput="searchNewPartLinkCombo(${idx})"
                                   onclick="event.stopPropagation()">
                        </div>
                        <div class="nplcb-list-${idx}" style="max-height:220px;overflow-y:auto;">
                            <div class="cb-option px-3 py-2 border-bottom text-muted" style="cursor:pointer;font-size:0.82rem;"
                                 onmouseenter="this.style.background='#f0f4ff'"
                                 onmouseleave="this.style.background=''"
                                 onclick="pickNewPartLinkCombo(${idx}, '', '-- Linked model (optional) --')">
                                None / Unlinked
                            </div>
                            ${products.map(p => `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                                 data-value="${p.id}" data-label="${p.label}"
                                 onmouseenter="this.style.background='#f0f4ff'"
                                 onmouseleave="this.style.background=''"
                                 onclick="pickNewPartLinkCombo(${idx}, '${p.id}', this.getAttribute('data-label'))">
                                ${p.label}
                            </div>`).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </td>

        {{-- Qty --}}
        <td>
            <input type="number" class="form-control form-control-sm qty-input text-center" name="items[${idx}][quantity]"
                   value="1" min="1" required onchange="onQtyChange(${idx})">
        </td>

        {{-- Unit Cost (optional) --}}
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" class="form-control cost-input" name="items[${idx}][unit_cost]"
                       value="" min="0" placeholder="0.00" onchange="calcRow(${idx})">
            </div>
        </td>

        {{-- Disc % --}}
        <td>
            <input type="number" step="0.01" class="form-control form-control-sm disc-input text-center"
                   name="items[${idx}][discount_percent]" value="" min="0" max="100" placeholder="0" onchange="calcRow(${idx})">
        </td>

        {{-- Disc ₱ --}}
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₱</span>
                <input type="number" step="0.01" class="form-control discount-amount-input"
                       name="items[${idx}][discount_amount]" value="" min="0" placeholder="0.00" onchange="calcRow(${idx})">
            </div>
        </td>

        {{-- Net --}}
        <td class="text-end">
            <input type="text" class="form-control form-control-sm text-end" id="net-${idx}" readonly value="0.00"
                   style="background:#f8f9fa;">
        </td>

        {{-- Total --}}
        <td class="text-end fw-bold text-primary">₱<span id="total-${idx}" class="total-display">0.00</span></td>

        {{-- Action --}}
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(${idx})" style="padding:1px 7px;">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>`;

    document.getElementById('itemsTableBody').insertAdjacentHTML('beforeend', html);

    // Pre-fill (editing or restoring after a validation error)
    if (prefill) {
        const row = document.getElementById(`row-${idx}`);

        if (prefill.part_id) {
            const p = partById(prefill.part_id);
            pickPartCombo(idx, prefill.part_id, prefill.unit_cost ?? (p ? p.cost : 0), p ? p.name : ('Part #' + prefill.part_id), true);
        } else if (prefill.new_part_name) {
            pickNewPart(idx, true);
            row.querySelector(`input[name="items[${idx}][new_part_name]"]`).value = prefill.new_part_name;
            if (prefill.new_part_product_id) {
                const linkedProduct = products.find(p => String(p.id) === String(prefill.new_part_product_id));
                pickNewPartLinkCombo(idx, prefill.new_part_product_id, linkedProduct ? linkedProduct.label : 'Linked model');
            }
        }

        row.querySelector('.qty-input').value = prefill.quantity || 1;
        if (prefill.unit_cost !== '' && prefill.unit_cost != null) {
            row.querySelector('.cost-input').value = parseFloat(prefill.unit_cost).toFixed(2);
        }
        row.querySelector('.disc-input').value = (prefill.discount_percent ?? prefill.discount) || '';
        row.querySelector('.discount-amount-input').value = prefill.discount_amount || '';

        calcRow(idx);
    } else if (parts.length === 0) {
        // No parts in the catalog yet — skip straight to "type a new part" entry
        pickNewPart(idx);
    }
}

/* ── PART COMBOBOX FUNCTIONS ── */
function togglePartCombo(idx) {
    const panel  = document.querySelector(`.pacb-panel-${idx}`);
    const isOpen = panel.style.display !== 'none';
    closeAllCombos();
    if (!isOpen) {
        panel.style.display = '';
        document.querySelector(`.pacb-search-${idx}`)?.focus();
    }
}

function searchPartCombo(idx) {
    const term = document.querySelector(`.pacb-search-${idx}`).value.toLowerCase();
    document.querySelectorAll(`#pacb-${idx} .cb-option`).forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function pickPartCombo(idx, value, cost, label, skipCalc) {
    document.querySelector(`#row-${idx} .part-id-input`).value = value;

    const disp = document.querySelector(`.pacb-display-${idx}`);
    disp.textContent = label;
    disp.title = label;
    disp.style.color = '#212529';

    document.querySelector(`.new-part-fields-${idx}`).style.display = 'none';
    const row = document.getElementById(`row-${idx}`);
    row.querySelector(`input[name="items[${idx}][new_part_name]"]`).value = '';
    row.querySelector(`.new-part-product-input`).value = '';
    pickNewPartLinkCombo(idx, '', '-- Linked model (optional) --');

    if (cost !== null && cost !== undefined && row.querySelector('.cost-input').value === '') {
        row.querySelector('.cost-input').value = parseFloat(cost).toFixed(2);
    }

    document.querySelector(`.pacb-panel-${idx}`).style.display = 'none';
    const search = document.querySelector(`.pacb-search-${idx}`);
    if (search) { search.value = ''; searchPartCombo(idx); }

    if (!skipCalc) calcRow(idx);
}

function pickNewPart(idx, skipFocus) {
    document.querySelector(`#row-${idx} .part-id-input`).value = '';

    const disp = document.querySelector(`.pacb-display-${idx}`);
    disp.textContent = '➕ New Aircon Part…';
    disp.style.color = '#212529';

    document.querySelector(`.new-part-fields-${idx}`).style.display = '';
    document.querySelector(`.pacb-panel-${idx}`).style.display = 'none';

    if (!skipFocus) {
        document.querySelector(`input[name="items[${idx}][new_part_name]"]`)?.focus();
    }
}

function closeAllPartCombos() {
    document.querySelectorAll('.part-row').forEach(row => {
        const idx = row.id.replace('row-', '');
        const p   = document.querySelector(`.pacb-panel-${idx}`);
        if (p) p.style.display = 'none';
    });
}

/* ── NEW-PART "LINKED MODEL" COMBOBOX FUNCTIONS ── */
function toggleNewPartLinkCombo(idx) {
    const panel  = document.querySelector(`.nplcb-panel-${idx}`);
    const isOpen = panel.style.display !== 'none';
    closeAllCombos();
    if (!isOpen) {
        panel.style.display = '';
        document.querySelector(`.nplcb-search-${idx}`)?.focus();
    }
}

function searchNewPartLinkCombo(idx) {
    const term = document.querySelector(`.nplcb-search-${idx}`).value.toLowerCase();
    document.querySelectorAll(`#nplcb-${idx} .cb-option`).forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function pickNewPartLinkCombo(idx, value, label) {
    document.querySelector(`#row-${idx} .new-part-product-input`).value = value;

    const disp = document.querySelector(`.nplcb-display-${idx}`);
    disp.textContent = label;
    disp.title = label;
    disp.style.color = value ? '#212529' : '#6c757d';

    document.querySelector(`.nplcb-panel-${idx}`).style.display = 'none';
    const search = document.querySelector(`.nplcb-search-${idx}`);
    if (search) { search.value = ''; searchNewPartLinkCombo(idx); }
}

function closeAllNewPartLinkCombos() {
    document.querySelectorAll('.part-row').forEach(row => {
        const idx = row.id.replace('row-', '');
        const p   = document.querySelector(`.nplcb-panel-${idx}`);
        if (p) p.style.display = 'none';
    });
}

function closeAllCombos() {
    closeAllPOCombos();
    closeAllPartCombos();
    closeAllNewPartLinkCombos();
}

/* ── PO COMBOBOX FUNCTIONS ── */
function togglePOCombo(idx) {
    const panel  = document.querySelector(`.pocb-panel-${idx}`);
    const isOpen = panel.style.display !== 'none';
    closeAllCombos();
    if (!isOpen) {
        panel.style.display = '';
        document.querySelector(`.pocb-search-${idx}`)?.focus();
    }
}

function searchPOCombo(idx) {
    const term = document.querySelector(`.pocb-search-${idx}`).value.toLowerCase();
    document.querySelectorAll(`#pocb-${idx} .cb-option`).forEach(opt => {
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
    row.querySelector('.cost-input').value = parseFloat(cost).toFixed(2);

    document.querySelector(`.pocb-panel-${idx}`).style.display = 'none';
    document.querySelector(`.pocb-search-${idx}`).value = '';
    searchPOCombo(idx);

    calcRow(idx);
    refreshDropdowns();
}

function closeAllPOCombos() {
    document.querySelectorAll('.item-row').forEach(row => {
        const idx = row.id.replace('row-', '');
        const p   = document.querySelector(`.pocb-panel-${idx}`);
        if (p) p.style.display = 'none';
    });
}

function calcRow(idx) {

    const row = document.getElementById(`row-${idx}`);
    if (!row) return;

    const qty     = parseFloat(row.querySelector('.qty-input').value) || 0;
    const cost    = parseFloat(row.querySelector('.cost-input').value) || 0;
    const discInput    = row.querySelector('.disc-input');
    const discAmtInput = row.querySelector('.discount-amount-input');

    let discPct = parseFloat(discInput.value) || 0;
    let discAmt = parseFloat(discAmtInput.value) || 0;

    // 🔥 Prevent using both discount types at same time
    if (discPct > 0 && discAmt > 0) {
        if (document.activeElement === discInput) {
            discAmtInput.value = '';
            discAmt = 0;
        } else if (document.activeElement === discAmtInput) {
            discInput.value = '';
            discPct = 0;
        }
    }

    let netCost = cost;

    // Apply percentage first
    netCost = netCost * (1 - discPct / 100);

    // Apply fixed discount distributed per quantity
    if (qty > 0 && discAmt > 0) {
        netCost -= (discAmt / qty);
    }

    if (netCost < 0) netCost = 0;

    const total = qty * netCost;

    document.getElementById(`net-${idx}`).value = netCost.toFixed(2);
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
    updateBalancePreview();
}

function removeRow(idx) {
    document.getElementById(`row-${idx}`)?.remove();
    if (!document.querySelector('.item-row')) {
        document.getElementById('itemsTableBody').innerHTML = `
            <tr id="emptyState">
                <td colspan="9" class="text-center text-muted py-4">
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
    if (!e.target.closest('.combobox')) closeAllCombos();
});

/* ── Payment type toggle ── */
document.getElementById('paymentType').addEventListener('change', function () {
    const is45 = this.value === '45days';
    document.getElementById('downpaymentSection').style.display = is45 ? '' : 'none';
    document.getElementById('deadlinePreview').style.display    = is45 ? '' : 'none';
    updateDeadline();
    updateBalancePreview();
});

document.getElementById('orderDate').addEventListener('change', updateDeadline);

function updateDeadline() {
    const orderDate   = document.getElementById('orderDate').value;
    const paymentType = document.getElementById('paymentType').value;
    if (!orderDate || paymentType !== '45days') return;
    const due = new Date(orderDate);
    due.setDate(due.getDate() + 45);
    document.getElementById('deadlineDate').textContent = due.toLocaleDateString('en-PH', { year:'numeric', month:'long', day:'numeric' });
}

function updateBalancePreview() {
    const is45      = document.getElementById('paymentType').value === '45days';
    const previewEl = document.getElementById('balancePreview');
    if (!is45) { previewEl.style.display = 'none'; return; }
    const totalText = document.getElementById('grandTotal').textContent.replace(/,/g, '');
    const total = parseFloat(totalText) || 0;
    const down  = parseFloat(document.getElementById('downpaymentAmount').value) || 0;
    const bal   = Math.max(0, total - down);
    document.getElementById('previewTotal').textContent   = formatMoney(total);
    document.getElementById('previewDown').textContent    = formatMoney(down);
    document.getElementById('previewBalance').textContent = formatMoney(bal);
    previewEl.style.display = '';
}

document.getElementById('downpaymentAmount').addEventListener('input', updateBalancePreview);

/* ── Submit guard ── */
document.getElementById('poForm').addEventListener('submit', function (e) {
    if (!document.querySelector('.item-row')) {
        e.preventDefault(); alert('Please add at least one product.'); return;
    }
    if (!document.getElementById('paymentType').value) {
        e.preventDefault(); alert('Please select a payment type.'); return;
    }

    let invalidPart = false;
    document.querySelectorAll('.part-row').forEach(row => {
        const idx     = row.id.replace('row-', '');
        const partId  = row.querySelector('.part-id-input')?.value;
        const newName = row.querySelector(`input[name="items[${idx}][new_part_name]"]`)?.value.trim();
        if (!partId && !newName) invalidPart = true;
    });
    if (invalidPart) {
        e.preventDefault(); alert('For each part row, select an existing part or enter a name for a new part.'); return;
    }
});

/* ── Restore item rows after a validation error ── */
@if(old('items'))
(function () {
    const oldItems   = @json(array_values(old('items', [])));
    const productMap = {};
    products.forEach(p => productMap[p.id] = p);

    oldItems.forEach(it => {
        if (!it) return;

        if (it.item_type === 'part') {
            addPartRow({
                part_id:             it.part_id || '',
                new_part_name:       it.new_part_name || '',
                new_part_product_id: it.new_part_product_id || '',
                quantity:            parseInt(it.quantity) || 1,
                unit_cost:           it.unit_cost ?? '',
                discount_percent:    it.discount_percent ?? 0,
                discount_amount:     it.discount_amount ?? 0,
            });
            return;
        }

        if (!it.product_id) return;
        const p = productMap[it.product_id] || {};
        addItem({
            product_id:       it.product_id,
            label:            p.label || '',
            unit_type:        p.unit_type || '',
            quantity:         parseInt(it.quantity) || 1,
            unit_cost:        it.unit_cost ?? '',
            discount_percent: it.discount_percent ?? 0,
            discount_amount:  it.discount_amount ?? 0,
        });
    });
})();
@endif

if (document.getElementById('paymentType').value === '45days') {
    document.getElementById('downpaymentSection').style.display = '';
    document.getElementById('deadlinePreview').style.display    = '';
    updateDeadline();
    updateBalancePreview();
}
</script>
@endpush
@endsection