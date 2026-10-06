<?php

namespace Tests\Feature\Sales;

use App\Enums\CashShiftStatus;
use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleCreationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seller;

    private Customer $customer;

    private PaymentMethod $cashMethod;

    private PaymentMethod $pixMethod;

    private PaymentMethod $cardMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->seller = User::factory()->seller()->create();
        $this->customer = Customer::factory()->create();

        $this->cashMethod = PaymentMethod::factory()->create([
            'name' => 'Dinheiro',
            'description' => 'Pagamento à vista em dinheiro',
            'active' => true,
        ]);
        $this->pixMethod = PaymentMethod::factory()->create([
            'name' => 'PIX',
            'description' => 'Pagamento instantâneo PIX',
            'active' => true,
        ]);
        $this->cardMethod = PaymentMethod::factory()->create([
            'name' => 'Cartão de Crédito',
            'description' => 'Cartão parcelado',
            'active' => true,
        ]);
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
            'payment_method_id' => $this->cashMethod->id,
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

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('sales.show', $sale->id));

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 90.00,
            'discount' => 10.00,
        ]);

        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method_id' => $this->cashMethod->id,
            'amount' => 90.00,
            'change_given' => 0.00,
        ]);

        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_can_create_sale_with_multi_payments_exact_sum_and_persists_sale_payments(): void
    {
        $product = Product::factory()->create([
            'price' => 150.00,
            'stock' => 5,
            'active' => true,
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->cashMethod->id,
            'discount' => 20.00,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2], // Subtotal 300 - 20 desc = 280 líquido
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 100.00,
                    'change_given' => 20.00, // Entregou 120, aplicou 100
                    'notes' => 'Entregou R$ 120 em espécie',
                ],
                [
                    'payment_method_id' => $this->pixMethod->id,
                    'amount' => 180.00,
                    'change_given' => 0.00,
                    'notes' => 'Chave PIX aprovada',
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $sale = Sale::latest('id')->first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('sales.show', $sale->id));

        $this->assertEquals(280.00, (float) $sale->total_amount);

        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method_id' => $this->cashMethod->id,
            'amount' => 100.00,
            'change_given' => 20.00,
        ]);

        $this->assertDatabaseHas('sale_payments', [
            'sale_id' => $sale->id,
            'payment_method_id' => $this->pixMethod->id,
            'amount' => 180.00,
            'change_given' => 0.00,
        ]);

        $this->assertCount(2, $sale->payments);
    }

    public function test_rejects_change_given_on_non_cash_methods(): void
    {
        $product = Product::factory()->create(['price' => 100.00, 'stock' => 5]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->pixMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->pixMethod->id,
                    'amount' => 100.00,
                    'change_given' => 10.00, // Inválido para PIX
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $response->assertSessionHasErrors(['payments.0.change_given']);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_rejects_and_rolls_back_when_payments_sum_mismatches_net_total(): void
    {
        $product = Product::factory()->create(['price' => 200.00, 'stock' => 10]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->cashMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1], // Total 200.00
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 150.00, // Faltam 50.00!
                    'change_given' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $response->assertSessionHasErrors(['payments']);
        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_rejects_duplicate_payment_method_in_same_checkout(): void
    {
        $product = Product::factory()->create(['price' => 100.00, 'stock' => 5]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->cashMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 50.00,
                ],
                [
                    'payment_method_id' => $this->cashMethod->id, // Duplicado!
                    'amount' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $response->assertSessionHasErrors(['payments.1.payment_method_id']);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_seller_without_open_cash_shift_is_blocked_from_creating_sale(): void
    {
        $product = Product::factory()->create(['price' => 50.00, 'stock' => 5]);

        $payload = [
            'payment_method_id' => $this->cashMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $response = $this->actingAs($this->seller)->post(route('sales.store'), $payload);

        $response->assertSessionHasErrors(['cash_shift']);
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_seller_with_open_cash_shift_can_create_sale_and_links_cash_shift(): void
    {
        $shift = CashShift::create([
            'user_id' => $this->seller->id,
            'opened_at' => now(),
            'initial_amount' => 100.00,
            'status' => CashShiftStatus::Open,
        ]);

        $product = Product::factory()->create(['price' => 75.00, 'stock' => 4]);

        $payload = [
            'payment_method_id' => $this->cashMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $response = $this->actingAs($this->seller)->post(route('sales.store'), $payload);

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('sales.show', $sale->id));
        $this->assertEquals($shift->id, $sale->cash_shift_id);
    }

    public function test_admin_retaguarda_can_create_sale_without_open_cash_shift(): void
    {
        $product = Product::factory()->create(['price' => 120.00, 'stock' => 3]);

        $payload = [
            'payment_method_id' => $this->pixMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('sales.show', $sale->id));
        $this->assertNull($sale->cash_shift_id);
    }

    public function test_admin_with_open_cash_shift_links_shift_automatically(): void
    {
        $shift = CashShift::create([
            'user_id' => $this->admin->id,
            'opened_at' => now(),
            'initial_amount' => 200.00,
            'status' => CashShiftStatus::Open,
        ]);

        $product = Product::factory()->create(['price' => 80.00, 'stock' => 10]);

        $payload = [
            'payment_method_id' => $this->pixMethod->id,
            'discount' => 0,
            'installments' => 1,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals($shift->id, $sale->cash_shift_id);
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
            'payment_method_id' => $this->cashMethod->id,
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

        $this->actingAs($this->admin)->post(route('sales.store'), $payload);
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
            'payment_method_id' => $this->cashMethod->id,
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

        $response = $this->actingAs($this->admin)->post(route('sales.store'), $payload);

        $sale = Sale::first();
        $this->assertNotNull($sale);
        $response->assertRedirect(route('sales.show', $sale->id));

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

    public function test_can_view_thermal_receipt_view_with_authorized_user(): void
    {
        $sale = Sale::factory()->create([
            'user_id' => $this->admin->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 100.00,
        ]);

        $response80 = $this->actingAs($this->admin)->get(route('sales.receipt', [$sale->id, 'width' => '80mm']));
        $response80->assertOk();
        $response80->assertSee('80mm');
        $response80->assertSee('CUPOM NÃO FISCAL');

        $response58 = $this->actingAs($this->admin)->get(route('sales.receipt', [$sale->id, 'width' => '58mm']));
        $response58->assertOk();
        $response58->assertSee('58mm');
    }
}
