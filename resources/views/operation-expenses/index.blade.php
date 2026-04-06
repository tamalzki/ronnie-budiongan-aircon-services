@extends('layouts.app')

@section('title', 'Operational expenses')

@section('content')
<div class="container-fluid">

    <x-page-header title="Operational expenses" subtitle="Day-to-day operational costs" icon="bi-receipt-cutoff">
        <x-slot name="actions">
            <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm me-1">
                <i class="bi bi-folder2"></i> Categories
            </a>
            <a href="{{ route('reports.index', ['report' => 'expenses', 'start_date' => request('from', now()->startOfMonth()->format('Y-m-d')), 'end_date' => request('to', now()->format('Y-m-d'))]) }}" class="btn btn-outline-primary btn-sm shadow-sm me-1">
                <i class="bi bi-graph-up"></i> Operational expense report
            </a>
            <a href="{{ route('operation-expenses.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> Add operational expense
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="card app-card-panel mb-3 app-filter-toolbar">
        <div class="card-body py-2">
            <form method="get" action="{{ route('operation-expenses.index') }}" class="row g-2 align-items-end">
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-0">From</label>
                    <input type="date" name="from" class="form-control form-control-sm" value="{{ request('from') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-0">To</label>
                    <input type="date" name="to" class="form-control form-control-sm" value="{{ request('to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-0">Search description</label>
                    <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Keywords…">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i> Filter</button>
                    <a href="{{ route('operation-expenses.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-card-panel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0 app-table-compact">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Date</th>
                            <th class="border-0 px-3 py-2">Category</th>
                            <th class="border-0 px-3 py-2">Description</th>
                            <th class="border-0 px-3 py-2 text-end">Amount</th>
                            <th class="border-0 px-3 py-2">Recorded by</th>
                            <th class="border-0 px-3 py-2">Actions</th>
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
                            <td class="px-3 py-2">
                                <a href="{{ route('operation-expenses.edit', $row) }}" class="btn btn-outline-primary btn-sm py-0" style="font-size:0.75rem">Edit</a>
                                <form action="{{ route('operation-expenses.destroy', $row) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this operational expense?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0" style="font-size:0.75rem">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-5 text-muted">No operational expenses match your filters.</td></tr>
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
