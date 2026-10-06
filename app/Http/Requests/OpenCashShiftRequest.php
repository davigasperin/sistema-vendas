<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenCashShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSales() ?? false;
    }

    public function rules(): array
    {
        return [
            'initial_amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'initial_amount.required' => 'Informe o fundo de troco da abertura.',
            'initial_amount.numeric' => 'O fundo de troco deve ser um valor numérico.',
            'initial_amount.min' => 'O fundo de troco não pode ser negativo.',
        ];
    }
}
