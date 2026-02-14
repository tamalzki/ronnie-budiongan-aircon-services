@extends('layouts.app')

@section('title', 'Create Sale')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Create New Sale</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
                @csrf

                <!-- Customer Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Customer Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                <input 
                                    type="text" 
                                    class="form-control @error('customer_name') is-invalid @enderror" 
                                    id="customer_name" 
                                    name="customer_name" 
                                    value="{{ old('customer_name') }}"
                                    required
                                >
                                @error('customer_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="customer_contact" class="form-label">Contact Number</label>
                                <input 
                                    type="text" 
                                    class="form-control @error('customer_contact') is-invalid @enderror" 
                                    id="customer_contact" 
                                    name="customer_contact" 
                                    value="{{ old('customer_contact') }}"
                                >
                                @error('customer_contact')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="sale_date" class="form-label">Sale Date <span class="text-danger">*</span></label>
                                <input 
                                    type="date" 
                                    class="form-control @error('sale_date') is-invalid @enderror" 
                                    id="sale_date" 
                                    name="sale_date" 
                                    value="{{ old('sale_date', date('Y-m-d')) }}"
                                    required
                                >
                                @error('sale_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="customer_address" class="form-label">Address</label>
                            <textarea 
                                class="form-control @error('customer_address') is-invalid @enderror" 
                                id="customer_address" 
                                name="customer_address" 
                                rows="2"
                            >{{ old('customer_address') }}</textarea>
                            @error('customer_address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Items Section -->
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Sale Items</h5>
                        <div>
                            <button type="button" class="btn btn-sm btn-success" onclick="addItem('product')">
                                <i class="bi bi-plus"></i> Add Product
                            </button>
                            <button type="button" class="btn btn-sm btn-info" onclick="addItem('service')">
                                <i class="bi bi-plus"></i> Add Service
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="itemsContainer">
                            <!-- Items will be added here dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Payment Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="payment_type" class="form-label">Payment Type <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_type') is-invalid @enderror" id="payment_type" name="payment_type" required>
                                <option value="cash" {{ old('payment_type') == 'cash' ? 'selected' : '' }}>Cash (Full Payment)</option>
                                <option value="installment" {{ old('payment_type') == 'installment' ? 'selected' : '' }}>Installment</option>
                            </select>
                            @error('payment_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Installment Options -->
                        <div id="installmentOptions" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="down_payment" class="form-label">Down Payment (Optional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" step="0.01" class="form-control" id="down_payment" name="down_payment" min="0" value="{{ old('down_payment', 0) }}">
                                    </div>
                                    <small class="text-muted">Amount paid upfront</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="installment_months" class="form-label">Installment Period <span class="text-danger">*</span></label>
                                    <select class="form-select" id="installment_months" name="installment_months">
                                        <option value="3">3 Months</option>
                                        <option value="6">6 Months</option>
                                        <option value="12" selected>12 Months</option>
                                        <option value="18">18 Months</option>
                                        <option value="24">24 Months</option>
                                    </select>
                                </div>
                            </div>

                            <div id="installmentSummary" class="alert alert-info">
                                <strong>Installment Summary:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>Total Amount: <span id="summaryTotal">₱0.00</span></li>
                                    <li>Down Payment: <span id="summaryDown">₱0.00</span></li>
                                    <li>Balance to Install: <span id="summaryBalance">₱0.00</span></li>
                                    <li>Monthly Payment: <span id="summaryMonthly">₱0.00</span></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Totals Summary -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <table class="table table-bordered">
                                    <tr class="table-primary">
                                        <th width="200">Total:</th>
                                        <td class="text-end fs-4 fw-bold">₱<span id="totalDisplay">0.00</span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Additional Notes</h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" name="notes" rows="3" placeholder="Optional notes about this sale">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-save"></i> Create Sale
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
let itemCounter = 0;
const products = @json($products);
const services = @json($services);

function addItem(type) {
    itemCounter++;
    const container = document.getElementById('itemsContainer');
    
    let options = '';
    if (type === 'product') {
        products.forEach(product => {
            options += `<option value="${product.id}" data-price="${product.price}">${product.name} - ₱${parseFloat(product.price).toFixed(2)} (Stock: ${product.stock_quantity})</option>`;
        });
    } else {
        services.forEach(service => {
            options += `<option value="${service.id}" data-price="${service.default_price}">${service.name} - ₱${parseFloat(service.default_price).toFixed(2)}</option>`;
        });
    }

    const itemHtml = `
        <div class="card mb-2 item-row" id="item-${itemCounter}">
            <div class="card-body">
                <div class="row align-items-end">
                    <input type="hidden" name="items[${itemCounter}][type]" value="${type}">
                    
                    <div class="col-md-5">
                        <label class="form-label">${type === 'product' ? 'Product' : 'Service'}</label>
                        <select class="form-select" name="items[${itemCounter}][id]" required onchange="updatePrice(${itemCounter}, '${type}')">
                            <option value="">-- Select ${type === 'product' ? 'Product' : 'Service'} --</option>
                            ${options}
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control quantity-input" name="items[${itemCounter}][quantity]" value="1" min="1" required>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" class="form-control price-input" name="items[${itemCounter}][price]" id="price-${itemCounter}" required>
                    </div>
                    
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger w-100" onclick="removeItem(${itemCounter})">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', itemHtml);
}

function removeItem(id) {
    document.getElementById(`item-${id}`).remove();
    calculateTotals();
}

function updatePrice(itemId, type) {
    const select = document.querySelector(`select[name="items[${itemId}][id]"]`);
    const priceInput = document.getElementById(`price-${itemId}`);
    
    if (select.selectedIndex > 0) {
        const selectedOption = select.options[select.selectedIndex];
        const price = selectedOption.getAttribute('data-price');
        priceInput.value = parseFloat(price).toFixed(2);
        calculateTotals();
    }
}

function calculateTotals() {
    let total = 0;
    
    document.querySelectorAll('#itemsContainer .item-row').forEach(row => {
        const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        total += quantity * price;
    });
    
    document.getElementById('totalDisplay').textContent = total.toFixed(2);
    updateInstallmentSummary();
}

function updateInstallmentSummary() {
    const total = parseFloat(document.getElementById('totalDisplay').textContent) || 0;
    const down = parseFloat(document.getElementById('down_payment').value) || 0;
    const months = parseInt(document.getElementById('installment_months').value) || 12;
    const balance = total - down;
    const monthly = balance / months;

    document.getElementById('summaryTotal').textContent = '₱' + total.toFixed(2);
    document.getElementById('summaryDown').textContent = '₱' + down.toFixed(2);
    document.getElementById('summaryBalance').textContent = '₱' + balance.toFixed(2);
    document.getElementById('summaryMonthly').textContent = '₱' + monthly.toFixed(2);
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    const paymentType = document.getElementById('payment_type');
    const installmentOptions = document.getElementById('installmentOptions');
    const downPayment = document.getElementById('down_payment');
    const installmentMonths = document.getElementById('installment_months');

    // Toggle installment options
    paymentType.addEventListener('change', function() {
        if (this.value === 'installment') {
            installmentOptions.style.display = 'block';
        } else {
            installmentOptions.style.display = 'none';
        }
    });

    // Update installment summary
    downPayment.addEventListener('input', updateInstallmentSummary);
    installmentMonths.addEventListener('change', updateInstallmentSummary);

    // Update on item changes
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input') || e.target.classList.contains('price-input')) {
            calculateTotals();
        }
    });

    // Form validation
    document.getElementById('saleForm').addEventListener('submit', function(e) {
        const itemsCount = document.querySelectorAll('#itemsContainer .item-row').length;
        if (itemsCount === 0) {
            e.preventDefault();
            alert('Please add at least one item to the sale.');
            return false;
        }
    });

    // Trigger initial state
    if (paymentType.value === 'installment') {
        installmentOptions.style.display = 'block';
    }
});
</script>
@endpush
@endsection