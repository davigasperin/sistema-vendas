<?php

namespace Tests\Feature\Sales;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create(['active' => true]);
    }

    public function test_can_create_sale_and_decrements_product_stock(): void
    {
        $product = Product::factory()->create([
            'price' => 50.00,
            'stock' => 10,
            'active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
            'discount' => 10.00,
            'notes' => 'Venda de teste inicial',
            'installments' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'subtotal' => 100.00,
                ],
            ],
            'installment_amounts' => [90.00],
            'installment_dates' => [now()->addMonth()->format('Y-m-d')],
        ];

        $response = $this->actingAs($this->user)->post(route('sales.store'), $payload);

        $response->assertRedirect(route('sales.index'));
        $this->assertDatabaseHas('sales', [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
            'total_amount' => 90.00,
            'discount' => 10.00,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_throws_insufficient_stock_exception_when_stock_is_not_enough(): void
    {
        $this->withoutExceptionHandling();

        $product = Product::factory()->create([
            'price' => 100.00,
            'stock' => 1,
            'active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 100.00,
                    'subtotal' => 500.00,
                ],
            ],
            'installment_amounts' => [500.00],
            'installment_dates' => [now()->addMonth()->format('Y-m-d')],
        ];

        $this->expectException(InsufficientStockException::class);

        $this->actingAs($this->user)->post(route('sales.store'), $payload);
    }

    public function test_backend_enforces_price_authority_ignoring_client_manipulated_price(): void
    {
        $product = Product::factory()->create([
            'price' => 500.00,
            'stock' => 10,
            'active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'unit_price' => 1.00, // Preço fraudulento enviado pelo cliente!
                    'subtotal' => 1.00,
                ],
            ],
            'installment_amounts' => [500.00],
            'installment_dates' => [now()->addMonth()->format('Y-m-d')],
        ];

        $response = $this->actingAs($this->user)->post(route('sales.store'), $payload);

        $response->assertRedirect(route('sales.index'));

        // O backend ignorou 1.00 e usou o preço oficial do banco (500.00)
        $this->assertDatabaseHas('sales', [
            'total_amount' => 500.00,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'product_id' => $product->id,
            'unit_price' => 500.00,
            'subtotal' => 500.00,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::Sale,
            'quantity' => -1,
            'previous_stock' => 10,
            'new_stock' => 9,
        ]);
    }
}
