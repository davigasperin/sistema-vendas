<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canManageSales() ?? false;
    }

    public function rules(): array
    {
        return [
            'reported_amount' => ['required', 'numeric', 'decimal:0,2', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reported_amount.required' => 'Informe o valor contado na gaveta.',
            'reported_amount.numeric' => 'O valor contado deve ser numérico.',
            'reported_amount.min' => 'O valor contado não pode ser negativo.',
        ];
    }
}
