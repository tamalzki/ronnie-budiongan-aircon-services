@extends('layouts.app')

@section('title', 'Daily Customers')

@section('content')
<div class="container-fluid">

    <x-page-header title="Daily Customers" subtitle="Walk-in / day-to-day service customers" icon="bi-person-lines-fill">
        <x-slot name="actions">
            <button type="button" class="btn btn-primary btn-sm shadow-sm" onclick="openDailyCustomerModal()">
                <i class="bi bi-plus-circle"></i> New Customer Sale
            </button>
        </x-slot>
    </x-page-header>

    <x-flash />

    {{-- Summary cards --}}
    <div class="row g-2 mb-2">
        <div class="col-6 col-lg-4">
            <div class="card app-card-panel border-0 shadow-sm">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                        <i class="bi bi-calendar-day text-primary" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 text-truncate">
                        <span class="text-muted small">Services Today:</span>
                        <span class="fw-bold">{{ $todayCount }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-4">
            <div class="card app-card-panel border-0 shadow-sm {{ $unpaidCount > 0 ? 'border-start border-danger border-3' : '' }}">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <div class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                        <i class="bi bi-exclamation-circle text-danger" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 text-truncate">
                        <span class="text-muted small">Total Unpaid:</span>
                        <span class="fw-bold text-danger">₱{{ number_format($unpaidAmount, 2) }}</span>
                        <span class="text-muted small">({{ $unpaidCount }})</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-4">
            <div class="card app-card-panel border-0 shadow-sm">
                <div class="card-body py-2 px-3 d-flex align-items-center gap-2">
                    <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
                        <i class="bi bi-check-circle text-success" aria-hidden="true"></i>
                    </div>
                    <div class="min-w-0 text-truncate">
                        <span class="text-muted small">Total Paid:</span>
                        <span class="fw-bold text-success">₱{{ number_format($paidAmount, 2) }}</span>
                        <span class="text-muted small">({{ $paidCount }})</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Search & Filters --}}
    <div class="card app-card-panel mb-2 app-filter-toolbar">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('daily-customers.index') }}" id="dcFilterForm" class="row g-2 align-items-center">
                <div class="col-md-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0"
                               placeholder="Search customer or service..." id="dcSearchInput">
                    </div>
                </div>
                <input type="hidden" name="status" id="dcStatusInput" value="{{ $status }}">
                <div class="col-auto d-flex gap-1 ms-auto">
                    <button type="button" onclick="dcSetStatus('unpaid')" class="btn btn-sm {{ $status === 'unpaid' ? 'btn-danger' : 'btn-outline-danger' }}">Unpaid</button>
                    <button type="button" onclick="dcSetStatus('paid')" class="btn btn-sm {{ $status === 'paid' ? 'btn-success' : 'btn-outline-success' }}">Paid</button>
                    @if($search !== '' || $status)
                    <a href="{{ route('daily-customers.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card app-card-panel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Service Availed</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $entry)
                        <tr>
                            <td class="px-3 py-2 text-nowrap">{{ $entry->service_date->format('M d, Y') }}</td>
                            <td class="px-3 py-2 fw-semibold">{{ $entry->customer_name }}</td>
                            <td class="px-3 py-2">
                                {{ $entry->service_label }}
                                @if($entry->parts->count() > 0)
                                    <span class="badge ms-1" style="background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;font-size:0.63rem;"
                                          title="{{ $entry->parts->map(fn($p) => $p->quantity . '× ' . $p->part->name)->implode(', ') }}">
                                        🔧 {{ $entry->parts->count() }} aircon part{{ $entry->parts->count() == 1 ? '' : 's' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-end">₱{{ number_format($entry->amount, 2) }}</td>
                            <td class="px-3 py-2">
                                <span class="badge {{ $entry->status === 'paid' ? 'bg-success' : 'bg-danger' }}">
                                    {{ ucfirst($entry->status) }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="app-act-wrap">
                                    @php $next = $entry->status === 'paid' ? 'unpaid' : 'paid'; @endphp
                                    <form action="{{ route('daily-customers.update-status', $entry) }}" method="POST" class="app-act-form"
                                          onsubmit="return confirm('Mark {{ $entry->customer_name }}\'s service as {{ ucfirst($next) }}?')">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="{{ $next }}">
                                        <button type="submit" class="btn app-act {{ $next === 'paid' ? 'btn-success text-white' : 'btn-warning text-dark' }}"
                                                title="Mark as {{ ucfirst($next) }}">
                                            <i class="bi bi-arrow-repeat"></i> Mark {{ ucfirst($next) }}
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-light border app-act"
                                            onclick='openDailyCustomerModal({!! json_encode([
                                                "id" => $entry->id,
                                                "customer_name" => $entry->customer_name,
                                                "service_type" => $entry->service_type,
                                                "other_service" => $entry->other_service,
                                                "amount" => (float) $entry->amount,
                                                "status" => $entry->status,
                                                "service_date" => $entry->service_date->format("Y-m-d"),
                                                "notes" => $entry->notes,
                                                "parts" => $entry->parts->map(fn($p) => ["part_id" => $p->part_id, "name" => $p->part->name, "quantity" => $p->quantity]),
                                                "parts_included_in_price" => $entry->parts_included_in_price,
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) !!})'>
                                        <i class="bi bi-pencil"></i><span class="act-label"> Edit</span>
                                    </button>
                                    <form action="{{ route('daily-customers.destroy', $entry) }}" method="POST" class="app-act-form"
                                          onsubmit="return confirm('Delete this entry for {{ $entry->customer_name }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light border app-act text-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                {{ $search !== '' || $status ? 'No entries match your search.' : 'No daily customer entries yet.' }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($entries->hasPages())
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top bg-light">
                <small class="text-muted">
                    Showing {{ $entries->firstItem() }}–{{ $entries->lastItem() }} of {{ $entries->total() }} entries
                </small>
                {{ $entries->links() }}
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Add / Edit Modal --}}
<div class="modal fade" id="dailyCustomerModal" tabindex="-1" aria-labelledby="dailyCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="dailyCustomerForm" method="POST" action="{{ route('daily-customers.store') }}">
                @csrf
                <input type="hidden" name="_method" id="dcFormMethod" value="POST">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title" id="dailyCustomerModalLabel"><i class="bi bi-person-plus me-2"></i>Add Daily Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Customer Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('customer_name') is-invalid @enderror"
                               name="customer_name" id="dcCustomerName" value="{{ old('customer_name') }}" required>
                        @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Service Availed <span class="text-danger">*</span></label>
                        <div class="combobox position-relative" id="dcServiceCombo">
                            <div class="form-control form-control-sm d-flex justify-content-between align-items-center"
                                 style="cursor:pointer;user-select:none;" onclick="toggleDcServiceCombo()">
                                <span id="dcServiceDisplay" class="text-muted" style="font-size:0.82rem;">-- Select Service --</span>
                                <i class="bi bi-chevron-down" style="font-size:0.7rem;color:#888;flex-shrink:0;"></i>
                            </div>
                            <div id="dcServicePanel" class="position-absolute w-100 bg-white border rounded shadow-sm"
                                 style="display:none;z-index:9999;top:100%;left:0;max-height:260px;overflow:hidden;">
                                <div class="p-2 border-bottom">
                                    <input type="text" class="form-control form-control-sm" id="dcServiceSearch"
                                           placeholder="🔍 Search…" oninput="filterDcServiceOptions()" onclick="event.stopPropagation()">
                                </div>
                                <div id="dcServiceOptions" style="max-height:200px;overflow-y:auto;">
                                    @foreach($serviceTypes as $type)
                                    <div class="dc-service-option px-3 py-2" style="cursor:pointer;font-size:0.82rem;"
                                         data-value="{{ $type }}"
                                         onmouseenter="this.style.background='#f0f4ff'" onmouseleave="this.style.background=''"
                                         onclick="pickDcService(this)">{{ $type }}</div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="service_type" id="dc_service_type" value="{{ old('service_type') }}" required>
                        @error('service_type')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-2" id="dcOtherServiceWrap" style="display:none;">
                        <label class="form-label small fw-semibold">Specify Service <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm @error('other_service') is-invalid @enderror"
                               name="other_service" id="dc_other_service" value="{{ old('other_service') }}" placeholder="Describe the service performed">
                        @error('other_service')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <label class="form-label small fw-semibold mb-0"><i class="bi bi-nut"></i> Aircon Parts Used (optional)</label>
                            <button type="button" class="btn btn-outline-warning btn-sm" style="font-size:0.72rem;padding:1px 8px;" onclick="addDcPartRow()">
                                <i class="bi bi-plus-circle"></i> Add Aircon Part
                            </button>
                        </div>
                        <div id="dcPartsRows" class="mt-1"></div>
                    </div>

                    <div class="mb-2" id="dcPartsIncludedWrap" style="display:none;">
                        <label class="form-label small fw-semibold d-block">Aircon parts included in the price? <span class="text-danger">*</span></label>
                        <input type="hidden" id="dc_parts_included_in_price" name="parts_included_in_price" value="">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-success" onclick="setDcPartsIncluded(1)" id="dcPartsIncludedYes">Yes</button>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="setDcPartsIncluded(0)" id="dcPartsIncludedNo">No</button>
                        </div>
                        @error('parts_included_in_price')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold" id="dcAmountLabel">Amount (₱)</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">₱</span>
                                <input type="number" step="0.01" min="0" class="form-control" name="amount" id="dc_amount" value="{{ old('amount') }}" placeholder="0.00">
                            </div>
                            @error('amount')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm @error('service_date') is-invalid @enderror"
                                   name="service_date" id="dc_service_date" value="{{ old('service_date') }}" required>
                            @error('service_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="mb-2 mt-2">
                        <label class="form-label small fw-semibold">Payment Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm @error('status') is-invalid @enderror" name="status" id="dc_status" required>
                            <option value="" {{ old('status') ? '' : 'selected' }} disabled>-- Select Status --</option>
                            <option value="unpaid" {{ old('status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        </select>
                        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-1">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea class="form-control form-control-sm" name="notes" id="dc_notes" rows="2" placeholder="Optional notes">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="dcSubmitBtn"><i class="bi bi-check2"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
const dcParts = {!! json_encode($partsJson) !!};
let dcPartIndex = 0;

let dcSearchTimer;
document.getElementById('dcSearchInput').addEventListener('input', function () {
    clearTimeout(dcSearchTimer);
    dcSearchTimer = setTimeout(() => document.getElementById('dcFilterForm').submit(), 400);
});

function dcSetStatus(value) {
    const input = document.getElementById('dcStatusInput');
    input.value = input.value === value ? '' : value;
    document.getElementById('dcFilterForm').submit();
}

function toggleDcServiceCombo() {
    const panel = document.getElementById('dcServicePanel');
    const open = panel.style.display !== 'none';
    panel.style.display = open ? 'none' : '';
    if (!open) document.getElementById('dcServiceSearch')?.focus();
}

function filterDcServiceOptions() {
    const term = document.getElementById('dcServiceSearch').value.toLowerCase();
    document.querySelectorAll('#dcServiceOptions .dc-service-option').forEach(o => {
        o.style.display = o.textContent.toLowerCase().includes(term) ? '' : 'none';
    });
}

function pickDcService(el) {
    const value = el.dataset.value;
    document.getElementById('dc_service_type').value = value;
    const disp = document.getElementById('dcServiceDisplay');
    disp.textContent = value;
    disp.classList.remove('text-muted');
    document.getElementById('dcServicePanel').style.display = 'none';
    document.getElementById('dcServiceSearch').value = '';
    filterDcServiceOptions();
    toggleDcOtherService(value);
}

function toggleDcOtherService(value) {
    const wrap = document.getElementById('dcOtherServiceWrap');
    const input = document.getElementById('dc_other_service');
    if (value === 'Others') {
        wrap.style.display = '';
        input.required = true;
    } else {
        wrap.style.display = 'none';
        input.required = false;
        input.value = '';
    }
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('#dcServiceCombo')) {
        document.getElementById('dcServicePanel').style.display = 'none';
    }
});

/* ── Parts Used ── */
function addDcPartRow(prefill) {
    const idx = dcPartIndex++;

    const options = dcParts.map(p => {
        const linked = p.linked_model_label ? ` (${p.linked_model_label})` : '';
        return `<option value="${p.id}" data-stock="${p.stock_quantity}">${p.name}${linked} — stock ${p.stock_quantity}</option>`;
    }).join('');

    const html = `
    <div class="dc-part-row mb-1" data-idx="${idx}">
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm dc-part-select" name="parts[${idx}][part_id]" onchange="onDcPartChange(${idx})" style="flex:1;">
                <option value="">-- Select Part --</option>
                ${options}
            </select>
            <input type="number" class="form-control form-control-sm dc-part-qty" name="parts[${idx}][quantity]"
                   value="1" min="1" style="width:70px;" onchange="onDcPartChange(${idx})">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeDcPartRow(${idx})">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <div class="dc-part-warning text-warning small mt-1" style="display:none;"></div>
    </div>`;

    document.getElementById('dcPartsRows').insertAdjacentHTML('beforeend', html);

    if (prefill) {
        const row = document.querySelector(`.dc-part-row[data-idx="${idx}"]`);
        row.querySelector('.dc-part-select').value = prefill.part_id || '';
        row.querySelector('.dc-part-qty').value = prefill.quantity || 1;
    }

    onDcPartChange(idx);
    toggleDcPartsIncludedWrap();
}

function removeDcPartRow(idx) {
    document.querySelector(`.dc-part-row[data-idx="${idx}"]`)?.remove();
    toggleDcPartsIncludedWrap();
}

function onDcPartChange(idx) {
    const row    = document.querySelector(`.dc-part-row[data-idx="${idx}"]`);
    const select = row.querySelector('.dc-part-select');
    const qty    = parseInt(row.querySelector('.dc-part-qty').value) || 0;
    const stock  = parseInt(select.selectedOptions[0]?.dataset.stock ?? 0);
    const warning = row.querySelector('.dc-part-warning');

    if (select.value && qty > stock) {
        warning.textContent = `⚠ Only ${stock} in stock`;
        warning.style.display = '';
    } else {
        warning.style.display = 'none';
    }
}

function toggleDcPartsIncludedWrap() {
    const hasParts = document.querySelectorAll('.dc-part-row').length > 0;
    document.getElementById('dcPartsIncludedWrap').style.display = hasParts ? '' : 'none';
    if (!hasParts) setDcPartsIncluded('');
    updateDcAmountUI();
}

function updateDcAmountUI() {
    const hasParts = document.querySelectorAll('.dc-part-row').length > 0;
    const label = document.getElementById('dcAmountLabel');
    const input = document.getElementById('dc_amount');

    if (hasParts) {
        label.innerHTML = 'Amount including parts (₱) <span class="text-danger">*</span>';
        input.required = true;
        if (!input.value || parseFloat(input.value) === 0) {
            input.value = '0';
        }
    } else {
        label.textContent = 'Amount (₱)';
        input.required = false;
    }
}

function setDcPartsIncluded(value) {
    document.getElementById('dc_parts_included_in_price').value = value;
    document.getElementById('dcPartsIncludedYes').classList.toggle('btn-success', value === 1 || value === '1');
    document.getElementById('dcPartsIncludedYes').classList.toggle('btn-outline-success', !(value === 1 || value === '1'));
    document.getElementById('dcPartsIncludedYes').classList.toggle('text-white', value === 1 || value === '1');
    document.getElementById('dcPartsIncludedNo').classList.toggle('btn-danger', value === 0 || value === '0');
    document.getElementById('dcPartsIncludedNo').classList.toggle('btn-outline-danger', !(value === 0 || value === '0'));
    document.getElementById('dcPartsIncludedNo').classList.toggle('text-white', value === 0 || value === '0');
}

function resetDailyCustomerForm() {
    const form = document.getElementById('dailyCustomerForm');
    form.reset();
    form.action = "{{ route('daily-customers.store') }}";
    document.getElementById('dcFormMethod').value = 'POST';
    document.getElementById('dailyCustomerModalLabel').innerHTML = '<i class="bi bi-person-plus me-2"></i>Add Daily Customer';
    document.getElementById('dc_service_type').value = '';
    const disp = document.getElementById('dcServiceDisplay');
    disp.textContent = '-- Select Service --';
    disp.classList.add('text-muted');
    document.getElementById('dc_service_date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('dc_status').value = '';
    document.getElementById('dc_amount').value = '';
    toggleDcOtherService('');

    document.getElementById('dcPartsRows').innerHTML = '';
    dcPartIndex = 0;
    setDcPartsIncluded('');
    toggleDcPartsIncludedWrap();
}

function openDailyCustomerModal(entry) {
    resetDailyCustomerForm();

    if (entry) {
        document.getElementById('dailyCustomerModalLabel').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Daily Customer';
        document.getElementById('dailyCustomerForm').action = '/daily-customers/' + entry.id;
        document.getElementById('dcFormMethod').value = 'PUT';

        document.getElementById('dcCustomerName').value = entry.customer_name || '';
        document.getElementById('dc_amount').value = entry.amount || 0;
        document.getElementById('dc_service_date').value = entry.service_date;
        document.getElementById('dc_status').value = entry.status || 'unpaid';
        document.getElementById('dc_notes').value = entry.notes || '';

        document.getElementById('dc_service_type').value = entry.service_type || '';
        const disp = document.getElementById('dcServiceDisplay');
        disp.textContent = entry.service_type || '-- Select Service --';
        disp.classList.toggle('text-muted', !entry.service_type);

        toggleDcOtherService(entry.service_type);
        if (entry.service_type === 'Others') {
            document.getElementById('dc_other_service').value = entry.other_service || '';
        }

        (entry.parts || []).forEach(p => addDcPartRow({ part_id: p.part_id, quantity: p.quantity }));
        if (entry.parts_included_in_price !== null && entry.parts_included_in_price !== undefined) {
            setDcPartsIncluded(entry.parts_included_in_price ? 1 : 0);
        }
    }

    bootstrap.Modal.getOrCreateInstance(document.getElementById('dailyCustomerModal')).show();
}

document.getElementById('dailyCustomerForm').addEventListener('submit', function (e) {
    let invalidPart = false;
    document.querySelectorAll('.dc-part-row').forEach(row => {
        if (!row.querySelector('.dc-part-select').value) invalidPart = true;
    });
    if (invalidPart) {
        e.preventDefault(); alert('Please select an aircon part for each parts-used row, or remove the empty row.'); return;
    }

    const hasParts = document.querySelectorAll('.dc-part-row').length > 0;
    if (hasParts && document.getElementById('dc_parts_included_in_price').value === '') {
        e.preventDefault(); alert('Please specify whether the aircon parts used are included in the price.'); return;
    }

    if (hasParts) {
        const amount = parseFloat(document.getElementById('dc_amount').value) || 0;
        if (amount <= 0) {
            e.preventDefault(); alert('Please enter the amount including parts — it must be greater than ₱0 when aircon parts are used.'); return;
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const dateInput = document.getElementById('dc_service_date');
    if (!dateInput.value) dateInput.value = new Date().toISOString().slice(0, 10);

    const searchInput = document.getElementById('dcSearchInput');
    if (searchInput.value) {
        searchInput.focus();
        searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
    }

    const oldServiceType = @json(old('service_type'));
    if (oldServiceType) {
        const disp = document.getElementById('dcServiceDisplay');
        disp.textContent = oldServiceType;
        disp.classList.remove('text-muted');
        toggleDcOtherService(oldServiceType);
    }

    @if(old('parts'))
        @foreach(array_values(old('parts')) as $oldPart)
            addDcPartRow({ part_id: {{ (int) ($oldPart['part_id'] ?? 0) }}, quantity: {{ (int) ($oldPart['quantity'] ?? 1) }} });
        @endforeach
        @if(old('parts_included_in_price') !== null)
            setDcPartsIncluded({{ old('parts_included_in_price') ? 1 : 0 }});
        @endif
    @endif

    @if($errors->any())
        bootstrap.Modal.getOrCreateInstance(document.getElementById('dailyCustomerModal')).show();
    @endif
});
</script>
@endpush

@endsection
