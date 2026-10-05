<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => StockMovementType::InitialStock,
            'quantity' => 10,
            'previous_stock' => 0,
            'new_stock' => 10,
            'reference_type' => null,
            'reference_id' => null,
            'reason' => 'Carga inicial de estoque',
            'user_id' => User::factory(),
        ];
    }
}
