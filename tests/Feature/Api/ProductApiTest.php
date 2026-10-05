<?php

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_products_with_pagination(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/products?per_page=2');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'price', 'stock', 'active', 'is_low_stock'],
            ],
            'links',
            'meta' => ['current_page', 'per_page', 'total'],
        ]);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_can_adjust_product_stock_via_api(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->patchJson("/api/v1/products/{$product->id}/adjust-stock", [
            'adjustment' => 5,
        ]);

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'id' => $product->id,
                'stock' => 15,
                'adjustment' => 5,
            ],
        ]);
        $this->assertEquals(15, $product->fresh()->stock);
    }
}
