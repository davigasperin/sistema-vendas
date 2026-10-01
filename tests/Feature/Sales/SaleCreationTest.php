<?php

namespace Tests\Feature\Sales;

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

    public function test_characterization_client_can_currently_manipulate_price(): void
    {
        // Documentando vulnerabilidade atual da Fase 0:
        // O produto custa R$ 500,00 no banco, mas o cliente envia unit_price = 1.00
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
                    'unit_price' => 1.00, // Preço manipulado!
                    'subtotal' => 1.00,
                ],
            ],
            'installment_amounts' => [1.00],
            'installment_dates' => [now()->addMonth()->format('Y-m-d')],
        ];

        $response = $this->actingAs($this->user)->post(route('sales.store'), $payload);

        $response->assertRedirect(route('sales.index'));
        // Na Fase 0/1 isso infelizmente grava 1.00. Na Fase 3 nós tornaremos o backend a autoridade estrita!
        $this->assertDatabaseHas('sales', [
            'total_amount' => 1.00,
        ]);
    }
}
