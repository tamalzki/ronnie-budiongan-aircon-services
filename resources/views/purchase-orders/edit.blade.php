@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-pencil text-warning"></i> Edit Purchase Order</h2>
            <p class="text-muted mb-0">{{ $purchaseOrder->po_number }}</p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Orders
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

                {{-- Supplier & Dates --}}
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

                {{-- Items --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-box-seam"></i> Order Items</h6>
                        <button type="button" class="btn btn-success btn-sm" onclick="addItem()">
                            <i class="bi bi-plus-circle"></i> Add Product
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <div id="itemsContainer" class="p-3">
                            <div class="text-center text-muted py-4" id="emptyState">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No products added yet. Click "Add Product" above.</p>
                            </div>
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
                                <option value="full"   {{ old('payment_type', $purchaseOrder->payment_type) == 'full'   ? 'selected' : '' }}>Full Payment</option>
                                <option value="45days" {{ old('payment_type', $purchaseOrder->payment_type) == '45days' ? 'selected' : '' }}>45-Day Term</option>
                            </select>
                            <small class="text-muted">45-Day: balance due in 45 days</small>
                        </div>

                        {{-- 45-day deadline --}}
                        <div id="deadlinePreview" style="display:none;" class="mb-3">
                            <div class="alert alert-warning py-2 mb-0" style="font-size:0.85rem;">
                                <div class="fw-semibold mb-1"><i class="bi bi-calendar-event"></i> Payment Due:</div>
                                <div class="fw-bold text-dark" id="deadlineDate">—</div>
                                <small class="text-muted">(45 days from order date)</small>
                            </div>
                        </div>

                        {{-- Downpayment (45days only) --}}
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
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
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

                            {{-- Balance preview --}}
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

                {{-- Submit --}}
                <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm">
                    <i class="bi bi-check-circle"></i> Update Purchase Order
                </button>

            </div>

        </div>
    </form>

</div>

@push('scripts')
<script>
const products = {!! json_encode($productsJson) !!};
let rowIndex = 0;

function addItem() {
    // Hide empty state
    document.getElementById('emptyState')?.remove();
    
    rowIndex++;
    const container = document.getElementById('itemsContainer');
    
    // Combobox options
    const cbOpts = products.map(p => {
        const costStr = p.cost > 0 ? ` — ₱${parseFloat(p.cost).toFixed(2)}` : ' — No cost';
        return `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                     data-value="${p.id}" data-cost="${p.cost}" data-label="${p.label}"
                     onmouseenter="this.style.background='#f0f4ff'"
                     onmouseleave="this.style.background=''"
                     onclick="pickPOCombo(${rowIndex}, '${p.id}', '${p.cost}', this.getAttribute('data-label'))">
                  ${p.label}${costStr}
                </div>`;
    }).join('');

    const html = `
        <div class="border rounded p-2 mb-2 item-row bg-light" id="row-${rowIndex}">
            <div class="row g-2 align-items-end">
                {{-- Hidden select for form submission --}}
                <select name="items[${rowIndex}][product_id]" class="product-select d-none" data-row="${rowIndex}" required>
                    <option value="">-- Select --</option>
                    ${products.map(p => `<option value="${p.id}" data-cost="${p.cost}">${p.label}</option>`).join('')}
                </select>

                <div class="col-md-12 mb-1">
                    <label class="form-label small fw-semibold mb-1">Product <span class="text-danger">*</span></label>
                    <div class="combobox position-relative" id="pocb-${rowIndex}">
                        <div class="form-control form-control-sm d-flex justify-content-between align-items-center"
                             style="cursor:pointer;user-select:none;background:#fff;"
                             onclick="togglePOCombo(${rowIndex})">
                            <span class="pocb-display-${rowIndex} text-muted" style="font-size:0.82rem;">-- Select Product --</span>
                            <i class="bi bi-chevron-down" style="font-size:0.7rem;color:#888;"></i>
                        </div>
                        <div class="pocb-panel-${rowIndex} position-absolute w-100 bg-white border rounded shadow-sm"
                             style="display:none;z-index:9999;top:100%;left:0;">
                            <div class="p-2 border-bottom">
                                <input type="text" class="form-control form-control-sm pocb-search-${rowIndex}"
                                       placeholder="🔍 Search product…"
                                       oninput="searchPOCombo(${rowIndex})"
                                       onclick="event.stopPropagation()">
                            </div>
                            <div class="pocb-list-${rowIndex}" style="max-height:200px;overflow-y:auto;">
                                ${cbOpts}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Qty</label>
                    <input type="number" class="form-control form-control-sm qty-input" name="items[${rowIndex}][quantity]"
                           value="1" min="1" required onchange="calcRow(${rowIndex})">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label small fw-semibold mb-1">Unit Cost</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">₱</span>
                        <input type="number" step="0.01" class="form-control cost-input" name="items[${rowIndex}][unit_cost]"
                               value="" min="0" required onchange="calcRow(${rowIndex})">
                    </div>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Disc %</label>
                    <input type="number" step="0.01" class="form-control form-control-sm disc-input" 
                           name="items[${rowIndex}][discount_percent]" value="0" min="0" max="100" onchange="calcRow(${rowIndex})">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label small fw-semibold mb-1">Net Cost</label>
                    <input type="text" class="form-control form-control-sm net-cost-display" id="net-${rowIndex}" readonly value="0.00">
                </div>
                
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="removeRow(${rowIndex})">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
            </div>
            <div class="text-end mt-1 px-1">
                <small class="text-muted">Row Total:</small>
                <span class="fw-bold text-primary ms-1">₱<span class="total-display" id="total-${rowIndex}">0.00</span></span>
            </div>
        </div>
    `;

    container.insertAdjacentHTML('beforeend', html);
    refreshDropdowns();
}

/* ── PO COMBOBOX FUNCTIONS ── */
function togglePOCombo(idx) {
    const panel = document.querySelector(`.pocb-panel-${idx}`);
    const isOpen = panel.style.display !== 'none';
    closeAllPOCombos();
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

function pickPOCombo(idx, value, cost, label) {
    // Update hidden select
    const sel = document.querySelector(`select[name="items[${idx}][product_id]"]`);
    sel.value = value;

    // Update display
    document.querySelector(`.pocb-display-${idx}`).textContent = label;
    document.querySelector(`.pocb-display-${idx}`).style.color = '#212529';

    // Set cost
    const row = document.getElementById(`row-${idx}`);
    row.querySelector('.cost-input').value = parseFloat(cost).toFixed(2);

    // Close panel + reset search
    document.querySelector(`.pocb-panel-${idx}`).style.display = 'none';
    document.querySelector(`.pocb-search-${idx}`).value = '';
    searchPOCombo(idx);

    calcRow(idx);
    refreshDropdowns();
}

function closeAllPOCombos() {
    document.querySelectorAll('.item-row').forEach(row => {
        const idx = row.id.replace('row-', '');
        const p = document.querySelector(`.pocb-panel-${idx}`);
        if (p) p.style.display = 'none';
    });
}

function onProductChange(select) {
    // kept for compatibility — combobox handles it now
}

function calcRow(idx) {
    const row = document.getElementById(`row-${idx}`);
    if (!row) return;

    const qty     = parseFloat(row.querySelector('.qty-input').value)  || 0;
    const cost    = parseFloat(row.querySelector('.cost-input').value) || 0;
    const disc    = parseFloat(row.querySelector('.disc-input').value) || 0;
    const netCost = cost * (1 - disc / 100);
    const total   = qty * netCost;

    row.querySelector(`#net-${idx}`).value = netCost.toFixed(2);
    row.querySelector(`#total-${idx}`).textContent = total.toFixed(2);

    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.total-display').forEach(el => {
        grand += parseFloat(el.textContent) || 0;
    });
    document.getElementById('grandTotal').textContent = grand.toFixed(2);
    updateBalancePreview();
}

function removeRow(idx) {
    const row = document.getElementById(`row-${idx}`);
    if (row) row.remove();
    
    // Show empty state if no items
    if (document.querySelectorAll('.item-row').length === 0) {
        document.getElementById('itemsContainer').innerHTML = `
            <div class="text-center text-muted py-4" id="emptyState">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <p class="mb-0">No products added yet. Click "Add Product" above.</p>
            </div>
        `;
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
            opt.style.opacity       = taken ? '0.3' : '1';
            opt.style.textDecoration = taken ? 'line-through' : '';
            opt.style.pointerEvents  = taken ? 'none' : '';
        });
    });
}

// Close PO combos on outside click
document.addEventListener('click', function(e) {
    if (!e.target.closest('.combobox')) closeAllPOCombos();
});

// Payment type toggle
document.getElementById('paymentType').addEventListener('change', function () {
    const is45 = this.value === '45days';
    document.getElementById('downpaymentSection').style.display = is45 ? '' : 'none';
    document.getElementById('deadlinePreview').style.display    = is45 ? '' : 'none';
    updateDeadline();
    updateBalancePreview();
});

// Deadline from order date
document.getElementById('orderDate').addEventListener('change', updateDeadline);

function updateDeadline() {
    const orderDate = document.getElementById('orderDate').value;
    const paymentType = document.getElementById('paymentType').value;
    
    if (!orderDate || paymentType !== '45days') return;

    const due = new Date(orderDate);
    due.setDate(due.getDate() + 45);

    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('deadlineDate').textContent = due.toLocaleDateString('en-PH', options);
}

function updateBalancePreview() {
    const is45 = document.getElementById('paymentType').value === '45days';
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

// Submit guard
document.getElementById('poForm').addEventListener('submit', function (e) {
    const rows = document.querySelectorAll('.item-row').length;
    if (rows === 0) {
        e.preventDefault();
        alert('Please add at least one product.');
        return false;
    }
    
    const paymentType = document.getElementById('paymentType').value;
    if (!paymentType) {
        e.preventDefault();
        alert('Please select a payment type.');
        return false;
    }
});

// Pre-fill existing items from PO
const existingItems = {!! json_encode($purchaseOrder->items->map(fn($i) => [
    'product_id'   => $i->product_id,
    'quantity'     => $i->quantity_ordered,
    'unit_cost'    => $i->unit_cost,
    'discount'     => $i->discount_percent ?? 0,
    'label'        => optional(optional($i->product)->brand)->name . ' · ' . optional($i->product)->model,
])) !!};

existingItems.forEach(item => {
    addItem();
    const idx  = rowIndex;
    const row  = document.getElementById(`row-${idx}`);
    const sel  = row.querySelector('.product-select');

    // Set hidden select
    sel.value = item.product_id;

    // Update combobox display
    const disp = document.querySelector(`.pocb-display-${idx}`);
    if (disp) { disp.textContent = item.label; disp.style.color = '#212529'; }

    row.querySelector('.qty-input').value  = item.quantity;
    row.querySelector('.cost-input').value = parseFloat(item.unit_cost).toFixed(2);
    row.querySelector('.disc-input').value = item.discount;
    calcRow(idx);
    refreshDropdowns();
});

if (document.getElementById('paymentType').value === '45days') {
    document.getElementById('downpaymentSection').style.display = '';
    document.getElementById('deadlinePreview').style.display = '';
    updateDeadline();
}
</script>
@endpush
@endsection