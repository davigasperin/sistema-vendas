<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
            'category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'type' => ['required', 'string', Rule::in([Expense::TYPE_EXPENSE, Expense::TYPE_INCOME])],
            'status' => ['sometimes', 'string', Rule::in([Expense::STATUS_PENDING, Expense::STATUS_PAID, Expense::STATUS_OVERDUE, Expense::STATUS_CANCELLED])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'A descrição é obrigatória.',
            'description.max' => 'A descrição não pode exceder 255 caracteres.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'due_date.required' => 'A data de vencimento é obrigatória.',
            'due_date.date' => 'Data de vencimento inválida.',
            'category_id.required' => 'A categoria é obrigatória.',
            'category_id.exists' => 'Categoria inválida.',
            'type.required' => 'O tipo é obrigatório.',
            'type.in' => 'Tipo inválido.',
        ];
    }
}
