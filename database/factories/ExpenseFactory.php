<?php

namespace Database\Factories;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Expense>
 */
class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'description' => fake()->sentence(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'due_date' => now()->addDays(10)->format('Y-m-d'),
            'paid_date' => null,
            'category_id' => ExpenseCategory::factory(),
            'type' => Expense::TYPE_EXPENSE,
            'status' => Expense::STATUS_PENDING,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Expense::STATUS_PAID,
            'paid_date' => now()->format('Y-m-d'),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Expense::STATUS_OVERDUE,
            'due_date' => now()->subDays(5)->format('Y-m-d'),
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => Expense::TYPE_INCOME,
            'category_id' => ExpenseCategory::factory()->income(),
        ]);
    }
}
