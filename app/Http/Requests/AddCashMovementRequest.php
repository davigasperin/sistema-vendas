<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddCashMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSales() ?? false;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['supply', 'bleed'])],
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Informe o tipo de movimentação.',
            'type.in' => 'Tipo de movimentação inválido.',
            'amount.required' => 'Informe o valor da movimentação.',
            'amount.gt' => 'O valor deve ser maior que zero.',
            'reason.required' => 'Informe a justificativa da movimentação.',
        ];
    }
}
