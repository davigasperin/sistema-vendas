<?php

namespace Tests\Feature\Api;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
        $this->customer = Customer::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_create_sale_via_api_with_server_price_calculation(): void
    {
        $product = Product::factory()->create(['price' => 200.00, 'stock' => 10]);

        $payload = [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
            'discount' => 20.00,
            'installments' => 2,
            'notes' => 'Venda via API',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
            ],
            'installment_amounts' => [190.00, 190.00],
            'installment_dates' => [
                now()->addMonth()->format('Y-m-d'),
                now()->addMonths(2)->format('Y-m-d'),
            ],
        ];

        $response = $this->postJson('/api/v1/sales', $payload);

        $response->assertCreated();
        $this->assertEquals(380.00, (float) $response->json('data.total_amount'));
        $this->assertEquals(20.00, (float) $response->json('data.discount'));
        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_can_cancel_sale_via_api(): void
    {
        $product = Product::factory()->create(['price' => 100.00, 'stock' => 5]);
        $sale = Sale::factory()->create(['total_amount' => 100.00, 'status' => SaleStatus::Completed]);
        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'subtotal' => 200.00,
        ]);
        $product->update(['stock' => 3]);

        $response = $this->postJson("/api/v1/sales/{$sale->id}/cancel");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'cancelled');
        $this->assertEquals(5, $product->fresh()->stock);
    }
}
