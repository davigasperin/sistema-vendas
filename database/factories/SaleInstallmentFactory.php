<?php

namespace Database\Factories;

use App\Models\Sale;
use App\Models\SaleInstallment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaleInstallment>
 */
class SaleInstallmentFactory extends Factory
{
    protected $model = SaleInstallment::class;

    public function definition(): array
    {
        return [
            'sale_id' => Sale::factory(),
            'installment_number' => 1,
            'amount' => 100.00,
            'due_date' => now()->addMonth()->format('Y-m-d'),
            'paid_date' => null,
            'is_paid' => false,
            'notes' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
            'paid_date' => now()->format('Y-m-d'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => false,
            'due_date' => now()->subDays(10)->format('Y-m-d'),
        ]);
    }
}
