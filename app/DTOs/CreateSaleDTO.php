<?php

namespace App\DTOs;

use App\Models\User;

readonly class CreateSaleDTO
{
    /**
     * @param  list<SaleItemDTO>  $items
     * @param  list<float>  $installmentAmounts
     * @param  list<string>  $installmentDates
     * @param  list<array{payment_method_id: int, amount: float, change_given?: float, notes?: string|null}>  $payments
     */
    public function __construct(
        public int $userId,
        public ?int $customerId,
        public int $paymentMethodId,
        public float $discount,
        public int $installments,
        public ?string $notes,
        public array $items,
        public array $installmentAmounts = [],
        public array $installmentDates = [],
        public ?int $cashShiftId = null,
        public array $payments = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data, int $userId): self
    {
        $items = array_map(
            fn (array $item) => SaleItemDTO::fromArray($item),
            $data['items'] ?? []
        );

        $installmentAmounts = array_map('floatval', $data['installment_amounts'] ?? []);
        $installmentDates = array_map('strval', $data['installment_dates'] ?? []);
        $payments = array_map(static fn (array $payment) => [
            'payment_method_id' => (int) $payment['payment_method_id'],
            'amount' => (float) $payment['amount'],
            'change_given' => isset($payment['change_given']) ? (float) $payment['change_given'] : 0.0,
            'notes' => isset($payment['notes']) ? (string) $payment['notes'] : null,
        ], $data['payments'] ?? []);

        $cashShiftId = ! empty($data['cash_shift_id']) ? (int) $data['cash_shift_id'] : null;
        if (! $cashShiftId) {
            $user = auth()->user() ?? User::find($userId);
            $cashShiftId = $user?->currentCashShift()?->id;
        }

        $paymentMethodId = ! empty($payments)
            ? (int) $payments[0]['payment_method_id']
            : (int) ($data['payment_method_id'] ?? 1);

        return new self(
            userId: $userId,
            customerId: ! empty($data['customer_id']) ? (int) $data['customer_id'] : null,
            paymentMethodId: $paymentMethodId,
            discount: isset($data['discount']) ? (float) $data['discount'] : 0.0,
            installments: isset($data['installments']) ? (int) $data['installments'] : 1,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            items: $items,
            installmentAmounts: $installmentAmounts,
            installmentDates: $installmentDates,
            cashShiftId: $cashShiftId,
            payments: $payments,
        );
    }
}
