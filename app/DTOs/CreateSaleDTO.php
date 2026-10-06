<?php

namespace App\DTOs;

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
            'notes' => $payment['notes'] ?? null,
        ], $data['payments'] ?? []);

        $cashShiftId = ! empty($data['cash_shift_id']) ? (int) $data['cash_shift_id'] : null;
        if (! $cashShiftId && auth()->user()) {
            $cashShiftId = auth()->user()->currentCashShift()?->id;
        }

        return new self(
            userId: $userId,
            customerId: ! empty($data['customer_id']) ? (int) $data['customer_id'] : null,
            paymentMethodId: (int) $data['payment_method_id'],
            discount: isset($data['discount']) ? (float) $data['discount'] : 0.0,
            installments: isset($data['installments']) ? (int) $data['installments'] : 1,
            notes: $data['notes'] ?? null,
            items: $items,
            installmentAmounts: $installmentAmounts,
            installmentDates: $installmentDates,
            cashShiftId: $cashShiftId,
            payments: $payments,
        );
    }
}
