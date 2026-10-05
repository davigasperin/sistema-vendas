<?php

namespace Tests\Feature\Expenses;

use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->financial()->create();
    }

    public function test_can_list_expenses(): void
    {
        Expense::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Expenses/Index')->has('expenses'));
    }

    public function test_can_create_expense(): void
    {
        $category = ExpenseCategory::factory()->create();

        $payload = [
            'description' => 'Conta de Luz',
            'amount' => 350.00,
            'due_date' => now()->addDays(5)->format('Y-m-d'),
            'category_id' => $category->id,
            'type' => Expense::TYPE_EXPENSE,
            'status' => Expense::STATUS_PENDING,
        ];

        $response = $this->actingAs($this->user)->post(route('expenses.store'), $payload);

        $response->assertRedirect(route('expenses.index'));
        $this->assertDatabaseHas('expenses', [
            'description' => 'Conta de Luz',
            'amount' => 350.00,
        ]);
    }

    public function test_can_mark_expense_as_paid(): void
    {
        $expense = Expense::factory()->create([
            'status' => Expense::STATUS_PENDING,
            'paid_date' => null,
        ]);

        $response = $this->actingAs($this->user)->patch(
            route('expenses.markPaid', $expense),
            ['paid_date' => now()->format('Y-m-d')]
        );

        $response->assertRedirect();
        $this->assertEquals(ExpenseStatus::Paid, $expense->fresh()->status);
        $this->assertNotNull($expense->fresh()->paid_date);
    }
}
