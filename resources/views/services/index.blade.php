@extends('layouts.app')

@section('title', 'Services')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-tools text-primary"></i> Services</h2>
            <p class="text-muted mb-0">Manage service offerings</p>
        </div>
        <a href="{{ route('services.create') }}" class="btn btn-primary btn-sm shadow-sm">
            <i class="bi bi-plus-circle"></i> Add Service
        </a>
    </div>

    {{-- Search & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
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
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" id="servicesTable" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Service Name</th>
                            <th class="border-0 px-3 py-2">Description</th>
                            <th class="border-0 px-3 py-2">Default Price</th>
                            <th class="border-0 px-3 py-2">Status</th>
                            <th class="border-0 px-3 py-2">Actions</th>
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
                            <td class="px-3 py-2" style="white-space:nowrap">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('services.show', $service) }}"
                                       class="btn btn-outline-primary"
                                       style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="{{ route('services.edit', $service) }}"
                                       class="btn btn-warning"
                                       style="padding:2px 8px;font-size:0.78rem">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>
                                    <form action="{{ route('services.destroy', $service) }}" method="POST"
                                          class="d-inline" onsubmit="return confirm('Delete this service?')">
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