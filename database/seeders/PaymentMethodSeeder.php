<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            ['name' => 'Dinheiro', 'description' => 'Pagamento à vista em dinheiro', 'active' => true],
            ['name' => 'Débito', 'description' => 'Cartão de débito', 'active' => true],
            ['name' => 'Crédito à Vista', 'description' => 'Cartão de crédito parcelado sem juros', 'active' => true],
            ['name' => 'Crédito Parcelado', 'description' => 'Cartão de crédito parcelado', 'active' => true],
            ['name' => 'PIX', 'description' => 'Pagamento via PIX', 'active' => true],
        ];

        foreach ($methods as $method) {
            PaymentMethod::create($method);
        }
    }
}