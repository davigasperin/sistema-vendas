<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Rules\InstallmentsSumRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'nullable|numeric|min:0',
            'installment_dates' => 'nullable|array',
            'installment_dates.*' => 'required|date',
            'installment_amounts' => 'nullable|array|min:1',
            'installment_amounts.*' => 'required|numeric|min:0',
            'cash_shift_id' => 'nullable|exists:cash_shifts,id',
            'payments' => 'nullable|array',
            'payments.*.payment_method_id' => 'required|exists:payment_methods,id',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.change_given' => 'nullable|numeric|min:0',
            'payments.*.notes' => 'nullable|string|max:255',
        ];

        if (! empty($this->input('installment_amounts'))) {
            $rules['installment_amounts'] = ['nullable', 'array', 'min:1', new InstallmentsSumRule($total)];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $user = $this->user();

            if ($user && $user->role === UserRole::Seller && ! $user->currentCashShift()) {
                $validator->errors()->add(
                    'cash_shift',
                    'Vendedores no PDV precisam de um caixa aberto para registrar vendas.'
                );
            }

            $items = (array) $this->input('items', []);
            if (empty($items)) {
                return;
            }

            $netTotalCents = $this->calculateItemsTotalCents();
            $payments = (array) $this->input('payments', []);

            if (! empty($payments)) {
                $paymentMethodIds = array_filter(array_column($payments, 'payment_method_id'));
                $paymentMethods = PaymentMethod::whereIn('id', $paymentMethodIds)->get()->keyBy('id');

                $seenMethodIds = [];
                $paymentsSumCents = 0;

                foreach ($payments as $index => $payment) {
                    $methodId = (int) ($payment['payment_method_id'] ?? 0);
                    $amount = (float) ($payment['amount'] ?? 0);
                    $changeGiven = (float) ($payment['change_given'] ?? 0);

                    $amountCents = (int) round($amount * 100);
                    $changeCents = (int) round($changeGiven * 100);

                    if ($amountCents <= 0) {
                        $validator->errors()->add("payments.{$index}.amount", 'O valor aplicado no pagamento deve ser maior que zero.');
                    }

                    if (in_array($methodId, $seenMethodIds, true)) {
                        $validator->errors()->add("payments.{$index}.payment_method_id", 'Forma de pagamento duplicada no checkout.');
                    }
                    $seenMethodIds[] = $methodId;

                    $method = $paymentMethods->get($methodId);
                    if ($method && ! $method->active) {
                        $validator->errors()->add("payments.{$index}.payment_method_id", "A forma de pagamento '{$method->name}' está inativa.");
                    }

                    $isCash = $method && (
                        mb_strtolower($method->name) === 'dinheiro' ||
                        str_contains(mb_strtolower($method->description ?? ''), 'dinheiro')
                    );

                    if ($changeCents > 0 && ! $isCash) {
                        $validator->errors()->add("payments.{$index}.change_given", 'Troco só é permitido para pagamentos em Dinheiro.');
                    }

                    $paymentsSumCents += $amountCents;
                }

                if ($paymentsSumCents !== $netTotalCents) {
                    $formattedSum = number_format($paymentsSumCents / 100, 2, ',', '.');
                    $formattedTotal = number_format($netTotalCents / 100, 2, ',', '.');
                    $validator->errors()->add(
                        'payments',
                        "A soma dos pagamentos (R$ {$formattedSum}) deve ser exatamente igual ao total líquido da venda (R$ {$formattedTotal})."
                    );
                }
            }
        });
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

    public function calculateItemsTotalCents(): int
    {
        $items = (array) $this->input('items', []);
        if (empty($items)) {
            return 0;
        }

        $productIds = array_column($items, 'product_id');
        $prices = Product::whereIn('id', $productIds)->pluck('price', 'id');

        $totalCents = 0;
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $price = (float) ($prices[$productId] ?? 0);
            $totalCents += (int) round($price * 100) * $quantity;
        }

        $discountCents = (int) round(((float) $this->input('discount', 0)) * 100);

        return max(0, $totalCents - $discountCents);
    }

    protected function calculateItemsTotal(): float
    {
        return round($this->calculateItemsTotalCents() / 100, 2);
    }
}
