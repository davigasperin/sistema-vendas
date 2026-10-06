<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaySaleInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('saleInstallment')) ?? false;
    }

    public function rules(): array
    {
        return [
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')
                    ->where('active', true)
                    ->whereNull('deleted_at'),
            ],
            'paid_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'Informe o método de pagamento.',
            'payment_method_id.exists' => 'Método de pagamento inativo ou inexistente.',
            'paid_date.date' => 'A data do recebimento é inválida.',
        ];
    }
}
