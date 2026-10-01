<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'João Silva', 'email' => 'joao@email.com', 'phone' => '11999999999', 'address' => 'Rua A, 123 - São Paulo', 'birth_date' => '1990-05-15'],
            ['name' => 'Maria Santos', 'email' => 'maria@email.com', 'phone' => '11988888888', 'address' => 'Av. B, 456 - São Paulo', 'birth_date' => '1985-08-22'],
            ['name' => 'Pedro Costa', 'email' => 'pedro@email.com', 'phone' => '11977777777', 'address' => 'Rua C, 789 - São Paulo', 'birth_date' => '1992-03-10'],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
