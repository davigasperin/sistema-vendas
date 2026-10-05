<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0.01'],
            'due_date' => ['sometimes', 'date'],
            'paid_date' => ['nullable', 'date', 'after_or_equal:due_date'],
            'category_id' => ['sometimes', 'integer', 'exists:expense_categories,id'],
            'type' => ['sometimes', 'string', Rule::in([Expense::TYPE_EXPENSE, Expense::TYPE_INCOME])],
            'status' => ['sometimes', 'string', Rule::in([Expense::STATUS_PENDING, Expense::STATUS_PAID, Expense::STATUS_OVERDUE, Expense::STATUS_CANCELLED])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.max' => 'A descrição não pode exceder 255 caracteres.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'due_date.date' => 'Data de vencimento inválida.',
            'paid_date.after_or_equal' => 'A data de pagamento deve ser posterior ou igual ao vencimento.',
            'category_id.exists' => 'Categoria inválida.',
            'type.in' => 'Tipo inválido.',
            'status.in' => 'Status inválido.',
        ];
    }
}
