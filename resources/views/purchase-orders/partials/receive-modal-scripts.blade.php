@once
@push('scripts')
<script>
function poReceiveSerialGroup(loopIndex, qty, fieldName, heading) {
    let inputs = '';
    for (let i = 0; i < qty; i++) {
        inputs += `
        <div class="col-md-4 col-sm-6">
            <div class="input-group input-group-sm mb-1">
                <span class="input-group-text text-muted" style="font-size:0.72rem;min-width:36px;">#${i + 1}</span>
                <input type="text" class="form-control form-control-sm"
                       name="items[${loopIndex}][${fieldName}][]"
                       placeholder="Serial #${i + 1}" required
                       style="font-family:monospace;font-size:0.82rem;">
            </div>
        </div>`;
    }
    const head = heading ? `<div class="small fw-semibold mb-1" style="font-size:0.72rem;">${heading}</div>` : '';
    return `<div class="mb-1">${head}<div class="row g-1">${inputs}</div></div>`;
}

function poReceiveRebuildSerials(form, qtyInput) {
    const modalId   = form.dataset.modalId;
    const loopIndex = qtyInput.dataset.loopIndex;
    const wrap      = form.querySelector('#receive-serials-' + modalId + '-' + loopIndex);
    const card      = qtyInput.closest('.receive-item');
    if (!wrap || !card) return;

    const qty   = parseInt(qtyInput.value, 10) || 0;
    const isSet = card.dataset.isSet === '1';

    wrap.innerHTML = '';
    if (qty < 1) return;

    if (isSet) {
        wrap.insertAdjacentHTML('beforeend', poReceiveSerialGroup(loopIndex, qty, 'serials', '❄️ Indoor unit — ' + card.dataset.indoorModel));
        wrap.insertAdjacentHTML('beforeend', poReceiveSerialGroup(loopIndex, qty, 'outdoor_serials', '🌀 Outdoor unit — ' + card.dataset.outdoorModel));
    } else {
        wrap.insertAdjacentHTML('beforeend', poReceiveSerialGroup(loopIndex, qty, 'serials', ''));
    }
}

function poReceiveInitForms() {
    document.querySelectorAll('.po-receive-form').forEach(function (form) {
        form.querySelectorAll('.receive-qty').forEach(function (qtyInput) {
            poReceiveRebuildSerials(form, qtyInput);
            qtyInput.addEventListener('input', function () {
                poReceiveRebuildSerials(form, qtyInput);
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', function () {
    poReceiveInitForms();

    if (window.location.hash !== '#receive') {
        const modalEl = document.querySelector('.po-due-receive-modal.auto-open-due-receive');
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    if (window.location.hash === '#receive') {
        const modalEl = document.getElementById('receiveModal');
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }
});
</script>
@endpush
@endonce
