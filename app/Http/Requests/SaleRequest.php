<?php

namespace App\Http\Requests;

use App\Rules\InstallmentsSumRule;
use Illuminate\Foundation\Http\FormRequest;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $total = $this->calculateItemsTotal();
        $rules = [
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'discount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
            'installments' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'installment_dates' => 'nullable|array',
            'installment_dates.*' => 'required|date',
            'installment_amounts' => 'nullable|array|min:1',
            'installment_amounts.*' => 'required|numeric|min:0',
        ];

        if (!empty($this->input('installment_amounts'))) {
            $rules['installment_amounts'] = ['nullable', 'array', 'min:1', new InstallmentsSumRule($total)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'A forma de pagamento é obrigatória.',
            'installments.required' => 'O número de parcelas é obrigatório.',
            'installments.min' => 'Mínimo de 1 parcela.',
            'items.required' => 'Adicione pelo menos um item à venda.',
            'items.*.product_id.exists' => 'Produto selecionado não encontrado.',
            'items.*.quantity.min' => 'Quantidade mínima é 1.',
        ];
    }

    protected function calculateItemsTotal(): float
    {
        $items = $this->input('items', []);
        $total = 0;

        foreach ($items as $item) {
            $total += ($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0);
        }

        return $total - floatval($this->input('discount', 0));
    }
}