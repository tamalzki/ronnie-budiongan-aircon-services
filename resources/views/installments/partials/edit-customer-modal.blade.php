@php
    $modalId = $modalId ?? 'editCustomerModal';
    $formAction = $formAction ?? '';
    $customerName = $customerName ?? '';
    $customerContact = $customerContact ?? '';
    $customerAddress = $customerAddress ?? '';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}Label">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form id="{{ $modalId }}Form" action="{{ $formAction }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-secondary text-white border-0">
                    <h5 class="modal-title" id="{{ $modalId }}Label">
                        <i class="bi bi-person-lines-fill"></i> Edit Customer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Updates the customer name, contact, and address on all installment sales for this account.
                    </p>
                    <div class="mb-3">
                        <label for="{{ $modalId }}Name" class="form-label fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="{{ $modalId }}Name" name="customer_name"
                               value="{{ $customerName }}" required maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label for="{{ $modalId }}Contact" class="form-label fw-semibold">Contact No.</label>
                        <input type="text" class="form-control" id="{{ $modalId }}Contact" name="customer_contact"
                               value="{{ $customerContact }}" maxlength="255">
                    </div>
                    <div class="mb-0">
                        <label for="{{ $modalId }}Address" class="form-label fw-semibold">Address</label>
                        <textarea class="form-control" id="{{ $modalId }}Address" name="customer_address" rows="2">{{ $customerAddress }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-secondary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
