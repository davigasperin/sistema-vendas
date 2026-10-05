<?php

namespace App\Http\Resources\V1;

use App\Models\SaleInstallment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SaleInstallment
 */
class SaleInstallmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'installment_number' => (int) $this->installment_number,
            'amount' => (float) $this->amount,
            'due_date' => $this->due_date->format('Y-m-d'),
            'paid_date' => $this->paid_date?->format('Y-m-d'),
            'is_paid' => (bool) $this->is_paid,
            'status' => $this->status,
            'notes' => $this->notes,
        ];
    }
}
