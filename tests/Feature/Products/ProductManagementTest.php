<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('products.index'));

        $response->assertOk();
        $response->assertViewHas('products');
    }

    public function test_can_create_product(): void
    {
        $payload = [
            'name' => 'Novo Teclado Mecânico',
            'description' => 'Switch azul RGB',
            'price' => 250.50,
            'stock' => 15,
            'low_stock_threshold' => 3,
            'active' => 1,
        ];

        $response = $this->actingAs($this->user)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', [
            'name' => 'Novo Teclado Mecânico',
            'stock' => 15,
        ]);
    }

    public function test_can_adjust_stock_via_patch(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($this->user)->patchJson(
            route('products.adjust-stock', $product),
            ['adjustment' => 5]
        );

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'stock' => 15,
        ]);
        $this->assertEquals(15, $product->fresh()->stock);
    }

    public function test_can_toggle_active_status(): void
    {
        $product = Product::factory()->create(['active' => true]);

        $response = $this->actingAs($this->user)->patchJson(
            route('products.toggle-active', $product)
        );

        $response->assertOk();
        $this->assertFalse($product->fresh()->active);
    }
}
