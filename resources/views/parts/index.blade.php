@extends('layouts.app')

@section('title', 'Aircon Parts')

@section('content')
<div class="container-fluid">

    <x-page-header title="Aircon Parts" subtitle="Manage aircon parts catalog and stock" icon="bi-nut">
        <x-slot name="actions">
            <a href="{{ route('parts.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> Add Aircon Part
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    {{-- Search & Filters --}}
    <div class="card app-card-panel mb-3 app-filter-toolbar">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="partSearch" class="form-control border-start-0"
                               placeholder="Search aircon part name or linked model...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select id="statusFilter" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary btn-sm" onclick="clearFilters()" title="Clear">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card app-card-panel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table" id="partsTable">
                    <thead>
                        <tr>
                            <th>Aircon Part Name</th>
                            <th>Linked Model / Set</th>
                            <th class="text-end">Cost</th>
                            <th class="text-end">Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="partsTableBody">
                        @forelse($parts as $part)
                        @php $stock = $part->stock_quantity; @endphp
                        <tr class="part-row"
                            data-href="{{ route('parts.show', $part) }}"
                            style="cursor:pointer;"
                            data-search="{{ strtolower($part->name . ' ' . ($part->linked_model_label ?? '')) }}"
                            data-status="{{ $part->is_active ? 'active' : 'inactive' }}">
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $part->name }}</td>
                            <td class="px-3 py-2">
                                @if($part->linked_model_label)
                                    <span class="text-muted">{{ $part->linked_model_label }}</span>
                                @else
                                    <span class="text-muted">General / Unlinked</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 fw-semibold text-success text-end" style="white-space:nowrap">
                                ₱{{ number_format($part->cost, 2) }}
                            </td>
                            <td class="px-3 py-2 text-end" style="white-space:nowrap">
                                <span class="badge {{ $stock <= 0 ? 'bg-danger' : 'bg-light text-dark border' }}">
                                    {{ $stock }}
                                </span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge {{ $part->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $part->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="white-space:nowrap">
                                <div class="app-act-wrap">
                                    <button type="button" class="btn btn-light border app-act text-success"
                                            data-bs-toggle="modal" data-bs-target="#stockInModal{{ $part->id }}">
                                        <i class="bi bi-box-arrow-in-down"></i><span class="act-label"> Stock In</span>
                                    </button>
                                    <a href="{{ route('parts.edit', $part) }}" class="btn btn-light border app-act">
                                        <i class="bi bi-pencil"></i><span class="act-label"> Edit</span>
                                    </a>
                                    <form action="{{ route('parts.destroy', $part) }}" method="POST"
                                          class="app-act-form" onsubmit="return confirm('Delete this aircon part?')">
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
                                No aircon parts yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- Stock In Modals --}}
@foreach($parts as $part)
<div class="modal fade" id="stockInModal{{ $part->id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('parts.stock-in', $part) }}" method="POST">
                @csrf
                <div class="modal-header bg-success text-white border-0">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-down"></i> Stock In — {{ $part->name }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="alert alert-info border-0 mb-3 py-2 small">
                        <i class="bi bi-info-circle"></i> Current stock: <strong>{{ $part->stock_quantity }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number" class="form-control form-control-sm" name="quantity" min="1" required placeholder="Enter quantity">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold mb-1">Notes</label>
                        <textarea class="form-control form-control-sm" name="notes" rows="2" placeholder="Optional notes"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="bi bi-check-circle"></i> Add Stock
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@push('scripts')
<script>
function clearFilters() {
    document.getElementById('partSearch').value = '';
    document.getElementById('statusFilter').value = '';
    filterTable();
}

function filterTable() {
    const search = document.getElementById('partSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows   = document.querySelectorAll('.part-row');
    let visible  = 0;

    rows.forEach(row => {
        const matchSearch = !search || row.dataset.search.includes(search);
        const matchStatus = !status || row.dataset.status === status;

        if (matchSearch && matchStatus) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    let noResults = document.getElementById('noResultsRow');
    if (visible === 0) {
        if (!noResults) {
            noResults = document.createElement('tr');
            noResults.id = 'noResultsRow';
            noResults.innerHTML = '<td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('partsTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('partSearch').addEventListener('input', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);

    document.querySelectorAll('.part-row[data-href]').forEach(row => {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, form, input, select, .modal')) return;
            window.location.href = this.dataset.href;
        });
    });
});
</script>
@endpush

@endsection
