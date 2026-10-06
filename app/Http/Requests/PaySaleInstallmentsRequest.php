<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaySaleInstallmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'installment_ids' => ['required', 'array', 'min:1'],
            'installment_ids.*' => ['required', 'integer', 'distinct', 'exists:sale_installments,id'],
            'payment_method_id' => [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->where('active', true)->whereNull('deleted_at'),
            ],
            'paid_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
