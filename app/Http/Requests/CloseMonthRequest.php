<?php

namespace App\Http\Requests;

use App\Models\MonthClose;
use Illuminate\Foundation\Http\FormRequest;

class CloseMonthRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('close', MonthClose::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'year' => $this->route('year'),
            'month' => $this->route('month'),
        ]);
    }

    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'between:2000,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ];
    }

    public function messages(): array
    {
        return [
            'year.required' => 'Informe o ano do fechamento.',
            'year.between' => 'Ano inválido para fechamento.',
            'month.required' => 'Informe o mês do fechamento.',
            'month.between' => 'Mês inválido para fechamento.',
        ];
    }
}
