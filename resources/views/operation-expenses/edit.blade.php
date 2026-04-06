@extends('layouts.app')

@section('title', 'Edit operational expense')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <x-page-header title="Edit operational expense" subtitle="Recorded {{ $operation_expense->expense_date->format('M d, Y') }}" icon="bi-pencil" marginClass="mb-3" />
            <x-flash />
            <div class="card app-card-panel">
                <div class="card-body">
                    <form action="{{ route('operation-expenses.update', $operation_expense) }}" method="POST">
                        @csrf @method('PUT')
                        <div class="mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="expense_category_id" required class="form-select @error('expense_category_id') is-invalid @enderror">
                                @foreach($categories as $c)
                                    <option value="{{ $c->id }}" @selected(old('expense_category_id', $operation_expense->expense_category_id) == $c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            @error('expense_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description <span class="text-danger">*</span></label>
                            <textarea name="description" rows="3" required maxlength="5000" class="form-control @error('description') is-invalid @enderror">{{ old('description', $operation_expense->description) }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Amount (₱) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" step="0.01" min="0.01" required
                                       value="{{ old('amount', $operation_expense->amount) }}"
                                       class="form-control @error('amount') is-invalid @enderror">
                                @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expense date <span class="text-danger">*</span></label>
                                <input type="date" name="expense_date" required
                                       value="{{ old('expense_date', $operation_expense->expense_date->format('Y-m-d')) }}"
                                       class="form-control @error('expense_date') is-invalid @enderror">
                                @error('expense_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('operation-expenses.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
