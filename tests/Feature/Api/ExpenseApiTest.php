<?php

namespace Tests\Feature\Api;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->financial()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_and_create_expenses_via_api(): void
    {
        $category = ExpenseCategory::factory()->create();

        $payload = [
            'description' => 'Servidor Cloud',
            'amount' => 450.00,
            'due_date' => now()->addDays(15)->format('Y-m-d'),
            'category_id' => $category->id,
            'type' => ExpenseType::Expense->value,
            'status' => ExpenseStatus::Pending->value,
        ];

        $response = $this->postJson('/api/v1/expenses', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.description', 'Servidor Cloud');
        $this->assertEquals(450.00, (float) $response->json('data.amount'));

        $listResponse = $this->getJson('/api/v1/expenses');
        $listResponse->assertOk();
        $this->assertNotEmpty($listResponse->json('data'));
    }

    public function test_can_mark_expense_as_paid_via_api(): void
    {
        $expense = Expense::factory()->create(['status' => ExpenseStatus::Pending]);

        $response = $this->patchJson("/api/v1/expenses/{$expense->id}/mark-paid", [
            'paid_date' => now()->format('Y-m-d'),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.status', 'paid');
        $this->assertEquals(ExpenseStatus::Paid, $expense->fresh()->status);
    }
}
