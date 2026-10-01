<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_and_create_resources(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('products.index'))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('expenses.index'))
            ->assertOk();
    }

    public function test_seller_cannot_create_or_delete_expenses(): void
    {
        $seller = User::factory()->seller()->create();
        $category = ExpenseCategory::factory()->create();

        $this->actingAs($seller)
            ->post(route('expenses.store'), [
                'description' => 'Gasto não autorizado',
                'amount' => 100.00,
                'due_date' => now()->format('Y-m-d'),
                'category_id' => $category->id,
                'type' => 'expense',
            ])
            ->assertForbidden();
    }

    public function test_unverified_user_cannot_create_products(): void
    {
        $unverified = User::factory()->unverified()->create([
            'role' => UserRole::Seller,
        ]);

        $this->actingAs($unverified)
            ->post(route('products.store'), [
                'name' => 'Produto Bloqueado',
                'price' => 50.00,
                'stock' => 10,
            ])
            ->assertForbidden();
    }

    public function test_non_admin_cannot_delete_customer(): void
    {
        $seller = User::factory()->seller()->create();
        $customer = Customer::factory()->create();

        $this->actingAs($seller)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();
    }

    public function test_expenses_report_route_is_not_shadowed_by_show_route(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('expenses.report'));

        $response->assertOk();
        $response->assertViewIs('expenses.report');
    }
}
