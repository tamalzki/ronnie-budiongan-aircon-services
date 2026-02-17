@extends('layouts.app')

@section('title', 'Create Sale')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-cart-plus text-primary"></i> Create New Sale</h2>
            <p class="text-muted mb-0">Add products/services and generate invoice</p>
        </div>
        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back to Sales
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
        @csrf

        <div class="row g-3">

            {{-- LEFT COLUMN --}}
            <div class="col-md-8">

                {{-- Customer --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-person"></i> Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Customer Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm @error('customer_name') is-invalid @enderror"
                                       name="customer_name" value="{{ old('customer_name') }}" required>
                                @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Contact Number</label>
                                <input type="text" class="form-control form-control-sm"
                                       name="customer_contact" value="{{ old('customer_contact') }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Sale Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm"
                                       name="sale_date" value="{{ old('sale_date', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Address</label>
                                <textarea class="form-control form-control-sm" name="customer_address" rows="2">{{ old('customer_address') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sale Items --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="bi bi-cart"></i> Sale Items</h6>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-success btn-sm" onclick="addItem('product')">
                                <i class="bi bi-plus-circle"></i> Add Product
                            </button>
                            <button type="button" class="btn btn-info btn-sm" onclick="addItem('service')">
                                <i class="bi bi-plus-circle"></i> Add Service
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div id="itemsContainer">
                            <div class="text-center text-muted py-4" id="emptyState">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                <p class="mb-0">No items yet. Click "Add Product" or "Add Service".</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-sticky"></i> Notes</h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control form-control-sm" name="notes" rows="2"
                                  placeholder="Optional notes (warranty, instructions…)">{{ old('notes') }}</textarea>
                    </div>
                </div>

            </div>

            {{-- RIGHT COLUMN --}}
            <div class="col-md-4">

                {{-- Order Summary sticky --}}
                <div class="card border-0 shadow-sm mb-3 sticky-top" style="top:16px;z-index:100;">
                    <div class="card-header bg-primary text-white border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-calculator"></i> Order Summary</h6>
                    </div>
                    <div class="card-body pb-2" style="font-size:0.88rem;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-semibold">₱<span id="subtotalDisplay">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Discount</span>
                            <span class="text-danger fw-semibold">- ₱<span id="discountDisplay">0.00</span></span>
                        </div>
                        <div class="d-flex justify-content-between border-top pt-2 mt-1">
                            <span class="fw-bold">TOTAL</span>
                            <span class="fw-bold text-primary" style="font-size:1.2rem;">₱<span id="totalDisplay">0.00</span></span>
                        </div>
                        {{-- Installment breakdown --}}
                        <div id="installmentSummary" style="display:none;" class="border-top mt-2 pt-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Down Payment <small>(Month #1)</small></span>
                                <span class="text-success fw-semibold" id="summaryDown">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Remaining Balance</span>
                                <span class="fw-semibold" id="summaryBalance">₱0.00</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Monthly Payment</span>
                                <span class="fw-bold text-primary" id="summaryMonthly">₱0.00</span>
                            </div>
                            <div class="mt-1 text-muted" id="summaryNote" style="font-size:0.75rem;"></div>
                        </div>
                    </div>
                </div>

                {{-- Payment Details --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-light border-0 py-2">
                        <h6 class="mb-0"><i class="bi bi-credit-card"></i> Payment Details</h6>
                    </div>
                    <div class="card-body">

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Payment Type <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('payment_type') is-invalid @enderror"
                                    id="payment_type" name="payment_type" required>
                                <option value="">-- Select Type --</option>
                                <option value="cash"        {{ old('payment_type') == 'cash'        ? 'selected' : '' }}>Cash (Full Payment)</option>
                                <option value="installment" {{ old('payment_type') == 'installment' ? 'selected' : '' }}>Installment Plan</option>
                            </select>
                            @error('payment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('payment_method') is-invalid @enderror"
                                    id="payment_method" name="payment_method" required>
                                <option value="">-- Select Method --</option>
                                <option value="cash"          {{ old('payment_method') == 'cash'          ? 'selected' : '' }}>💵 Cash</option>
                                <option value="gcash"         {{ old('payment_method') == 'gcash'         ? 'selected' : '' }}>📱 GCash</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                                <option value="cheque"        {{ old('payment_method') == 'cheque'        ? 'selected' : '' }}>🧾 Cheque</option>
                            </select>
                            @error('payment_method')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Discount (₱)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control"
                                       id="discount" name="discount"
                                       value="{{ old('discount', 0) }}" oninput="calculateTotals()">
                            </div>
                        </div>

                        {{-- Installment Options --}}
                        <div id="installmentOptions" style="display:none;" class="border-top pt-2 mt-2">
                            <p class="small fw-semibold text-muted mb-2">Installment Settings</p>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Number of Months <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" id="installment_months" name="installment_months">
                                    @foreach([3,6,9,12,18,24] as $m)
                                    <option value="{{ $m }}" {{ old('installment_months', 12) == $m ? 'selected' : '' }}>{{ $m }} months</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Down Payment</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" min="0" class="form-control"
                                           id="down_payment" name="down_payment"
                                           value="{{ old('down_payment', 0) }}" oninput="calculateTotals()">
                                </div>
                                <small class="text-success"><i class="bi bi-info-circle"></i> Saved as Month #1 (already paid)</small>
                            </div>

                            <div class="mb-1">
                                <label class="form-label small fw-semibold">Down Payment Method</label>
                                <select class="form-select form-select-sm" name="down_payment_method">
                                    <option value="">-- Same as above --</option>
                                    <option value="cash"          {{ old('down_payment_method') == 'cash'          ? 'selected' : '' }}>💵 Cash</option>
                                    <option value="gcash"         {{ old('down_payment_method') == 'gcash'         ? 'selected' : '' }}>📱 GCash</option>
                                    <option value="bank_transfer" {{ old('down_payment_method') == 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                                    <option value="cheque"        {{ old('down_payment_method') == 'cheque'        ? 'selected' : '' }}>🧾 Cheque</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 shadow-sm">
                    <i class="bi bi-check-circle"></i> Create Sale
                </button>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const products = @json($products);
const services = @json($services);
let counter = 0;

/* ── ADD ITEM ── */
function addItem(type) {
    document.getElementById('emptyState')?.remove();
    counter++;
    const id  = counter;
    const arr = type === 'product' ? products : services;

    const opts = arr.map(p => {
        if (type === 'product') {
            const badge = p.stock === 0 ? ' ⚠ Out of Stock' : ` (Stock: ${p.stock})`;
            return `<option value="${p.id}" data-price="${p.price}">${p.label} — ₱${parseFloat(p.price).toFixed(2)}${badge}</option>`;
        }
        return `<option value="${p.id}" data-price="${p.price}">${p.label} — ₱${parseFloat(p.price).toFixed(2)}</option>`;
    }).join('');

    // Hidden real select for form submission (keeps value)
    const hiddenSelect = `<select name="items[${id}][id]" class="item-select d-none" data-id="${id}" required>
        <option value="">-- Select --</option>
        ${opts}
    </select>`;

    const html = `
    <div class="border rounded p-2 mb-2 item-row bg-white shadow-sm" id="item-${id}" data-type="${type}">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-${type==='product'?'success':'info'}">
          <i class="bi bi-${type==='product'?'box':'tools'}"></i>
          ${type==='product'?'Product':'Service'}
        </span>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(${id})"
                style="padding:1px 8px;font-size:0.78rem">
          <i class="bi bi-trash"></i> Remove
        </button>
      </div>
      <input type="hidden" name="items[${id}][type]" value="${type}">
      ${hiddenSelect}
      <div class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label small fw-semibold mb-1">${type==='product'?'Product':'Service'} <span class="text-danger">*</span></label>
          {{-- Combobox --}}
          <div class="combobox position-relative" id="cb-${id}">
            <div class="form-control form-control-sm d-flex justify-content-between align-items-center"
                 style="cursor:pointer;user-select:none;background:#fff;"
                 onclick="toggleCombo(${id})">
              <span class="cb-display-${id} text-muted" style="font-size:0.82rem;">-- Select ${type==='product'?'Product':'Service'} --</span>
              <i class="bi bi-chevron-down" style="font-size:0.7rem;color:#888;"></i>
            </div>
            <div class="cb-panel-${id} position-absolute w-100 bg-white border rounded shadow-sm"
                 style="display:none;z-index:9999;top:100%;left:0;max-height:260px;overflow:hidden;">
              <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm cb-search-${id}"
                       placeholder="🔍 Search…" oninput="searchCombo(${id})"
                       onclick="event.stopPropagation()">
              </div>
              <div class="cb-list-${id}" style="max-height:200px;overflow-y:auto;">
                ${arr.map(p => {
                    const label = type==='product'
                        ? `${p.label} — ₱${parseFloat(p.price).toFixed(2)}${p.stock===0?' ⚠ Out':' ('+p.stock+')'}`
                        : `${p.label} — ₱${parseFloat(p.price).toFixed(2)}`;
                    const price = type==='product' ? p.price : p.price;
                    return `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                                 data-value="${p.id}" data-price="${price}" data-label="${p.label}"
                                 onmouseenter="this.style.background='#f0f4ff'"
                                 onmouseleave="this.style.background=''"
                                 onclick="pickCombo(${id}, '${p.id}', '${price}', this.getAttribute('data-label'))">
                              ${label}
                            </div>`;
                }).join('')}
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Qty</label>
          <input type="number" class="form-control form-control-sm qty-input" min="1" value="1"
                 name="items[${id}][quantity]" required oninput="calculateTotals()">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Price (₱)</label>
          <input type="number" step="0.01" class="form-control form-control-sm price-input"
                 name="items[${id}][price]" id="price-${id}" required readonly
                 style="background:#f8f9fa;">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Line Total</label>
          <div class="bg-light rounded text-center fw-bold text-primary px-1 py-1"
               id="line-${id}" style="font-size:0.82rem;height:31px;line-height:2;">₱0.00</div>
        </div>
      </div>
    </div>`;

    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
    refreshDropdowns();
}

/* ── COMBOBOX: TOGGLE ── */
function toggleCombo(id) {
    const panel = document.querySelector(`.cb-panel-${id}`);
    const isOpen = panel.style.display !== 'none';
    closeAllCombos();
    if (!isOpen) {
        panel.style.display = '';
        document.querySelector(`.cb-search-${id}`)?.focus();
    }
}

/* ── COMBOBOX: SEARCH ── */
function searchCombo(id) {
    const term = document.querySelector(`.cb-search-${id}`).value.toLowerCase();
    document.querySelectorAll(`#cb-${id} .cb-option`).forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

/* ── COMBOBOX: PICK ── */
function pickCombo(id, value, price, label) {
    // Set hidden select value
    const sel = document.querySelector(`select[name="items[${id}][id]"]`);
    sel.value = value;

    // Update display text
    document.querySelector(`.cb-display-${id}`).textContent = label;
    document.querySelector(`.cb-display-${id}`).style.color = '#212529';

    // Set price
    document.getElementById(`price-${id}`).value = parseFloat(price).toFixed(2);

    // Close panel
    document.querySelector(`.cb-panel-${id}`).style.display = 'none';
    document.querySelector(`.cb-search-${id}`).value = '';
    searchCombo(id); // reset filter

    calculateTotals();
    refreshDropdowns();
}

/* ── COMBOBOX: CLOSE ALL ── */
function closeAllCombos() {
    document.querySelectorAll('[class^="cb-panel-"], [class*=" cb-panel-"]').forEach(p => {
        p.style.display = 'none';
    });
    // Use attribute selector pattern instead
    document.querySelectorAll('.item-row').forEach(row => {
        const id = row.id.replace('item-', '');
        const panel = document.querySelector(`.cb-panel-${id}`);
        if (panel) panel.style.display = 'none';
    });
}

/* ── REMOVE ── */
function removeItem(id) {
    document.getElementById(`item-${id}`)?.remove();
    if (!document.querySelector('.item-row')) {
        document.getElementById('itemsContainer').innerHTML =
            `<div class="text-center text-muted py-4" id="emptyState">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
              <p class="mb-0">No items yet. Click "Add Product" or "Add Service".</p>
            </div>`;
    }
    refreshDropdowns();
    calculateTotals();
}

/* ── PREVENT DUPLICATES ── */
function refreshDropdowns() {
    const usedP = new Set(), usedS = new Set();
    document.querySelectorAll('.item-row').forEach(r => {
        const s = r.querySelector('.item-select');
        if (s?.value) (r.dataset.type === 'product' ? usedP : usedS).add(s.value);
    });
    // Gray out already-selected options in all comboboxes
    document.querySelectorAll('.item-row').forEach(r => {
        const s    = r.querySelector('.item-select');
        const used = r.dataset.type === 'product' ? usedP : usedS;
        const cur  = s.value;
        const id   = r.id.replace('item-', '');
        document.querySelectorAll(`#cb-${id} .cb-option`).forEach(opt => {
            const val = opt.getAttribute('data-value');
            const taken = val !== cur && used.has(val);
            opt.style.opacity         = taken ? '0.35' : '1';
            opt.style.textDecoration  = taken ? 'line-through' : '';
            opt.style.pointerEvents   = taken ? 'none' : '';
        });
    });
}

/* ── TOTALS ── */
function calculateTotals() {
    let sub = 0;
    document.querySelectorAll('.item-row').forEach(r => {
        const qty   = parseFloat(r.querySelector('.qty-input').value)   || 0;
        const price = parseFloat(r.querySelector('.price-input').value) || 0;
        const line  = qty * price;
        sub += line;
        const id = r.id.replace('item-', '');
        const el = document.getElementById(`line-${id}`);
        if (el) el.textContent = '₱' + line.toFixed(2);
    });
    const disc  = parseFloat(document.getElementById('discount').value) || 0;
    const total = Math.max(0, sub - disc);
    document.getElementById('subtotalDisplay').textContent = sub.toFixed(2);
    document.getElementById('discountDisplay').textContent = disc.toFixed(2);
    document.getElementById('totalDisplay').textContent    = total.toFixed(2);
    updateInstallmentSummary(total);
}

/* ── INSTALLMENT SUMMARY ── */
function updateInstallmentSummary(total) {
    if (total === undefined)
        total = parseFloat(document.getElementById('totalDisplay').textContent) || 0;
    const months  = parseInt(document.getElementById('installment_months')?.value) || 12;
    const down    = parseFloat(document.getElementById('down_payment')?.value) || 0;
    const balance = Math.max(0, total - down);
    const monthly = months > 0 ? balance / months : 0;

    document.getElementById('summaryDown').textContent    = '₱' + down.toFixed(2);
    document.getElementById('summaryBalance').textContent = '₱' + balance.toFixed(2);
    document.getElementById('summaryMonthly').textContent = '₱' + monthly.toFixed(2);

    const note = down > 0
        ? `Down = Month #1 (paid today). Then ${months} × ₱${monthly.toFixed(2)}/mo.`
        : `${months} equal payments of ₱${monthly.toFixed(2)}/mo.`;
    document.getElementById('summaryNote').textContent = note;
}

/* ── CLOSE COMBOS ON OUTSIDE CLICK ── */
document.addEventListener('click', function(e) {
    if (!e.target.closest('.combobox')) {
        document.querySelectorAll('.item-row').forEach(row => {
            const id = row.id.replace('item-', '');
            const panel = document.querySelector(`.cb-panel-${id}`);
            if (panel) panel.style.display = 'none';
        });
    }
});

/* ── INIT ── */
document.addEventListener('DOMContentLoaded', function () {
    const payType = document.getElementById('payment_type');
    const instOpt = document.getElementById('installmentOptions');
    const instSum = document.getElementById('installmentSummary');

    payType.addEventListener('change', function () {
        const on = this.value === 'installment';
        instOpt.style.display = on ? '' : 'none';
        instSum.style.display = on ? '' : 'none';
        calculateTotals();
    });

    document.getElementById('installment_months')?.addEventListener('change', calculateTotals);
    document.getElementById('down_payment')?.addEventListener('input', calculateTotals);

    document.addEventListener('input', e => {
        if (e.target.classList.contains('qty-input') ||
            e.target.classList.contains('price-input'))
            calculateTotals();
    });

    if (payType.value === 'installment') {
        instOpt.style.display = '';
        instSum.style.display = '';
        calculateTotals();
    }

    document.getElementById('saleForm').addEventListener('submit', function (e) {
        if (!document.querySelector('.item-row')) {
            e.preventDefault(); alert('Please add at least one item.'); return;
        }
        if (!payType.value) {
            e.preventDefault(); alert('Please select a payment type.'); return;
        }
        if (!document.getElementById('payment_method').value) {
            e.preventDefault(); alert('Please select a payment method.');
        }
    });
});
</script>
@endpush
@endsection