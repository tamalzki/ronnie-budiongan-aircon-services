@extends('layouts.app')

@section('title', 'Operational expenses')

@section('content')
<div class="container-fluid">

    <x-page-header title="Operational expenses" subtitle="Day-to-day operational costs" icon="bi-receipt-cutoff">
        <x-slot name="actions">
            <a href="{{ route('reports.index', ['report' => 'expenses', 'start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]) }}" class="btn btn-outline-primary btn-sm shadow-sm me-1">
                <i class="bi bi-graph-up"></i> Operational expense report
            </a>
            <a href="{{ route('operation-expenses.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> Add Expense
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="card app-card-panel mb-3 app-filter-toolbar">
        <div class="card-body py-2">
            <form method="get" action="{{ route('operation-expenses.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6 col-lg-4">
                    <label class="form-label small text-muted mb-0">Search description</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Keywords…">
                </div>
                <div class="col-auto d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search"></i> Search</button>
                    @if(request()->filled('q'))
                    <a href="{{ route('operation-expenses.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card-panel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th>Recorded by</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $row)
                        <tr>
                            <td class="px-3 py-2 text-nowrap">{{ $row->expense_date->format('M d, Y') }}</td>
                            <td class="px-3 py-2">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary">{{ $row->category->name }}</span>
                            </td>
                            <td class="px-3 py-2">{{ Str::limit($row->description, 80) }}</td>
                            <td class="px-3 py-2 text-end fw-semibold">₱{{ number_format($row->amount, 2) }}</td>
                            <td class="px-3 py-2 text-muted small">{{ $row->user->name ?? '—' }}</td>
                            <td>
                                <div class="app-act-wrap">
                                    <a href="{{ route('operation-expenses.edit', $row) }}" class="btn btn-light border app-act">
                                        <i class="bi bi-pencil"></i><span class="act-label"> Edit</span>
                                    </a>
                                    <form action="{{ route('operation-expenses.destroy', $row) }}" method="POST" class="app-act-form" onsubmit="return confirm('Delete this operational expense?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light border app-act text-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">{{ request()->filled('q') ? 'No operational expenses match your search.' : 'No operational expenses yet.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($expenses->hasPages())
        <div class="card-footer bg-white border-top-0 py-2">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>
@endsection
