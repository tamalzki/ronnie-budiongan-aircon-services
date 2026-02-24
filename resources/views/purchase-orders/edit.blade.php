@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-pencil text-warning"></i> Edit Purchase Order</h2>
            <p class="text-muted mb-0">{{ $purchaseOrder->po_number }}</p>
        </div>
        <a href="{{ route('purchase-orders.show', $purchaseOrder) }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('purchase-orders.update', $purchaseOrder) }}" method="POST" id="poForm">
        @csrf
        @method('PUT')

        <div class="row g-3">

            {{-- Left Column --}}
            <div class="col-md-8">

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-building"></i> Supplier & Order Details</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
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
                                <label class="form-label small fw-semibold">Order Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="order_date"
                                       value="{{ old('order_date', $purchaseOrder->order_date->format('Y-m-d')) }}" required id="orderDate">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Expected Delivery</label>
                                <input type="date" class="form-control form-control-sm" name="expected_delivery_date"
                                       value="{{ old('expected_delivery_date', optional($purchaseOrder->expected_delivery_date)->format('Y-m-d')) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Order Items</h6>
                        <button type="button" class="btn btn-success btn-sm" onclick="addItem()">
                            <i class="bi bi-plus-circle"></i> Add Product
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="itemsContainer" class="p-3">
                            <div class="text-center text-muted py-4" id="emptyState" style="display:none;">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No products added yet.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-sticky"></i> Additional Notes</h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control form-control-sm" name="notes" rows="2">{{ old('notes', $purchaseOrder->notes) }}</textarea>
                    </div>
                </div>

            </div>

            {{-- Right Column --}}
            <div class="col-md-4">

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

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-credit-card"></i> Payment Terms</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Payment Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="payment_type" id="paymentType" required>
                                <option value="">-- Select Payment Type --</option>
                                <option value="full"   {{ old('payment_type', $purchaseOrder->payment_type) == 'full'   ? 'selected' : '' }}>Full Payment</option>
                                <option value="45days" {{ old('payment_type', $purchaseOrder->payment_type) == '45days' ? 'selected' : '' }}>45-Day Term</option>
                            </select>
                        </div>

                        <div id="deadlinePreview" style="display:none;" class="mb-3">
                            <label class="form-label small fw-semibold"><i class="bi bi-calendar-event"></i> Payment Due Date</label>
                            <input type="date" class="form-control form-control-sm" name="payment_due_date" id="paymentDueDate"
                                   value="{{ old('payment_due_date', optional($purchaseOrder->payment_due_date)->format('Y-m-d')) }}">
                            <small class="text-muted">Auto-calculated, but you can override</small>
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
                                    <span>Total:</span><span class="fw-semibold">₱<span id="previewTotal">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Downpayment:</span><span class="fw-semibold text-success">₱<span id="previewDown">0.00</span></span>
                                </div>
                                <div class="d-flex justify-content-between border-top pt-1 mt-1">
                                    <span class="fw-bold">Balance Due:</span><span class="fw-bold text-danger">₱<span id="previewBalance">0.00</span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                    <i class="bi bi-check-circle"></i> Update Purchase Order
                </button>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const products       = {!! json_encode($productsJson) !!};
const existingSerials = {!! json_encode($existingSerials) !!}; // keyed by product_id
let rowIndex = 0;

function unitTypeBadge(unitType) {
    if (!unitType) return '';
    const isIndoor = unitType === 'indoor';
    const color    = isIndoor ? '#0d6efd' : '#198754';
    const icon     = isIndoor ? '❄️' : '🌀';
    return `<span style="font-size:0.7rem;padding:1px 6px;border-radius:20px;background:${color}15;color:${color};border:1px solid ${color}40;font-weight:600;white-space:nowrap;">${icon} ${isIndoor ? 'Indoor' : 'Outdoor'}</span>`;
}

function addItem(prefill) {
    document.getElementById('emptyState').style.display = 'none';
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
    <div class="border rounded mb-3 item-row bg-white shadow-sm" id="row-${idx}">
        <div class="p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge bg-secondary">Item #${idx}</span>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeRow(${idx})" style="padding:1px 8px;font-size:0.78rem;">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>

            <select name="items[${idx}][product_id]" class="product-select d-none" data-row="${idx}" required>
                <option value="">-- Select --</option>
                ${products.map(p => `<option value="${p.id}" data-cost="${p.cost}">${p.label}</option>`).join('')}
            </select>

            <div class="mb-2">
                <label class="form-label small fw-semibold mb-1">Product <span class="text-danger">*</span></label>
                <div class="combobox position-relative" id="pocb-${idx}">
                    <div class="form-control form-control-sm d-flex justify-content-between align-items-center gap-2"
                         style="cursor:pointer;user-select:none;background:#fff;"
                         onclick="togglePOCombo(${idx})">
                        <div class="d-flex align-items-center gap-2 flex-wrap" style="flex:1;min-width:0;">
                            <span class="pocb-display-${idx} text-muted" style="font-size:0.82rem;">-- Select Product --</span>
                            <span class="pocb-badge-${idx}"></span>
                        </div>
                        <i class="bi bi-chevron-down flex-shrink-0" style="font-size:0.7rem;color:#888;"></i>
                    </div>
                    <div class="pocb-panel-${idx} position-absolute w-100 bg-white border rounded shadow-sm"
                         style="display:none;z-index:9999;top:100%;left:0;">
                        <div class="p-2 border-bottom">
                            <input type="text" class="form-control form-control-sm pocb-search-${idx}"
                                   placeholder="🔍 Search product…"
                                   oninput="searchPOCombo(${idx})"
                                   onclick="event.stopPropagation()">
                        </div>
                        <div class="pocb-list-${idx}" style="max-height:220px;overflow-y:auto;">${cbOpts}</div>
                    </div>
                </div>
            </div>

            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Qty <span class="text-danger">*</span></label>
                    <input type="number" class="form-control form-control-sm qty-input" name="items[${idx}][quantity]"
                           value="1" min="1" required onchange="onQtyChange(${idx})">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Unit Cost</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">₱</span>
                        <input type="number" step="0.01" class="form-control cost-input" name="items[${idx}][unit_cost]"
                               value="" min="0" required onchange="calcRow(${idx})">
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Disc %</label>
                    <input type="number" step="0.01" class="form-control form-control-sm disc-input"
                           name="items[${idx}][discount_percent]" value="0" min="0" max="100" onchange="calcRow(${idx})">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Net Cost</label>
                    <input type="text" class="form-control form-control-sm" id="net-${idx}" readonly value="0.00" style="background:#f8f9fa;">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Row Total</label>
                    <div class="bg-primary bg-opacity-10 rounded text-center fw-bold text-primary px-2 py-1" style="font-size:0.85rem;height:31px;line-height:1.8;">
                        ₱<span id="total-${idx}" class="total-display">0.00</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Serial Numbers --}}
        <div class="border-top bg-light px-3 py-2" id="serials-section-${idx}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <i class="bi bi-upc-scan text-primary"></i>
                    <span class="small fw-semibold text-primary ms-1">Serial Numbers</span>
                    <span class="text-muted small ms-1">(optional — required when receiving)</span>
                </div>
                <span class="badge bg-secondary" id="serial-count-${idx}">0 / 0</span>
            </div>
            <div id="serials-inputs-${idx}" class="row g-1"></div>
        </div>
    </div>`;

    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
    rebuildSerialInputs(idx, 1, []);
    refreshDropdowns();

    if (prefill) {
        const sel = document.querySelector(`select[name="items[${idx}][product_id]"]`);
        sel.value = prefill.product_id;

        const disp = document.querySelector(`.pocb-display-${idx}`);
        if (disp) { disp.textContent = prefill.label; disp.style.color = '#212529'; }

        const badge = document.querySelector(`.pocb-badge-${idx}`);
        if (badge && prefill.unit_type) badge.innerHTML = unitTypeBadge(prefill.unit_type);

        const row = document.getElementById(`row-${idx}`);
        row.querySelector('.qty-input').value  = prefill.quantity;
        row.querySelector('.cost-input').value = parseFloat(prefill.unit_cost).toFixed(2);
        row.querySelector('.disc-input').value = prefill.discount ?? 0;

        // Load existing serials for this product from the controller-provided map
        const productSerials = existingSerials[prefill.product_id] || [];
        rebuildSerialInputs(idx, prefill.quantity, productSerials);
        calcRow(idx);
        refreshDropdowns();
    }
}

function rebuildSerialInputs(idx, qty, existingValues) {
    const container = document.getElementById(`serials-inputs-${idx}`);
    container.innerHTML = '';
    for (let i = 0; i < qty; i++) {
        const val = existingValues[i] || '';
        container.insertAdjacentHTML('beforeend', `
            <div class="col-md-4 col-sm-6">
                <div class="input-group input-group-sm mb-1">
                    <span class="input-group-text text-muted" style="font-size:0.72rem;min-width:36px;">#${i+1}</span>
                    <input type="text"
                           class="form-control form-control-sm serial-input"
                           name="items[${idx}][serials][]"
                           value="${val}"
                           placeholder="Serial #${i+1}"
                           style="font-family:monospace;font-size:0.82rem;"
                           oninput="updateSerialCount(${idx})">
                </div>
            </div>`);
    }
    updateSerialCount(idx);
}

function updateSerialCount(idx) {
    const inputs  = document.querySelectorAll(`#serials-inputs-${idx} .serial-input`);
    const filled  = [...inputs].filter(i => i.value.trim() !== '').length;
    const total   = inputs.length;
    const counter = document.getElementById(`serial-count-${idx}`);
    counter.textContent = `${filled} / ${total}`;
    counter.className   = filled === total && total > 0
        ? 'badge bg-success'
        : filled > 0 ? 'badge bg-warning text-dark' : 'badge bg-secondary';
}

function onQtyChange(idx) {
    const row    = document.getElementById(`row-${idx}`);
    const qty    = parseInt(row.querySelector('.qty-input').value) || 0;
    const existing = [...document.querySelectorAll(`#serials-inputs-${idx} .serial-input`)].map(i => i.value);
    rebuildSerialInputs(idx, qty, existing);
    calcRow(idx);
}

function togglePOCombo(idx) {
    const panel  = document.querySelector(`.pocb-panel-${idx}`);
    const isOpen = panel.style.display !== 'none';
    closeAllPOCombos();
    if (!isOpen) { panel.style.display = ''; document.querySelector(`.pocb-search-${idx}`)?.focus(); }
}

function searchPOCombo(idx) {
    const term = document.querySelector(`.pocb-search-${idx}`).value.toLowerCase();
    document.querySelectorAll(`#pocb-${idx} .cb-option`).forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function pickPOCombo(idx, value, cost, label, unitType) {
    document.querySelector(`select[name="items[${idx}][product_id]"]`).value = value;
    const disp = document.querySelector(`.pocb-display-${idx}`);
    disp.textContent = label; disp.style.color = '#212529';
    document.querySelector(`.pocb-badge-${idx}`).innerHTML = unitType ? unitTypeBadge(unitType) : '';
    document.getElementById(`row-${idx}`).querySelector('.cost-input').value = parseFloat(cost).toFixed(2);
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
    const qty     = parseFloat(row.querySelector('.qty-input').value)  || 0;
    const cost    = parseFloat(row.querySelector('.cost-input').value) || 0;
    const disc    = parseFloat(row.querySelector('.disc-input').value) || 0;
    const netCost = cost * (1 - disc / 100);
    document.getElementById(`net-${idx}`).value = netCost.toFixed(2);
    document.getElementById(`total-${idx}`).textContent = (qty * netCost).toFixed(2);
    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.total-display').forEach(el => grand += parseFloat(el.textContent) || 0);
    document.getElementById('grandTotal').textContent = grand.toFixed(2);
    updateBalancePreview();
}

function removeRow(idx) {
    document.getElementById(`row-${idx}`)?.remove();
    if (!document.querySelector('.item-row')) {
        document.getElementById('emptyState').style.display = '';
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

document.addEventListener('click', e => { if (!e.target.closest('.combobox')) closeAllPOCombos(); });

document.getElementById('paymentType').addEventListener('change', function () {
    const is45 = this.value === '45days';
    document.getElementById('downpaymentSection').style.display = is45 ? '' : 'none';
    document.getElementById('deadlinePreview').style.display    = is45 ? '' : 'none';
    updateDeadline(); updateBalancePreview();
});

document.getElementById('orderDate').addEventListener('change', updateDeadline);

function updateDeadline() {
    const orderDate   = document.getElementById('orderDate').value;
    const paymentType = document.getElementById('paymentType').value;
    const dueDateInput = document.getElementById('paymentDueDate');
    if (!orderDate || paymentType !== '45days' || !dueDateInput) return;
    if (!dueDateInput.value) {
        const due = new Date(orderDate);
        due.setDate(due.getDate() + 45);
        dueDateInput.value = due.toISOString().split('T')[0];
    }
}

function updateBalancePreview() {
    const is45      = document.getElementById('paymentType').value === '45days';
    const previewEl = document.getElementById('balancePreview');
    if (!is45) { previewEl.style.display = 'none'; return; }
    const total = parseFloat(document.getElementById('grandTotal').textContent) || 0;
    const down  = parseFloat(document.getElementById('downpaymentAmount').value) || 0;
    const bal   = Math.max(0, total - down);
    document.getElementById('previewTotal').textContent   = total.toFixed(2);
    document.getElementById('previewDown').textContent    = down.toFixed(2);
    document.getElementById('previewBalance').textContent = bal.toFixed(2);
    previewEl.style.display = '';
}

document.getElementById('downpaymentAmount').addEventListener('input', updateBalancePreview);

document.getElementById('poForm').addEventListener('submit', function (e) {
    if (!document.querySelector('.item-row')) {
        e.preventDefault(); alert('Please add at least one product.'); return;
    }
    let valid = true;
    document.querySelectorAll('.item-row').forEach(row => {
        const idx    = row.id.replace('row-', '');
        const inputs = row.querySelectorAll('.serial-input');
        const filled = [...inputs].filter(i => i.value.trim() !== '').length;
        if (filled > 0 && filled !== inputs.length) {
            valid = false;
            document.getElementById(`serial-count-${idx}`).className = 'badge bg-danger';
        }
    });
    if (!valid) {
        e.preventDefault();
        alert('Serial number count must match quantity for all items, or leave all blank.');
    }
});

// ── Pre-fill existing items ──
const existingItems = {!! json_encode($purchaseOrder->items->map(fn($i) => [
    'product_id' => $i->product_id,
    'quantity'   => $i->quantity_ordered,
    'unit_cost'  => $i->unit_cost,
    'discount'   => $i->discount_percent ?? 0,
    'label'      => trim((optional(optional($i->product)->brand)->name ?? '') . ' · ' . (optional($i->product)->model ?? '')),
    'unit_type'  => optional($i->product)->unit_type,
])) !!};

existingItems.forEach(item => addItem(item));

if (document.getElementById('paymentType').value === '45days') {
    document.getElementById('downpaymentSection').style.display = '';
    document.getElementById('deadlinePreview').style.display    = '';
    updateDeadline();
}
updateBalancePreview();
</script>
@endpush
@endsection