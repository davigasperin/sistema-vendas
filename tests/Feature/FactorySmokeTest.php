<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactorySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_domain_factories_create_valid_records(): void
    {
        $user = User::factory()->create();
        $this->assertDatabaseHas('users', ['id' => $user->id]);

        $customer = Customer::factory()->create();
        $this->assertDatabaseHas('customers', ['id' => $customer->id]);

        $product = Product::factory()->create();
        $this->assertDatabaseHas('products', ['id' => $product->id]);

        $paymentMethod = PaymentMethod::factory()->create();
        $this->assertDatabaseHas('payment_methods', ['id' => $paymentMethod->id]);

        $sale = Sale::factory()->create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);

        $saleItem = SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseHas('sale_items', ['id' => $saleItem->id]);

        $installment = SaleInstallment::factory()->create([
            'sale_id' => $sale->id,
        ]);
        $this->assertDatabaseHas('sale_installments', ['id' => $installment->id]);

        $category = ExpenseCategory::factory()->create();
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);

        $expense = Expense::factory()->create([
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);

        $movement = StockMovement::factory()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('stock_movements', ['id' => $movement->id]);
    }
}
