@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="container-fluid">

    <x-page-header title="Services" subtitle="Manage service offerings" icon="bi-tools">
        <x-slot name="actions">
            <a href="{{ route('services.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> Add Service
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
                        <input type="text" id="serviceSearch" class="form-control border-start-0"
                               placeholder="Search service name...">
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
                <table class="table table-hover table-sm align-middle mb-0 app-table" id="servicesTable">
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Description</th>
                            <th class="text-end">Default Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody">
                        @forelse($services as $service)
                        <tr class="service-row"
                            data-search="{{ strtolower($service->name) }}"
                            data-status="{{ $service->is_active ? 'active' : 'inactive' }}">
                            <td class="px-3 py-2 fw-semibold" style="white-space:nowrap">{{ $service->name }}</td>
                            <td class="px-3 py-2">
                                <span class="text-muted">{{ $service->description ? Str::limit($service->description, 60) : '—' }}</span>
                            </td>
                            <td class="px-3 py-2 fw-semibold text-success" style="white-space:nowrap">
                                ₱{{ number_format($service->default_price, 2) }}
                            </td>
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <span class="badge {{ $service->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $service->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td style="white-space:nowrap">
                                <div class="app-act-wrap">
                                    <a href="{{ route('services.show', $service) }}" class="btn btn-light border app-act">
                                        <i class="bi bi-eye"></i><span class="act-label"> View</span>
                                    </a>
                                    <a href="{{ route('services.edit', $service) }}" class="btn btn-light border app-act">
                                        <i class="bi bi-pencil"></i><span class="act-label"> Edit</span>
                                    </a>
                                    <form action="{{ route('services.destroy', $service) }}" method="POST"
                                          class="app-act-form" onsubmit="return confirm('Delete this service?')">
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
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No services yet
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
    document.getElementById('serviceSearch').value = '';
    document.getElementById('statusFilter').value = '';
    filterTable();
}

function filterTable() {
    const search = document.getElementById('serviceSearch').value.toLowerCase();
    const status = document.getElementById('statusFilter').value;
    const rows   = document.querySelectorAll('.service-row');
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
            noResults.innerHTML = '<td colspan="5" class="text-center py-5 text-muted"><i class="bi bi-search fs-1 d-block mb-2"></i>No results found</td>';
            document.getElementById('servicesTableBody').appendChild(noResults);
        }
        noResults.style.display = '';
    } else if (noResults) {
        noResults.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('serviceSearch').addEventListener('input', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
});
</script>
@endpush

@endsection