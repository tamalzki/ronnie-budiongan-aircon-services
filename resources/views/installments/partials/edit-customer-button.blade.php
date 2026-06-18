<button type="button"
        class="btn btn-outline-secondary btn-sm edit-customer-btn d-inline-flex align-items-center gap-1 py-0 px-2"
        style="font-size:0.75rem;font-weight:500;line-height:1.35;"
        data-bs-toggle="modal"
        data-bs-target="#editCustomerModal"
        data-action="{{ route('installments.customer.update', $saleId) }}"
        data-name="{{ $name }}"
        data-contact="{{ $contact ?? '' }}"
        data-address="{{ $address ?? '' }}"
        title="Edit customer"
        onclick="event.stopPropagation()">
    <i class="bi bi-pencil" style="font-size:0.72rem;"></i>
    <span>Edit</span>
</button>
