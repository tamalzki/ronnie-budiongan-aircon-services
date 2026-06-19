@php
    $defaultCustomerNo = '1378';
    $defaultName = 'RONNIE BUDIONGAN AIRCON SUPPLY AND SERVICES, INC';
    $customerNo = old('delivered_to_customer_no', $deliveredToCustomerNo ?? $defaultCustomerNo);
    $name = old('delivered_to_name', $deliveredToName ?? $defaultName);
    $address = old('delivered_to_address', $deliveredToAddress ?? '');
@endphp
<div class="border rounded bg-white px-2 py-1 h-100 delivered-to-card" style="font-size:0.72rem;line-height:1.35;">
    <div class="d-flex justify-content-between align-items-start gap-1 mb-1">
        <span class="fw-bold text-uppercase text-primary" style="font-size:0.66rem;letter-spacing:.5px;">
            <i class="bi bi-truck"></i> Delivered To
        </span>
        <button type="button" class="btn btn-outline-secondary btn-sm delivered-to-edit-btn"
                onclick="toggleDeliveredToEdit(this)">
            <i class="bi bi-pencil"></i> Edit
        </button>
    </div>

    <div class="delivered-to-display">
        <div class="text-muted delivered-to-display-customer-no">Customer No. : {{ $customerNo ?: '—' }}</div>
        <div class="fw-semibold delivered-to-display-name">{{ $name ?: '—' }}</div>
        <div class="delivered-to-display-address {{ $address ? '' : 'd-none' }}">{{ $address }}</div>
    </div>

    <div class="delivered-to-edit-panel mt-1" style="display:none;">
        <div class="mb-1">
            <label class="form-label mb-0 text-muted" style="font-size:0.68rem;">Customer No.</label>
            <input type="text" class="form-control form-control-sm delivered-to-field-customer-no"
                   value="{{ $customerNo }}" maxlength="255">
        </div>
        <div class="mb-1">
            <label class="form-label mb-0 text-muted" style="font-size:0.68rem;">Name</label>
            <input type="text" class="form-control form-control-sm delivered-to-field-name"
                   value="{{ $name }}" maxlength="255">
        </div>
        <div class="mb-1">
            <label class="form-label mb-0 text-muted" style="font-size:0.68rem;">Address</label>
            <textarea class="form-control form-control-sm delivered-to-field-address" rows="2">{{ $address }}</textarea>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="saveDeliveredTo(this)">
            <i class="bi bi-check-lg"></i> Done
        </button>
    </div>

    <input type="hidden" name="delivered_to_customer_no" class="delivered-to-input-customer-no" value="{{ $customerNo }}">
    <input type="hidden" name="delivered_to_name" class="delivered-to-input-name" value="{{ $name }}">
    <input type="hidden" name="delivered_to_address" class="delivered-to-input-address" value="{{ $address }}">
</div>

@once
@push('scripts')
<script>
function toggleDeliveredToEdit(btn) {
    const card = btn.closest('.delivered-to-card');
    const no = card.querySelector('.delivered-to-input-customer-no').value;
    const name = card.querySelector('.delivered-to-input-name').value;
    const address = card.querySelector('.delivered-to-input-address').value;

    card.querySelector('.delivered-to-field-customer-no').value = no;
    card.querySelector('.delivered-to-field-name').value = name;
    card.querySelector('.delivered-to-field-address').value = address;

    card.querySelector('.delivered-to-display').style.display = 'none';
    card.querySelector('.delivered-to-edit-panel').style.display = '';
    btn.style.display = 'none';
    card.dataset.editing = '1';
}

function saveDeliveredToCard(card) {
    if (!card || card.dataset.editing !== '1') return;

    const no = card.querySelector('.delivered-to-field-customer-no').value.trim();
    const name = card.querySelector('.delivered-to-field-name').value.trim();
    const address = card.querySelector('.delivered-to-field-address').value.trim();

    card.querySelector('.delivered-to-input-customer-no').value = no;
    card.querySelector('.delivered-to-input-name').value = name;
    card.querySelector('.delivered-to-input-address').value = address;

    card.querySelector('.delivered-to-display-customer-no').textContent =
        'Customer No. : ' + (no || '—');
    card.querySelector('.delivered-to-display-name').textContent = name || '—';

    const addrEl = card.querySelector('.delivered-to-display-address');
    if (address) {
        addrEl.textContent = address;
        addrEl.classList.remove('d-none');
    } else {
        addrEl.textContent = '';
        addrEl.classList.add('d-none');
    }

    card.querySelector('.delivered-to-display').style.display = '';
    card.querySelector('.delivered-to-edit-panel').style.display = 'none';
    card.querySelector('.delivered-to-edit-btn').style.display = '';
    delete card.dataset.editing;
}

function saveDeliveredTo(btn) {
    saveDeliveredToCard(btn.closest('.delivered-to-card'));
}

document.addEventListener('mousedown', function (e) {
    document.querySelectorAll('.delivered-to-card[data-editing="1"]').forEach(function (card) {
        if (card.contains(e.target)) return;
        saveDeliveredToCard(card);
    });
});
</script>
@endpush
@endonce
