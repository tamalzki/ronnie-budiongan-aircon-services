@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid">

    <x-page-header title="Suppliers" subtitle="Manage supplier information" icon="bi-building">
        <x-slot name="actions">
            <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> Add Supplier
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
    <div class="card app-card-panel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table" id="suppliersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Contact Number</th>
                            <th>Email</th>
                            <th class="text-center">Products</th>
                            <th>Status</th>
                            <th>Actions</th>
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
                            <td style="white-space:nowrap">
                                <div class="app-act-wrap">
                                    <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-light border app-act">
                                        <i class="bi bi-eye"></i><span class="act-label"> View</span>
                                    </a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-light border app-act">
                                        <i class="bi bi-pencil"></i><span class="act-label"> Edit</span>
                                    </a>
                                    <form action="{{ route('suppliers.destroy', $supplier) }}" method="POST"
                                          class="app-act-form" onsubmit="return confirm('Delete this supplier?')">
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