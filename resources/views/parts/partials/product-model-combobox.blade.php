@php
    $fieldName = $fieldName ?? 'product_id';
    $inputId = $inputId ?? $fieldName;
    $selectedId = (string) ($selectedId ?? old($fieldName, ''));
    $selectedLabel = '— General / Unlinked —';
    if ($selectedId !== '') {
        $match = collect($productOptions)->first(fn ($o) => (string) $o['id'] === $selectedId);
        if ($match) {
            $selectedLabel = $match['label'];
        }
    }
    $hasError = $hasError ?? false;
@endphp

<div class="mb-3">
    <label for="{{ $inputId }}" class="form-label small fw-semibold mb-1">Linked Model / Set</label>
    <div class="combobox position-relative part-model-combobox" id="partProductCombo">
        <div class="form-control form-control-sm d-flex justify-content-between align-items-center gap-2 part-model-trigger {{ $hasError ? 'is-invalid' : '' }}"
             style="cursor:pointer;user-select:none;" role="button" tabindex="0"
             aria-haspopup="listbox" aria-expanded="false" id="partProductComboTrigger">
            <span id="partProductDisplay" class="{{ $selectedId ? '' : 'text-muted' }}" style="font-size:0.82rem;">{{ $selectedLabel }}</span>
            <i class="bi bi-chevron-down" style="font-size:0.7rem;color:#888;flex-shrink:0;"></i>
        </div>
        <div id="partProductPanel" class="position-absolute w-100 bg-white border rounded shadow-sm part-model-panel"
             style="display:none;z-index:1050;top:100%;left:0;max-height:280px;overflow:hidden;">
            <div class="p-2 border-bottom">
                <input type="text" class="form-control form-control-sm" id="partProductSearch"
                       placeholder="Search model or set…" autocomplete="off">
            </div>
            <div id="partProductOptions" class="part-model-options" style="max-height:220px;overflow-y:auto;" role="listbox">
                <div class="part-product-option px-3 py-2 text-muted" data-value="" data-label="— General / Unlinked —">
                    — General / Unlinked —
                </div>
                @foreach($productOptions as $option)
                <div class="part-product-option px-3 py-2"
                     data-value="{{ $option['id'] }}"
                     data-label="{{ e($option['label']) }}"
                     data-search="{{ strtolower($option['label'] . ' ' . ($option['unit_type_label'] ?? '') . ' ' . ($option['indoor_model'] ?? '') . ' ' . ($option['outdoor_model'] ?? '')) }}">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span style="font-size:0.82rem;">{{ $option['label'] }}</span>
                        @if(!empty($option['is_set']))
                        <span class="badge bg-primary" style="font-size:0.62rem;">Set</span>
                        @elseif(!empty($option['unit_type_label']))
                        <span class="badge bg-secondary" style="font-size:0.62rem;">{{ $option['unit_type_label'] }}</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <input type="hidden" name="{{ $fieldName }}" id="{{ $inputId }}" value="{{ $selectedId }}">
    @if($hasError)
        <div class="invalid-feedback d-block">{{ $errors->first($fieldName) }}</div>
    @endif
    <div class="form-text">Optional — associates this part with an aircon model/set for reference.</div>
</div>

@once
@push('styles')
<style>
    .part-model-option-hover { background: #f0f4ff !important; }
    .part-model-panel .part-product-option { cursor: pointer; font-size: 0.82rem; }
    .part-model-trigger:focus { border-color: #86b7fe; box-shadow: 0 0 0 0.2rem rgba(13,110,253,.25); outline: 0; }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const trigger = document.getElementById('partProductComboTrigger');
    const panel = document.getElementById('partProductPanel');
    const search = document.getElementById('partProductSearch');
    const display = document.getElementById('partProductDisplay');
    const hidden = document.getElementById('product_id');

    if (!trigger || !panel || !hidden) return;

    function setOpen(open) {
        panel.style.display = open ? '' : 'none';
        trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            search.value = '';
            filterPartProductOptions();
            setTimeout(function () { search.focus(); }, 0);
        }
    }

    function togglePartProductCombo() {
        setOpen(panel.style.display === 'none');
    }

    function filterPartProductOptions() {
        const term = (search.value || '').toLowerCase().trim();
        document.querySelectorAll('#partProductOptions .part-product-option').forEach(function (opt) {
            const haystack = (opt.dataset.search || opt.dataset.label || opt.textContent || '').toLowerCase();
            opt.style.display = !term || haystack.includes(term) ? '' : 'none';
        });
    }

    function pickPartProduct(opt) {
        const value = opt.dataset.value || '';
        const label = opt.dataset.label || opt.textContent.trim();
        hidden.value = value;
        display.textContent = label;
        display.classList.toggle('text-muted', !value);
        setOpen(false);
    }

    trigger.addEventListener('click', togglePartProductCombo);
    trigger.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            togglePartProductCombo();
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    });

    search.addEventListener('input', filterPartProductOptions);
    search.addEventListener('click', function (e) { e.stopPropagation(); });
    search.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') setOpen(false);
    });

    document.querySelectorAll('#partProductOptions .part-product-option').forEach(function (opt) {
        opt.addEventListener('mouseenter', function () { opt.classList.add('part-model-option-hover'); });
        opt.addEventListener('mouseleave', function () { opt.classList.remove('part-model-option-hover'); });
        opt.addEventListener('click', function () { pickPartProduct(opt); });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#partProductCombo')) setOpen(false);
    });
});
</script>
@endpush
@endonce
