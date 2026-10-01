<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Notebook', 'description' => 'Notebook Dell Inspiron', 'price' => 3500.00, 'stock' => 10],
            ['name' => 'Mouse Wireless', 'description' => 'Mouse sem fio Logitech', 'price' => 89.90, 'stock' => 50],
            ['name' => 'Teclado Mecânico', 'description' => 'Teclado gamer RGB', 'price' => 299.90, 'stock' => 30],
            ['name' => 'Monitor 24"', 'description' => 'Monitor Full HD 60Hz', 'price' => 899.00, 'stock' => 15],
            ['name' => 'Webcam HD', 'description' => 'Webcam 1080p', 'price' => 189.90, 'stock' => 25],
            ['name' => 'Fone de Ouvido', 'description' => 'Fone Bluetooth', 'price' => 149.90, 'stock' => 40],
            ['name' => 'Mousepad', 'description' => 'Mousepad gamer XL', 'price' => 59.90, 'stock' => 60],
            ['name' => 'Hub USB', 'description' => 'Hub 4 portas USB 3.0', 'price' => 79.90, 'stock' => 35],
            ['name' => 'SSD 500GB', 'description' => 'SSD Samsung 500GB', 'price' => 349.90, 'stock' => 20],
            ['name' => 'Webcam Pro', 'description' => 'Webcam 4K com microfone', 'price' => 499.90, 'stock' => 12],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
