<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (! $request->user()?->canManageExpenses()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'in:expense,income'],
            'color' => ['nullable', 'string', 'max:7', 'regex:/^#([a-fA-F0-9]{3}){1,2}$/'],
        ]);

        $category = ExpenseCategory::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'color' => $validated['color'] ?? '#64748b',
        ]);

        return response()->json($category, 201);
    }
}
