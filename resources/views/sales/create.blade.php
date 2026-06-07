@extends('layouts.app')
@section('title', 'Create Sale')
@section('content')
<div class="container-fluid">

    <x-page-header title="Create New Sale" subtitle="Add products/services and generate invoice" icon="bi-cart-plus">
        <x-slot name="actions">
            <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Back to Sales
            </a>
        </x-slot>
    </x-page-header>

    @if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm mb-3">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    @if($lockedCount > 0)
    <div class="alert alert-warning border-0 shadow-sm mb-3" style="font-size:0.875rem;">
        <i class="bi bi-lock-fill me-1"></i>
        <strong>{{ $lockedCount }} product(s)</strong> have no selling price and are hidden.
        <a href="{{ route('products.index') }}" class="alert-link">Set prices in Products</a>.
    </div>
    @endif

    <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
        @csrf
        <div class="row g-3">

            {{-- LEFT --}}
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

            {{-- RIGHT --}}
            <div class="col-md-4">

                {{-- Summary --}}
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
                        <div id="installmentSummary" style="display:none;" class="border-top mt-2 pt-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted">Down Payment</span>
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

                {{-- Payment --}}
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
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm @error('payment_method') is-invalid @enderror"
                                    name="payment_method" required>
                                <option value="">-- Select Method --</option>
                                <option value="cash"          {{ old('payment_method') == 'cash'          ? 'selected' : '' }}>💵 Cash</option>
                                <option value="gcash"         {{ old('payment_method') == 'gcash'         ? 'selected' : '' }}>📱 GCash</option>
                                <option value="bank_transfer" {{ old('payment_method') == 'bank_transfer' ? 'selected' : '' }}>🏦 Bank Transfer</option>
                                <option value="cheque"        {{ old('payment_method') == 'cheque'        ? 'selected' : '' }}>🧾 Cheque</option>
                            </select>
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
                        <div id="installmentOptions" style="display:none;" class="border-top pt-2 mt-2">
                            <p class="small fw-semibold text-muted mb-2">Installment Settings</p>
                            <div class="mb-2">
                                <label class="form-label small fw-semibold">Number of Months</label>
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
                                <small class="text-success"><i class="bi bi-info-circle"></i> Saved as Month #1 (paid today)</small>
                            </div>
                            <div class="mb-1">
                                <label class="form-label small fw-semibold">Down Payment Method</label>
                                <select class="form-select form-select-sm" name="down_payment_method">
                                    <option value="">-- Same as above --</option>
                                    <option value="cash">💵 Cash</option>
                                    <option value="gcash">📱 GCash</option>
                                    <option value="bank_transfer">🏦 Bank Transfer</option>
                                    <option value="cheque">🧾 Cheque</option>
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

{{-- Pick warehouse / new serials (same POST fields as before: serial_ids + new_serials_raw) --}}
<div class="modal fade" id="salePickSerialModal" tabindex="-1" aria-labelledby="salePickSerialModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title" id="salePickSerialModalLabel"><i class="bi bi-upc-scan me-2"></i>Serial numbers</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1 fw-semibold text-dark" id="saleSerialModalLead" style="font-size:0.95rem;"></p>
                <p class="small text-muted mb-3 d-none" id="saleSerialNoStockHint"></p>
                <div id="saleSerialSlotsWrap" class="overflow-auto" style="max-height:min(360px,50vh);"></div>
                <div class="form-check mt-3 pt-3 border-top" id="saleSerialSkipWrap">
                    <input class="form-check-input" type="checkbox" id="saleSerialSkip">
                    <label class="form-check-label small text-muted" for="saleSerialSkip">
                        Sell this line without serial numbers (no inventory tracking)
                    </label>
                </div>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="saleSerialApplyBtn"><i class="bi bi-check2"></i> Apply</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const products = @json($products);
const services = @json($services);
let counter = 0;

function unitTypeBadge(unitType) {
    if (!unitType) return '';
    const isIndoor = unitType === 'indoor';
    const c = isIndoor ? '#0d6efd' : '#198754';
    const icon = isIndoor ? '❄️' : '🌀';
    return `<span style="font-size:0.68rem;padding:1px 6px;border-radius:20px;background:${c}15;color:${c};border:1px solid ${c}40;font-weight:600;">${icon} ${isIndoor?'Indoor':'Outdoor'}</span>`;
}

function addItem(type) {
    document.getElementById('emptyState')?.remove();
    counter++;
    const id = counter;
    const html = type === 'product' ? buildProductRow(id) : buildServiceRow(id);
    document.getElementById('itemsContainer').insertAdjacentHTML('beforeend', html);
    refreshDropdowns();
}

/* ─── PRODUCT ROW ─── */
function buildProductRow(id) {
    const opts = products.map(p => {
        const stockStr = p.stock === 0 ? ' ⚠ Out' : ` (${p.stock} in stock)`;
        return `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                     data-value="${p.id}" data-price="${p.price}" data-label="${escHtml(p.label)}"
                     data-unit-type="${p.unit_type||''}"
                     onmouseenter="this.style.background='#f0f4ff'"
                     onmouseleave="this.style.background=''"
                     onclick="pickProduct(${id},${p.id},${p.price},this.dataset.label,'${p.unit_type||''}')">
               <div class="d-flex align-items-center gap-2 flex-wrap">
                 <span>₱${p.price.toFixed(2)} — ${p.label}${stockStr}</span>
                 ${unitTypeBadge(p.unit_type)}
               </div>
             </div>`;
    }).join('');

    return `
    <div class="border rounded p-2 mb-2 item-row bg-white shadow-sm" id="item-${id}" data-type="product">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-success"><i class="bi bi-box"></i> Product</span>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(${id})"
                style="padding:1px 8px;font-size:0.78rem"><i class="bi bi-trash"></i> Remove</button>
      </div>
      <input type="hidden" name="items[${id}][type]" value="product">
      <input type="hidden" name="items[${id}][id]"       id="prod-id-${id}" value="">
      <input type="hidden" name="items[${id}][price]"    id="price-${id}"   value="0">

      <div class="row g-2 align-items-end mb-2">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Product <span class="text-danger">*</span></label>
          <div class="combobox position-relative" id="cb-${id}">
            <div class="form-control form-control-sm d-flex justify-content-between align-items-center gap-2"
                 style="cursor:pointer;user-select:none;" onclick="toggleCombo(${id})">
              <div class="d-flex align-items-center gap-2 flex-wrap" style="flex:1;min-width:0;">
                <span class="cb-display-${id} text-muted" style="font-size:0.82rem;">-- Select Product --</span>
                <span class="cb-badge-${id}"></span>
              </div>
              <i class="bi bi-chevron-down" style="font-size:0.7rem;color:#888;flex-shrink:0;"></i>
            </div>
            <div class="cb-panel-${id} position-absolute w-100 bg-white border rounded shadow-sm"
                 style="display:none;z-index:9999;top:100%;left:0;max-height:260px;overflow:hidden;">
              <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm cb-search-${id}"
                       placeholder="🔍 Search…" oninput="searchCombo(${id})" onclick="event.stopPropagation()">
              </div>
              <div class="cb-list-${id}" style="max-height:200px;overflow-y:auto;">${opts}</div>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Qty <span class="text-danger">*</span></label>
          <input type="number" min="1" value="1" name="items[${id}][quantity]" id="qty-${id}"
                 class="form-control form-control-sm" required
                 oninput="onProductQtyChange(${id}); calculateTotals();">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Unit Price</label>
          <div class="bg-light rounded text-center fw-semibold px-1 py-1 text-danger"
               id="price-display-${id}" style="font-size:0.82rem;height:31px;line-height:2;">₱—</div>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">Line Total</label>
          <div class="bg-light rounded text-center fw-bold text-primary px-1 py-1"
               id="line-${id}" style="font-size:0.82rem;height:31px;line-height:2;">₱0.00</div>
        </div>
      </div>

      {{-- Serials via modal → hidden fields preserved for SaleController / inventory --}}
      <div id="serial-section-${id}" style="display:none;">
        <div class="border rounded px-3 py-2 mt-2" style="background:#f8faff;">
          <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between">
            <div id="serial-summary-${id}" class="flex-grow-1" style="font-size:0.92rem;line-height:1.35;min-height:1.35em;"></div>
            <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" onclick="openSalePickSerialModal(${id})">
              <i class="bi bi-box-seam"></i> Warehouse / serials
            </button>
          </div>
          <div id="serial-ids-mount-${id}" class="d-none"></div>
          <textarea name="items[${id}][new_serials_raw]" id="new-sn-${id}" class="d-none" aria-hidden="true"></textarea>
        </div>
      </div>
    </div>`;
}

/* ─── SERVICE ROW ─── */
function buildServiceRow(id) {
    const opts = services.map(s =>
        `<div class="cb-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
              data-value="${s.id}" data-price="${s.price}" data-label="${escHtml(s.label)}"
              onmouseenter="this.style.background='#f0f4ff'"
              onmouseleave="this.style.background=''"
              onclick="pickService(${id},${s.id},${s.price},this.dataset.label)">
           ${s.label} — ₱${s.price.toFixed(2)}
         </div>`
    ).join('');

    return `
    <div class="border rounded p-2 mb-2 item-row bg-white shadow-sm" id="item-${id}" data-type="service">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-info text-dark"><i class="bi bi-tools"></i> Service</span>
        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeItem(${id})"
                style="padding:1px 8px;font-size:0.78rem"><i class="bi bi-trash"></i> Remove</button>
      </div>
      <input type="hidden" name="items[${id}][type]"     value="service">
      <input type="hidden" name="items[${id}][id]"       id="svc-id-${id}"  value="">
      <div class="row g-2 align-items-end">
        <div class="col-md-5">
          <label class="form-label small fw-semibold mb-1">Service <span class="text-danger">*</span></label>
          <div class="combobox position-relative" id="cb-${id}">
            <div class="form-control form-control-sm d-flex justify-content-between align-items-center gap-2"
                 style="cursor:pointer;user-select:none;" onclick="toggleCombo(${id})">
              <span class="cb-display-${id} text-muted" style="font-size:0.82rem;">-- Select Service --</span>
              <i class="bi bi-chevron-down" style="font-size:0.7rem;color:#888;flex-shrink:0;"></i>
            </div>
            <div class="cb-panel-${id} position-absolute w-100 bg-white border rounded shadow-sm"
                 style="display:none;z-index:9999;top:100%;left:0;max-height:260px;overflow:hidden;">
              <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm cb-search-${id}"
                       placeholder="🔍 Search…" oninput="searchCombo(${id})" onclick="event.stopPropagation()">
              </div>
              <div class="cb-list-${id}" style="max-height:200px;overflow-y:auto;">${opts}</div>
            </div>
          </div>
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Qty</label>
          <input type="number" class="form-control form-control-sm qty-input" min="1" value="1"
                 name="items[${id}][quantity]" oninput="calculateTotals()">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold mb-1">Price (₱)</label>
          <input type="number" step="0.01" class="form-control form-control-sm price-input"
                 name="items[${id}][price]" id="price-${id}" readonly style="background:#f8f9fa;">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold mb-1">Line Total</label>
          <div class="bg-light rounded text-center fw-bold text-primary px-1 py-1"
               id="line-${id}" style="font-size:0.82rem;height:31px;line-height:2;">₱0.00</div>
        </div>
      </div>
    </div>`;
}

/* ─── SERIAL MODAL / INVENTORY SYNC (SaleController unchanged) ─── */
let saleSerialDraftItemKey = null;

function getSalePickSerialBsModal() {
    return bootstrap.Modal.getOrCreateInstance(document.getElementById('salePickSerialModal'));
}

function escapeHtml(s) {
    return String(s)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function clearSerialRowAttachments(id) {
    const row = document.getElementById(`item-${id}`);
    if (row) delete row.dataset.serialDraftJson;
    const m = document.getElementById(`serial-ids-mount-${id}`);
    if (m) m.innerHTML = '';
    const ta = document.getElementById(`new-sn-${id}`);
    if (ta) ta.value = '';
    updateSerialRowSummary(id);
}

function productForRow(id) {
    const pid = parseInt(document.getElementById(`prod-id-${id}`)?.value, 10);
    if (!pid) return null;
    return products.find(p => p.id === pid) || null;
}

function updateSerialRowSummary(id) {
    const el = document.getElementById(`serial-summary-${id}`);
    if (!el) return;

    const prod = productForRow(id);
    const hasStock = (prod?.serials?.length || 0) > 0;
    const row = document.getElementById(`item-${id}`);

    let draft = null;
    try { draft = row?.dataset.serialDraftJson ? JSON.parse(row.dataset.serialDraftJson) : null; } catch (e) { draft = null; }

    // Resolve the actual serial numbers from the draft so the cashier can review them on the page.
    const entries = [];
    if (draft && Array.isArray(draft.slots)) {
        for (const s of draft.slots) {
            if (s.stockId) {
                const found = (prod?.serials || []).find(x => Number(x.id) === Number(s.stockId));
                entries.push({ label: found ? found.serial_number : ('#' + s.stockId), kind: 'stock' });
            } else if (s.newTxt && String(s.newTxt).trim()) {
                entries.push({ label: String(s.newTxt).trim(), kind: 'new' });
            }
        }
    }

    if (entries.length === 0) {
        if (draft && draft.attach === false) {
            el.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Selling without serial numbers.</span>';
        } else if (!hasStock) {
            el.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-circle"></i> Serial number required — click “Warehouse / serials” to encode.</span>';
        } else {
            el.innerHTML = '<span class="text-muted">Click “Warehouse / serials” to choose or encode serials.</span>';
        }
        return;
    }

    const chips = entries.map(e =>
        `<span class="badge ${e.kind === 'new' ? 'bg-info text-dark' : 'bg-success'} me-1 mb-1" style="font-weight:600;font-size:0.72rem;">`
        + `<i class="bi ${e.kind === 'new' ? 'bi-plus-circle' : 'bi-upc-scan'}"></i> ${escapeHtml(e.label)}${e.kind === 'new' ? ' · new' : ''}`
        + `</span>`).join('');

    el.innerHTML =
        `<div class="mb-1"><i class="bi bi-check-circle text-success"></i> <strong>${entries.length}</strong> serial(s) for this line:</div>`
        + `<div class="d-flex flex-wrap">${chips}</div>`;
}

function writeMountsAndTextarea(id, ids, news) {
    const m = document.getElementById(`serial-ids-mount-${id}`);
    if (m) m.innerHTML = ids.map(V => `<input type="hidden" name="items[${id}][serial_ids][]" value="${V}">`).join('');
    const ta = document.getElementById(`new-sn-${id}`);
    if (ta) ta.value = news.filter(Boolean).join('\n');
    updateSerialRowSummary(id);
}

function onSaleSlotStockChange(sel) {
    const row = sel.closest('.sale-sn-slot');
    if (!row) return;
    const input = row.querySelector('.sale-sn-newin');
    if (!input) return;
    const showNew = sel.value === '__new__';
    input.style.display = showNew ? '' : 'none';
    if (!showNew) input.value = '';
    else input.focus();
}

function renderSaleSerialSlots(serials, qty, slotsDraft) {
    const wrap = document.getElementById('saleSerialSlotsWrap');
    const hint = document.getElementById('saleSerialNoStockHint');
    if (!wrap) return;

    wrap.innerHTML = '';
    const hasStock = serials.length > 0;

    if (hasStock) {
        hint.textContent = 'Pick a warehouse serial for each piece, or choose “Type a new serial” to encode one that isn’t recorded yet.';
        hint.classList.remove('d-none');
    } else {
        hint.textContent = 'No serials are recorded for this model yet. Type each sticker number below — they’ll be saved to inventory when you save the sale.';
        hint.classList.remove('d-none');
    }

    for (let i = 0; i < qty; i++) {
        const d = slotsDraft[i] || { stockId: null, newTxt: '' };
        if (hasStock) {
            let opts = '<option value="">Choose serial…</option>';
            for (const s of serials) {
                const selAttr = Number(d.stockId) === Number(s.id) ? ' selected' : '';
                opts += `<option value="${s.id}"${selAttr}>${escapeHtml(s.serial_number)}</option>`;
            }
            const useNew = !d.stockId && !!String(d.newTxt || '').trim();
            opts += `<option value="__new__"${useNew ? ' selected' : ''}>➕ Type a new serial…</option>`;

            const initialNewVal = useNew ? escapeHtml(String(d.newTxt || '').trim()) : '';

            wrap.insertAdjacentHTML('beforeend',
                `<div class="sale-sn-slot border rounded p-2 mb-2" data-index="${i}" data-hasstock="1">
                  <label class="form-label small fw-semibold mb-1 text-muted">Piece ${i + 1} of ${qty}</label>
                  <select class="form-select form-select-sm sale-sn-stocksel" onchange="onSaleSlotStockChange(this)">${opts}</select>
                  <input type="text" class="form-control form-control-sm mt-2 sale-sn-newin" autocomplete="off"
                         placeholder="Type the serial number from the unit"
                         value="${initialNewVal}" style="display:${useNew ? '' : 'none'}">
                </div>`);

            const rowEl = wrap.lastElementChild;
            if (rowEl && d.stockId) {
                const ni = rowEl.querySelector('.sale-sn-newin');
                if (ni) { ni.style.display = 'none'; ni.value = ''; }
            }
        } else {
            wrap.insertAdjacentHTML('beforeend',
                `<div class="sale-sn-slot border rounded p-2 mb-2" data-index="${i}" data-hasstock="0">
                  <label class="form-label small fw-semibold mb-1 text-muted">Piece ${i + 1} of ${qty}</label>
                  <input type="text" class="form-control form-control-sm sale-sn-newonly" autocomplete="off"
                         placeholder="Serial from sticker — saved to inventory"
                         value="${escapeHtml(d.newTxt || '')}">
                </div>`);
        }
    }
}

function saleSerialDraftFromRow(row, qty, defaultAttach) {
    if (!row || !qty) return { attach: false, slots: [] };
    const raw = row.dataset.serialDraftJson;
    try {
        if (raw) {
            const j = JSON.parse(raw);
            if (j && j.attach === false && (!Array.isArray(j.slots) || j.slots.length === 0)) {
                return {
                    attach: false,
                    slots: Array.from({ length: qty }, () => ({ stockId: null, newTxt: '' })),
                };
            }
            if (j && Array.isArray(j.slots) && j.slots.length === qty) {
                return { attach: !!j.attach, slots: j.slots };
            }
        }
    } catch (e) { /* fallback */ }

    const slots = Array.from({ length: qty }, () => ({ stockId: null, newTxt: '' }));
    return { attach: defaultAttach, slots };
}

function openSalePickSerialModal(id) {
    const prodId = document.getElementById(`prod-id-${id}`)?.value;
    if (!prodId) {
        alert('Choose a product first.');
        return;
    }
    const qty = parseInt(document.getElementById(`qty-${id}`)?.value, 10) || 0;
    if (qty < 1) {
        alert('Quantity must be at least 1.');
        return;
    }

    saleSerialDraftItemKey = id;
    const prod = products.find(p => p.id === parseInt(prodId, 10));
    const serials = prod?.serials || [];
    const label = prod ? prod.label : 'Product';

    document.getElementById('saleSerialModalLead').textContent = `${label} · Quantity ${qty}`;

    const row = document.getElementById(`item-${id}`);
    const hasStock = serials.length > 0;
    // Default to attaching serials; required (no skip) when the product has no recorded serials.
    let draft = saleSerialDraftFromRow(row, qty, true);

    const skipWrap = document.getElementById('saleSerialSkipWrap');
    const skipBox  = document.getElementById('saleSerialSkip');
    if (hasStock) {
        skipWrap.classList.remove('d-none');
        skipBox.disabled = false;
        skipBox.checked = !draft.attach;
    } else {
        // No serials on file → encoding is mandatory.
        skipWrap.classList.add('d-none');
        skipBox.disabled = true;
        skipBox.checked = false;
    }

    renderSaleSerialSlots(serials, qty, draft.slots || []);
    saleSerialToggleSlotsVisibility();

    getSalePickSerialBsModal().show();
}

function saleSerialToggleSlotsVisibility() {
    const skip = document.getElementById('saleSerialSkip').checked;
    const wrap = document.getElementById('saleSerialSlotsWrap');
    const hint = document.getElementById('saleSerialNoStockHint');
    if (wrap) wrap.style.display = skip ? 'none' : '';
    if (hint) {
        if (skip) hint.style.display = 'none';
        else if (hint.textContent.trim()) hint.style.display = '';
        else hint.style.display = 'none';
    }
}

function applySalePickSerialModal() {
    const id = saleSerialDraftItemKey;
    if (id === null || id === undefined) return;

    const qty = parseInt(document.getElementById(`qty-${id}`)?.value, 10) || 0;
    if (qty < 1) {
        alert('Invalid quantity.');
        return;
    }

    const prodId = document.getElementById(`prod-id-${id}`)?.value;
    const row = document.getElementById(`item-${id}`);

    const skip = document.getElementById('saleSerialSkip').checked;
    if (skip) {
        clearSerialRowAttachments(id);
        if (row) row.dataset.serialDraftJson = JSON.stringify({ attach: false, slots: [] });
        updateSerialRowSummary(id);
        getSalePickSerialBsModal().hide();
        calculateTotals();
        return;
    }

    const ids = [];
    const news = [];
    const slots = [];
    let applyError = null;

    for (const slotEl of document.querySelectorAll('#saleSerialSlotsWrap .sale-sn-slot')) {
        const hs = slotEl.dataset.hasstock === '1';
        if (hs) {
            const sel = slotEl.querySelector('.sale-sn-stocksel');
            const ni = slotEl.querySelector('.sale-sn-newin');
            const v = sel ? sel.value : '';
            if (v === '') {
                applyError = 'Pick a warehouse serial or choose “New serial” for each piece.';
                break;
            }
            if (v === '__new__') {
                const txt = (ni && ni.value) ? ni.value.trim() : '';
                if (!txt) {
                    applyError = 'Type the new serial number for each piece you marked as new.';
                    break;
                }
                news.push(txt);
                slots.push({ stockId: null, newTxt: txt });
            } else {
                const nid = parseInt(v, 10);
                if (!nid) {
                    applyError = 'Pick a warehouse serial or choose “New serial” for each piece.';
                    break;
                }
                ids.push(nid);
                slots.push({ stockId: nid, newTxt: '' });
            }
        } else {
            const inp = slotEl.querySelector('.sale-sn-newonly');
            const txt = inp ? inp.value.trim() : '';
            if (!txt) {
                applyError = 'Enter a serial number for each piece.';
                break;
            }
            news.push(txt);
            slots.push({ stockId: null, newTxt: txt });
        }
    }

    if (applyError) {
        alert(applyError);
        return;
    }

    if (slots.length !== qty) return;

    const idSet = new Set(ids);
    if (idSet.size !== ids.length) {
        alert('You chose the same warehouse serial twice. Each piece needs a different one.');
        return;
    }

    const nSet = new Set(news);
    if (nSet.size !== news.length) {
        alert('Duplicate new serial entries — use a unique number per piece.');
        return;
    }

    if (ids.length + news.length !== qty) {
        alert(`This line is quantity ${qty}. You must assign exactly that many serials.`);
        return;
    }

    if (row) row.dataset.serialDraftJson = JSON.stringify({ attach: true, slots });
    writeMountsAndTextarea(id, ids, news);

    getSalePickSerialBsModal().hide();
    calculateTotals();
}

/* ─── PICK PRODUCT ─── */
function pickProduct(id, productId, price, label, unitType) {
    document.getElementById(`prod-id-${id}`).value      = productId;
    document.getElementById(`price-${id}`).value        = price;
    document.getElementById(`price-display-${id}`).textContent = '₱' + price.toFixed(2);

    const disp = document.querySelector(`.cb-display-${id}`);
    disp.textContent = label; disp.style.color = '#212529';
    document.querySelector(`.cb-badge-${id}`).innerHTML = unitTypeBadge(unitType);
    document.querySelector(`.cb-panel-${id}`).style.display = 'none';
    document.querySelector(`.cb-search-${id}`).value = '';
    searchCombo(id);

    clearSerialRowAttachments(id);
    const section = document.getElementById(`serial-section-${id}`);
    if (section) section.style.display = '';

    calculateTotals();
    refreshDropdowns();

    setTimeout(() => openSalePickSerialModal(id), 0);
}

function onProductQtyChange(id) {
    const qty = parseInt(document.getElementById(`qty-${id}`)?.value, 10) || 1;
    const row = document.getElementById(`item-${id}`);
    if (!row?.dataset.serialDraftJson) return;
    try {
        const j = JSON.parse(row.dataset.serialDraftJson);
        const hadSerials =
            !!(j.attach)
            || (Array.isArray(j.slots) && j.slots.some(s => (s.stockId != null && s.stockId !== '') || (s.newTxt != null && String(s.newTxt).trim() !== '')));
        if (Array.isArray(j.slots) && j.slots.length !== qty && hadSerials) {
            clearSerialRowAttachments(id);
        }
    } catch (e) {
        clearSerialRowAttachments(id);
    }
}

function parseNewSerialLinesFromTextarea(val) {
    if (!val || !String(val).trim()) return [];
    return String(val).split(/\r\n|\r|\n/).map(s => s.trim()).filter(Boolean);
}

/* ─── PICK SERVICE ─── */
function pickService(id, serviceId, price, label) {
    document.getElementById(`svc-id-${id}`).value  = serviceId;
    document.getElementById(`price-${id}`).value   = price;
    const disp = document.querySelector(`.cb-display-${id}`);
    disp.textContent = label; disp.style.color = '#212529';
    document.querySelector(`.cb-panel-${id}`).style.display = 'none';
    document.querySelector(`.cb-search-${id}`).value = '';
    searchCombo(id);
    calculateTotals();
    refreshDropdowns();
}

/* ─── COMBOBOX ─── */
function toggleCombo(id) {
    const panel = document.querySelector(`.cb-panel-${id}`);
    const open  = panel.style.display !== 'none';
    closeAllCombos();
    if (!open) { panel.style.display = ''; document.querySelector(`.cb-search-${id}`)?.focus(); }
}
function searchCombo(id) {
    const term = document.querySelector(`.cb-search-${id}`).value.toLowerCase();
    document.querySelectorAll(`#cb-${id} .cb-option`).forEach(o => {
        o.style.display = o.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}
function closeAllCombos() {
    document.querySelectorAll('[class*="cb-panel-"]').forEach(p => p.style.display = 'none');
}
document.addEventListener('click', e => { if (!e.target.closest('.combobox')) closeAllCombos(); });

/* ─── REMOVE ─── */
function removeItem(id) {
    document.getElementById(`item-${id}`)?.remove();
    if (!document.querySelector('.item-row')) {
        document.getElementById('itemsContainer').innerHTML =
            `<div class="text-center text-muted py-4" id="emptyState">
              <i class="bi bi-inbox fs-1 d-block mb-2"></i>
              <p class="mb-0">No items yet. Click "Add Product" or "Add Service".</p>
            </div>`;
    }
    refreshDropdowns(); calculateTotals();
}

/* ─── GREY OUT USED PRODUCTS ─── */
function refreshDropdowns() {
    const used = new Set();
    document.querySelectorAll('.item-row[data-type="product"]').forEach(r => {
        const v = document.getElementById(`prod-id-${r.id.replace('item-','')}`)?.value;
        if (v) used.add(parseInt(v));
    });
    document.querySelectorAll('.item-row[data-type="product"]').forEach(r => {
        const id  = r.id.replace('item-', '');
        const cur = parseInt(document.getElementById(`prod-id-${id}`)?.value || 0);
        document.querySelectorAll(`#cb-${id} .cb-option`).forEach(o => {
            const val   = parseInt(o.dataset.value);
            const taken = val !== cur && used.has(val);
            o.style.opacity        = taken ? '0.35' : '1';
            o.style.textDecoration = taken ? 'line-through' : '';
            o.style.pointerEvents  = taken ? 'none' : '';
        });
    });
}

/* ─── TOTALS ─── */
function calculateTotals() {
    let sub = 0;
    document.querySelectorAll('.item-row').forEach(r => {
        const id   = r.id.replace('item-', '');
        const type = r.dataset.type;
        const price = parseFloat(document.getElementById(`price-${id}`)?.value) || 0;
        let   qty   = 0;
        if (type === 'product') {
            qty = parseInt(document.getElementById(`qty-${id}`)?.value) || 0;
        } else {
            qty = parseFloat(r.querySelector('.qty-input')?.value) || 0;
        }
        const line = qty * price;
        sub += line;
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

function updateInstallmentSummary(total) {
    if (total === undefined) total = parseFloat(document.getElementById('totalDisplay').textContent) || 0;
    const months  = parseInt(document.getElementById('installment_months')?.value) || 12;
    const down    = parseFloat(document.getElementById('down_payment')?.value) || 0;
    const balance = Math.max(0, total - down);
    const monthly = months > 0 ? balance / months : 0;
    document.getElementById('summaryDown').textContent    = '₱' + down.toFixed(2);
    document.getElementById('summaryBalance').textContent = '₱' + balance.toFixed(2);
    document.getElementById('summaryMonthly').textContent = '₱' + monthly.toFixed(2);
    document.getElementById('summaryNote').textContent    = down > 0
        ? `Down = Month #1. Then ${months} × ₱${monthly.toFixed(2)}/mo.`
        : `${months} equal payments of ₱${monthly.toFixed(2)}/mo.`;
}

function escHtml(s) { return s.replace(/"/g, '&quot;'); }

/* ─── INIT ─── */
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

    if (payType.value === 'installment') {
        instOpt.style.display = '';
        instSum.style.display = '';
        calculateTotals();
    }

    document.getElementById('saleSerialApplyBtn')?.addEventListener('click', applySalePickSerialModal);
    document.getElementById('saleSerialSkip')?.addEventListener('change', saleSerialToggleSlotsVisibility);

    document.getElementById('saleForm').addEventListener('submit', function (e) {
        if (!document.querySelector('.item-row')) {
            e.preventDefault(); alert('Add at least one item.'); return;
        }
        // Validate product items have serials selected
        let valid = true;
        document.querySelectorAll('.item-row[data-type="product"]').forEach(r => {
            const id     = r.id.replace('item-', '');
            const prodId = document.getElementById(`prod-id-${id}`)?.value;
            if (!prodId) { valid = false; alert('Please select a product for all product rows.'); return; }
            const qty = parseInt(document.getElementById(`qty-${id}`)?.value) || 0;
            if (qty < 1) { valid = false; alert('Product Qty must be at least 1 for each product row.'); return; }
            const mount = document.getElementById(`serial-ids-mount-${id}`);
            const sel = mount ? mount.querySelectorAll('input[type="hidden"]').length : 0;
            const newLines = parseNewSerialLinesFromTextarea(document.getElementById(`new-sn-${id}`)?.value);
            const attached = sel + newLines.length;

            const prod = products.find(p => p.id === parseInt(prodId, 10));
            const noStock = prod && ((prod.stock || 0) === 0 || (prod.serials || []).length === 0);
            if (noStock && attached !== qty) {
                valid = false;
                alert('This product has no recorded serials, so a serial number is required for each unit. Open “Warehouse / serials” and encode ' + qty + ' serial(s).');
                return;
            }

            if (attached !== qty && attached !== 0) {
                valid = false;
                alert('Product line: Quantity is ' + qty + ', but warehouse + new serials add up to ' + attached + '. Open “Warehouse / serials” or leave all empty.');
            }
        });
        if (!valid) e.preventDefault();
    });
});
</script>
@endpush
@endsection