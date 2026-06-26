@once
@push('scripts')
<script>
(function () {
    const SESSION_KEY = 'poDueReceiveSnoozed';

    function isDueReceiveSnoozed() {
        try {
            return sessionStorage.getItem(SESSION_KEY) === '1';
        } catch (e) {
            return false;
        }
    }

    window.poDueReceiveSnoozeSession = function () {
        try {
            sessionStorage.setItem(SESSION_KEY, '1');
        } catch (e) {}
    };

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

    window.poReceiveRebuildSerials = function (form, qtyInput) {
        const modalId   = form.dataset.modalId;
        const loopIndex = qtyInput.dataset.loopIndex;
        const wrap      = form.querySelector('#receive-serials-' + modalId + '-' + loopIndex);
        const card      = qtyInput.closest('.receive-item');
        if (!wrap || !card) return;

        wrap.innerHTML = '';
        if (card.dataset.isPart === '1') return;

        const qty   = parseInt(qtyInput.value, 10) || 0;
        const isSet = card.dataset.isSet === '1';

        if (qty < 1) return;

        if (isSet) {
            wrap.insertAdjacentHTML('beforeend', poReceiveSerialGroup(loopIndex, qty, 'serials', '❄️ Indoor unit — ' + card.dataset.indoorModel));
            wrap.insertAdjacentHTML('beforeend', poReceiveSerialGroup(loopIndex, qty, 'outdoor_serials', '🌀 Outdoor unit — ' + card.dataset.outdoorModel));
        } else {
            wrap.insertAdjacentHTML('beforeend', poReceiveSerialGroup(loopIndex, qty, 'serials', ''));
        }
    };

    // Moves the shared "new PO" section to sit right under the last-checked item (instead of
    // always at the bottom), and autofocuses its PO number field the moment it first appears.
    function poReceiveUpdateSplitSection(form, tryFocus) {
        const section = form.querySelector('.split-po-section');
        if (!section) return;

        const checkedRows = Array.from(form.querySelectorAll('.split-remainder-checkbox:checked'))
            .map(function (cb) { return cb.closest('.receive-item'); })
            .filter(Boolean);

        const poNumberInput = section.querySelector('input[name="new_po_supplier_po_number"]');

        if (checkedRows.length === 0) {
            section.style.display = 'none';
            section.dataset.poSplitVisible = '0';
            if (poNumberInput) poNumberInput.required = false;
            return;
        }

        const lastCheckedRow = checkedRows[checkedRows.length - 1];
        if (lastCheckedRow.nextElementSibling !== section) {
            lastCheckedRow.insertAdjacentElement('afterend', section);
        }

        const wasHidden = section.dataset.poSplitVisible !== '1';
        section.style.display = '';
        section.dataset.poSplitVisible = '1';

        if (poNumberInput) {
            poNumberInput.required = true;
            if (tryFocus && wasHidden) poNumberInput.focus();
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

            form.querySelectorAll('.split-remainder-checkbox').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    poReceiveUpdateSplitSection(form, checkbox.checked);
                });
            });
            poReceiveUpdateSplitSection(form, false);
        });
    }

    function bindDueReceiveDismiss(modalEl) {
        if (modalEl.id === 'receiveModal') return;

        modalEl.querySelectorAll('[data-po-due-receive-dismiss], .btn-close').forEach(function (btn) {
            btn.addEventListener('click', window.poDueReceiveSnoozeSession);
        });

        modalEl.addEventListener('hidden.bs.modal', function (e) {
            if (e.target !== modalEl) return;
            if (modalEl.dataset.submitting === '1') return;
            window.poDueReceiveSnoozeSession();
        });

        const form = modalEl.querySelector('.po-receive-form');
        if (form) {
            form.addEventListener('submit', function () {
                modalEl.dataset.submitting = '1';
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        poReceiveInitForms();

        document.querySelectorAll('.po-due-receive-modal').forEach(bindDueReceiveDismiss);

        // Auto-open once per browser session (reopening the app), not on every sidebar navigation.
        if (window.location.hash !== '#receive' && !isDueReceiveSnoozed()) {
            const modalEl = document.querySelector('.po-due-receive-modal.auto-open-due-receive');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
                window.poDueReceiveSnoozeSession();
            }
        }

        if (window.location.hash === '#receive') {
            const modalEl = document.getElementById('receiveModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }
    });
})();
</script>
@endpush
@endonce
