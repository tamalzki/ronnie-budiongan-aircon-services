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
                <table class="table table-hover table-sm mb-0" style="font-size:0.875rem;">
                    <thead class="bg-light">
                        <tr>
                            <th class="border-0 px-3 py-2">Name</th>
                            <th class="border-0 px-3 py-2 text-center">Sort</th>
                            <th class="border-0 px-3 py-2 text-center">Expenses</th>
                            <th class="border-0 px-3 py-2">Actions</th>
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
                            <td class="px-3 py-2">
                                <a href="{{ route('expense-categories.edit', $cat) }}" class="btn btn-outline-primary btn-sm py-0" style="font-size:0.78rem">
                                    <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('expense-categories.destroy', $cat) }}" method="POST" class="d-inline"
                                      onsubmit="return confirm('Delete this category?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-0" style="font-size:0.78rem"
                                            @if($cat->operation_expenses_count > 0) disabled title="Remove or reassign expenses first" @endif>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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
