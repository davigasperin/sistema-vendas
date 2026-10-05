<?php

namespace Tests\Feature\Expenses;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseCategoryCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_expense_category(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/expense-categories', [
            'name' => 'Marketing & Ads',
            'type' => 'expense',
        ]);

        $response->assertCreated()
            ->assertJson([
                'name' => 'Marketing & Ads',
                'type' => 'expense',
            ]);

        $this->assertDatabaseHas('expense_categories', [
            'name' => 'Marketing & Ads',
            'type' => 'expense',
        ]);
    }

    public function test_unauthorized_user_cannot_create_expense_category(): void
    {
        $seller = User::factory()->create([
            'role' => UserRole::Seller,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($seller)->postJson('/expense-categories', [
            'name' => 'Test',
            'type' => 'expense',
        ]);

        $response->assertForbidden();
    }

    public function test_validation_rules_for_expense_category(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/expense-categories', [
            'name' => '',
            'type' => 'invalid_type',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'type']);
    }
}
