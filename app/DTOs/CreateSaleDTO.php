<?php

namespace App\DTOs;

readonly class CreateSaleDTO
{
    /**
     * @param  list<SaleItemDTO>  $items
     * @param  list<float>  $installmentAmounts
     * @param  list<string>  $installmentDates
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
    ) {}

    public static function fromArray(array $data, int $userId): self
    {
        $items = array_map(
            fn (array $item) => SaleItemDTO::fromArray($item),
            $data['items'] ?? []
        );

        $installmentAmounts = array_map('floatval', $data['installment_amounts'] ?? []);
        $installmentDates = array_map('strval', $data['installment_dates'] ?? []);

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
        );
    }
}
