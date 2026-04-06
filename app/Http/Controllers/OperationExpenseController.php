<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\OperationExpense;
use Illuminate\Http\Request;

class OperationExpenseController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(OperationExpense::class, 'operation_expense', ['except' => ['show']]);
    }

    public function index(Request $request)
    {
        $query = OperationExpense::with(['category', 'user'])->orderByDesc('expense_date')->orderByDesc('id');

        if ($request->filled('q')) {
            $q = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $request->q) . '%';
            $query->where('description', 'like', $q);
        }

        $expenses = $query->paginate(20)->withQueryString();

        return view('operation-expenses.index', compact('expenses'));
    }

    public function create()
    {
        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('operation-expenses.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'description'         => ['required', 'string', 'max:5000'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'expense_date'        => ['required', 'date'],
        ]);

        $validated['user_id'] = auth()->id();

        OperationExpense::create($validated);

        return redirect()->route('operation-expenses.index')
            ->with('success', 'Operational expense recorded.');
    }

    public function edit(OperationExpense $operation_expense)
    {
        $categories = ExpenseCategory::orderBy('sort_order')->orderBy('name')->get();

        return view('operation-expenses.edit', compact('operation_expense', 'categories'));
    }

    public function update(Request $request, OperationExpense $operation_expense)
    {
        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'description'         => ['required', 'string', 'max:5000'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'expense_date'        => ['required', 'date'],
        ]);

        $operation_expense->update($validated);

        return redirect()->route('operation-expenses.index')
            ->with('success', 'Operational expense updated.');
    }

    public function destroy(OperationExpense $operation_expense)
    {
        $operation_expense->delete();

        return redirect()->route('operation-expenses.index')
            ->with('success', 'Operational expense deleted.');
    }
}
