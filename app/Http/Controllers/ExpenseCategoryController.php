<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(ExpenseCategory::class, 'expense_category', ['except' => ['show']]);
    }

    public function index()
    {
        $categories = ExpenseCategory::withCount('operationExpenses')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('expense-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('expense-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255', 'unique:expense_categories,name'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        ExpenseCategory::create($validated);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(ExpenseCategory $expense_category)
    {
        return view('expense-categories.edit', ['category' => $expense_category]);
    }

    public function update(Request $request, ExpenseCategory $expense_category)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255', 'unique:expense_categories,name,' . $expense_category->id],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);

        $validated['sort_order'] = $validated['sort_order'] ?? $expense_category->sort_order;

        $expense_category->update($validated);

        return redirect()->route('expense-categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        if ($expense_category->operationExpenses()->exists()) {
            return redirect()->route('expense-categories.index')
                ->with('error', 'Cannot delete a category that has operational expenses. Reassign or delete those entries first.');
        }

        $expense_category->delete();

        return redirect()->route('expense-categories.index')
            ->with('success', 'Category deleted.');
    }
}
