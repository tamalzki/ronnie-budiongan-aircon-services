@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-building text-primary"></i> Suppliers</h2>
            <p class="text-muted mb-0">Manage supplier information</p>
        </div>
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-circle"></i> Add Supplier
        </a>
    </div>

    {{-- Search & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="supplierSearch" class="form-control border-start-0"
                               placeholder="Search name, contact person, email...">
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
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="suppliersTable" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Name</th>
                            <th class="border-0 px-3 py-2">Contact Person</th>
                            <th class="border-0 px-3 py-2">Contact Number</th>
                            <th class="border-0 px-3 py-2">Email</th>
                            <th class="border-0 px-3 py-2 text-center">Products</th>
                            <th class="border-0 px-3 py-2">Status</th>
                            <th class="border-0 px-3 py-2">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTableBody">
                        @forelse($suppliers as $supplier)
                        <tr class="supplier-row"
                            data-search="{{ strtolower($supplier->name . ' ' . ($supplier->contact_person ?? '') . ' ' . ($supplier->email ?? '')) }}"
                            data-status="{{ $supplier->is_active ? 'active' : 'inactive' }}">
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $supplier->name }}</td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-muted">{{ $supplier->contact_person ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-muted">{{ $supplier->contact_number ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="text-muted">{{ $supplier->email ?? '—' }}</span>
                            </td>
                            <td class="px-3 py-2 text-center" style="white-space:nowrap">
                                <span class="badge bg-info">{{ $supplier->products_count }}</span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge {{ $supplier->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('suppliers.show', $supplier) }}"
                                       class="btn btn-outline-primary"
                                       style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}"
                                       class="btn btn-warning"
                                       style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Delete this supplier?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger"
                                                style="padding:2px 8px;font-size:0.78rem">
                                            <i class="bi bi-trash">delete</i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No suppliers yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function clearFilters() {
    document.getElementById('supplierSearch').value = '';
    document.getElementById('statusFilter').value = '';
    filterTable();
}

function filterTable() {
    const search = document.getElementById('supplierSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows   = document.querySelectorAll('.supplier-row');
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
            noResults.innerHTML = '<td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('suppliersTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('supplierSearch').addEventListener('input', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@endsection