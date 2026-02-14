@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-cart-plus text-primary"></i> Create Purchase Order</h2>
            <p class="text-muted mb-0">Order products from supplier</p>
        </div>
        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('purchase-orders.store') }}" method="POST" id="poForm">
        @csrf

        {{-- Supplier & Dates --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white border-0">
                <h5 class="mb-0"><i class="bi bi-building"></i> Supplier & Order Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Supplier <span class="text-danger">*</span></label>
                        <select class="form-select" name="supplier_id" required>
                            <option value="">-- Select Supplier --</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Order Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="order_date"
                               value="{{ old('order_date', date('Y-m-d')) }}" required id="orderDate">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Expected Delivery Date</label>
                        <input type="date" class="form-control" name="expected_delivery_date"
                               value="{{ old('expected_delivery_date') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0 d-flex justify-content-between align-items-center" style="background:#f8f9fa;">
                <h5 class="mb-0"><i class="bi bi-box-seam text-primary"></i> Order Items</h5>
                <button type="button" class="btn btn-success btn-sm" onclick="addItem()">
                    <i class="bi bi-plus-circle"></i> Add Item
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0" id="itemsTable">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-3 py-2">Product (Brand - Model)</th>
                                <th class="px-3 py-2" width="100">Qty</th>
                                <th class="px-3 py-2" width="140">Unit Cost (₱)</th>
                                <th class="px-3 py-2" width="120">Discount (%)</th>
                                <th class="px-3 py-2" width="140">Net Cost (₱)</th>
                                <th class="px-3 py-2" width="140">Total (₱)</th>
                                <th class="px-3 py-2" width="60"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            {{-- rows injected by JS --}}
                        </tbody>
                        <tfoot>
                            <tr class="bg-light">
                                <td colspan="5" class="text-end fw-bold px-3 py-2">Grand Total:</td>
                                <td class="fw-bold text-primary px-3 py-2 fs-5">₱<span id="grandTotal">0.00</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header border-0" style="background:#f8f9fa;">
                <h5 class="mb-0"><i class="bi bi-credit-card text-primary"></i> Payment Terms</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Payment Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="payment_type" id="paymentType" required>
                            <option value="full"   {{ old('payment_type') == 'full'   ? 'selected' : '' }}>Full Payment</option>
                            <option value="45days" {{ old('payment_type') == '45days' ? 'selected' : '' }}>45-Day Term</option>
                        </select>
                        <small class="text-muted">45-Day Term: full balance due within 45 days from order date.</small>
                    </div>

                    {{-- 45-day deadline preview --}}
                    <div class="col-md-6" id="deadlinePreview" style="display:none;">
                        <label class="form-label fw-semibold">Payment Deadline</label>
                        <div class="alert alert-warning mb-0 py-2">
                            <i class="bi bi-calendar-event"></i>
                            Due on: <strong id="deadlineDate">—</strong>
                            <span class="text-muted ms-2">(45 days from order date)</span>
                            <br>
                            <small class="text-danger">
                                <i class="bi bi-bell"></i> You will be alerted 10 days before the deadline.
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Downpayment section (45days only) --}}
                <div id="downpaymentSection" style="display:none;" class="mt-3">
                    <hr>
                    <h6 class="fw-semibold text-muted mb-3">Downpayment (Optional)</h6>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Downpayment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" class="form-control" name="downpayment_amount"
                                       id="downpaymentAmount" value="{{ old('downpayment_amount', 0) }}" min="0">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Downpayment Date</label>
                            <input type="date" class="form-control" name="downpayment_date"
                                   value="{{ old('downpayment_date', date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" name="downpayment_method">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference #</label>
                            <input type="text" class="form-control" name="downpayment_reference"
                                   value="{{ old('downpayment_reference') }}" placeholder="Optional">
                        </div>
                    </div>

                    {{-- Balance preview --}}
                    <div class="alert alert-info mt-3 mb-0 py-2" id="balancePreview" style="display:none;">
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Total</small>
                                <strong class="text-primary">₱<span id="previewTotal">0.00</span></strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Downpayment</small>
                                <strong class="text-success">₱<span id="previewDown">0.00</span></strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Balance Due</small>
                                <strong class="text-danger">₱<span id="previewBalance">0.00</span></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <label class="form-label fw-semibold">Notes</label>
                <textarea class="form-control" name="notes" rows="2"
                          placeholder="Optional notes about this purchase order">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary px-4">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary btn-lg px-5">
                <i class="bi bi-save"></i> Create Purchase Order
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Products data pre-mapped in controller
const products = {!! json_encode($productsJson) !!};

let rowIndex = 0;

function addItem() {
    rowIndex++;
    const opts = products.map(p =>
        `<option value="${p.id}" data-cost="${p.cost}">${p.label}</option>`
    ).join('');

    const row = `
    <tr id="row-${rowIndex}">
        <td class="px-2 py-2">
            <select class="form-select form-select-sm product-select" name="items[${rowIndex}][product_id]" required onchange="onProductChange(this)">
                <option value="">-- Select Product --</option>
                ${opts}
            </select>
        </td>
        <td class="px-2 py-2">
            <input type="number" class="form-control form-control-sm qty-input" name="items[${rowIndex}][quantity]"
                   value="1" min="1" required onchange="calcRow(${rowIndex})">
        </td>
        <td class="px-2 py-2">
            <input type="number" step="0.01" class="form-control form-control-sm cost-input" name="items[${rowIndex}][unit_cost]"
                   value="" min="0" required onchange="calcRow(${rowIndex})">
        </td>
        <td class="px-2 py-2">
            <div class="input-group input-group-sm">
                <input type="number" step="0.01" class="form-control disc-input" name="items[${rowIndex}][discount_percent]"
                       value="0" min="0" max="100" onchange="calcRow(${rowIndex})">
                <span class="input-group-text">%</span>
            </div>
        </td>
        <td class="px-2 py-2">
            <input type="text" class="form-control form-control-sm net-cost-display" id="net-${rowIndex}" readonly value="0.00">
        </td>
        <td class="px-2 py-2">
            <input type="text" class="form-control form-control-sm total-display" id="total-${rowIndex}" readonly value="0.00">
        </td>
        <td class="px-2 py-2">
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(${rowIndex})">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>`;

    document.getElementById('itemsBody').insertAdjacentHTML('beforeend', row);
}

function onProductChange(select) {
    const option  = select.options[select.selectedIndex];
    const cost    = option.getAttribute('data-cost') || 0;
    const rowEl   = select.closest('tr');
    rowEl.querySelector('.cost-input').value = parseFloat(cost).toFixed(2);

    // find row index from row id
    const rowId = rowEl.id.split('-')[1];
    calcRow(rowId);
}

function calcRow(idx) {
    const row      = document.getElementById(`row-${idx}`);
    if (!row) return;

    const qty      = parseFloat(row.querySelector('.qty-input').value)  || 0;
    const cost     = parseFloat(row.querySelector('.cost-input').value) || 0;
    const disc     = parseFloat(row.querySelector('.disc-input').value) || 0;
    const netCost  = cost * (1 - disc / 100);
    const total    = qty * netCost;

    row.querySelector(`#net-${idx}`).value   = netCost.toFixed(2);
    row.querySelector(`#total-${idx}`).value = total.toFixed(2);

    calcGrandTotal();
}

function calcGrandTotal() {
    let grand = 0;
    document.querySelectorAll('.total-display').forEach(el => {
        grand += parseFloat(el.value) || 0;
    });
    document.getElementById('grandTotal').textContent = grand.toFixed(2);
    updateBalancePreview();
}

function removeRow(idx) {
    const row = document.getElementById(`row-${idx}`);
    if (row) row.remove();
    calcGrandTotal();
}

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
    if (!orderDate) return;

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
    const rows = document.querySelectorAll('#itemsBody tr');
    if (rows.length === 0) {
        e.preventDefault();
        alert('Please add at least one item.');
    }
});

// Init first row and deadline
addItem();
updateDeadline();
</script>
@endpush
@endsection