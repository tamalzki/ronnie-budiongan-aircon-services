@extends('layouts.app')

@section('title', 'Expense categories')

@section('content')
<div class="container-fluid">

    <x-page-header title="Expense categories" subtitle="Used when recording operational expenses" icon="bi-folder2">
        <x-slot name="actions">
            <a href="{{ route('operation-expenses.index') }}" class="btn btn-outline-secondary btn-sm shadow-sm me-1">
                <i class="bi bi-receipt"></i> Operational expenses
            </a>
            <a href="{{ route('expense-categories.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="bi bi-plus-circle"></i> Add category
            </a>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="card app-card-panel">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0 app-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-center">Sort</th>
                            <th class="text-center">Expenses</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $cat)
                        <tr>
                            <td class="px-3 py-2 fw-semibold">{{ $cat->name }}</td>
                            <td class="px-3 py-2 text-center text-muted">{{ $cat->sort_order }}</td>
                            <td class="px-3 py-2 text-center">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $cat->operation_expenses_count }}</span>
                            </td>
                            <td>
                                <div class="app-act-wrap">
                                    <a href="{{ route('expense-categories.edit', $cat) }}" class="btn btn-light border app-act">
                                        <i class="bi bi-pencil"></i><span class="act-label"> Edit</span>
                                    </a>
                                    <form action="{{ route('expense-categories.destroy', $cat) }}" method="POST" class="app-act-form"
                                          onsubmit="return confirm('Delete this category?')">
                                        @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-light border app-act text-danger"
                                            @if($cat->operation_expenses_count > 0) disabled title="Remove or reassign expenses first" @endif>
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center py-4 text-muted">No categories</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
