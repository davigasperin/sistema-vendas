<?php

namespace Tests\Feature\Receivables;

use App\Actions\AddCashMovementAction;
use App\Actions\CloseCashShiftAction;
use App\Actions\OpenCashShiftAction;
use App\Enums\CashMovementType;
use App\Enums\ExpenseStatus;
use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\MonthClose;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SalePayment;
use App\Models\User;
use App\Queries\ReceivablesQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivablesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private PaymentMethod $cashMethod;

    private PaymentMethod $pixMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->cashMethod = PaymentMethod::factory()->create(['name' => 'Dinheiro']);
        $this->pixMethod = PaymentMethod::factory()->create(['name' => 'PIX']);
    }

    public function test_admin_can_pay_installment_via_web_route(): void
    {
        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
            'payment_method_id' => $this->pixMethod->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
            'payment_method_id' => $this->pixMethod->id,
            'paid_date' => now()->toDateString(),
        ]);

        $response->assertSessionHas('success');
        $this->assertTrue($installment->fresh()->is_paid);
        $this->assertEquals($this->pixMethod->id, $installment->fresh()->payment_method_id);
    }

    public function test_cash_payment_creates_receipt_movement_in_user_open_shift(): void
    {
        $shift = app(OpenCashShiftAction::class)($this->admin->id, 100.00);
        $sale = Sale::factory()->create(['user_id' => $this->admin->id]);
        $installment = SaleInstallment::factory()->create([
            'sale_id' => $sale->id,
            'amount' => 75.50,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
            'payment_method_id' => $this->cashMethod->id,
        ])->assertSessionHas('success');

        $this->assertTrue($installment->fresh()->is_paid);
        $this->assertDatabaseHas('cash_movements', [
            'cash_shift_id' => $shift->id,
            'user_id' => $this->admin->id,
            'type' => CashMovementType::Receipt->value,
            'amount' => '75.50',
            'reason' => "Recebimento da parcela {$installment->installment_number} da venda #{$sale->id}",
        ]);
    }

    public function test_cash_payment_without_open_shift_is_rejected(): void
    {
        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
            'payment_method_id' => $this->cashMethod->id,
        ])->assertSessionHasErrors('installment');

        $this->assertFalse($installment->fresh()->is_paid);
        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_payment_with_paid_date_in_closed_month_is_blocked(): void
    {
        MonthClose::create([
            'year' => now()->year,
            'month' => now()->month,
            'totals' => [],
            'closed_by' => $this->admin->id,
            'closed_at' => now(),
        ]);

        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
            'payment_method_id' => $this->pixMethod->id,
            'paid_date' => now()->toDateString(),
        ])->assertSessionHasErrors('installment');

        $this->assertFalse($installment->fresh()->is_paid);
    }

    public function test_already_paid_installment_cannot_be_paid_again(): void
    {
        $installment = SaleInstallment::factory()->paid()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
            'payment_method_id' => $this->pixMethod->id,
        ])->assertSessionHasErrors('installment');
    }

    public function test_seller_can_pay_only_installments_of_own_sales(): void
    {
        $seller = User::factory()->seller()->create();
        $ownInstallment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $seller->id])->id,
        ]);
        $otherInstallment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $this->admin->id])->id,
        ]);

        $this->actingAs($seller)
            ->post(route('installments.pay', $otherInstallment), [
                'payment_method_id' => $this->pixMethod->id,
            ])
            ->assertForbidden();

        $this->actingAs($seller)
            ->post(route('installments.pay', $ownInstallment), [
                'payment_method_id' => $this->pixMethod->id,
            ])
            ->assertSessionHas('success');

        $this->assertTrue($ownInstallment->fresh()->is_paid);
        $this->assertFalse($otherInstallment->fresh()->is_paid);
    }

    public function test_seller_receivables_listing_is_scoped_to_own_sales(): void
    {
        $seller = User::factory()->seller()->create();
        $ownInstallment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $seller->id])->id,
        ]);
        SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $this->admin->id])->id,
        ]);

        $this->actingAs($seller)
            ->get(route('receivables.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Receivables/Index', false)
                ->has('installments.data', 1)
                ->where('installments.data.0.id', $ownInstallment->id));
    }

    public function test_close_month_creates_record_with_snapshot_and_blocks_duplicates(): void
    {
        Sale::factory()->create([
            'user_id' => $this->admin->id,
            'status' => SaleStatus::Completed,
            'installments' => 1,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 300.00,
        ]);

        $parcelSale = Sale::factory()->create([
            'user_id' => $this->admin->id,
            'installments' => 3,
            'total_amount' => 240.00,
        ]);
        SaleInstallment::factory()->paid()->create([
            'sale_id' => $parcelSale->id,
            'amount' => 80.00,
        ]);

        Expense::factory()->create([
            'amount' => 50.00,
            'status' => ExpenseStatus::Paid,
            'paid_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('month-close.store', [now()->year, now()->month]))
            ->assertSessionHas('success');

        $close = MonthClose::sole();
        $this->assertEquals(now()->year, $close->year);
        $this->assertEquals(now()->month, $close->month);
        $this->assertEquals($this->admin->id, $close->closed_by);
        $this->assertEquals(540.00, $close->totals['sales_completed_total']);
        $this->assertEquals(80.00, $close->totals['installments_received_total']);
        $this->assertEquals(50.00, $close->totals['expenses_paid_total']);
        $this->assertEquals(300.00, $close->totals['spot_sales_total']);
        $this->assertEquals(330.00, $close->totals['cash_balance']);

        $this->actingAs($this->admin)
            ->post(route('month-close.store', [now()->year, now()->month]))
            ->assertSessionHasErrors('month_close');

        $this->assertDatabaseCount('month_closes', 1);
    }

    public function test_close_month_is_blocked_while_cash_shift_is_open(): void
    {
        app(OpenCashShiftAction::class)($this->admin->id, 100.00);

        $this->actingAs($this->admin)
            ->post(route('month-close.store', [now()->year, now()->month]))
            ->assertSessionHasErrors('month_close');

        $this->assertDatabaseCount('month_closes', 0);
    }

    public function test_seller_cannot_close_month(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)
            ->post(route('month-close.store', [now()->year, now()->month]))
            ->assertForbidden();

        $this->assertDatabaseCount('month_closes', 0);
    }

    public function test_close_cash_shift_expected_amount_includes_receipt_movements(): void
    {
        $shift = app(OpenCashShiftAction::class)($this->admin->id, 100.00);
        app(AddCashMovementAction::class)->addReceipt(
            $shift,
            $this->admin->id,
            40.00,
            'Recebimento de parcela em dinheiro'
        );

        $closed = app(CloseCashShiftAction::class)($shift, 140.00);

        $this->assertEquals(140.00, $closed->final_amount_expected);
        $this->assertEquals(0.00, $closed->difference);
        $this->assertTrue(CashMovementType::Receipt->isAddition());
        $this->assertSame('Recebimento de parcela', CashMovementType::Receipt->label());
    }

    public function test_cancelled_and_deleted_sales_are_excluded_and_cannot_be_paid(): void
    {
        $cancelledSale = Sale::factory()->create(['status' => SaleStatus::Cancelled]);
        $cancelled = SaleInstallment::factory()->create(['sale_id' => $cancelledSale->id]);
        $deletedSale = Sale::factory()->create();
        $deleted = SaleInstallment::factory()->create(['sale_id' => $deletedSale->id]);
        $deletedSale->delete();

        $this->actingAs($this->admin)
            ->get(route('receivables.index'))
            ->assertInertia(fn ($page) => $page->has('installments.data', 0));

        foreach ([$cancelled, $deleted] as $installment) {
            $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
                'payment_method_id' => $this->pixMethod->id,
            ])->assertForbidden();
            $this->assertFalse($installment->fresh()->is_paid);
        }
    }

    public function test_batch_payment_is_atomic_and_authorizes_each_installment(): void
    {
        $seller = User::factory()->seller()->create();
        $own = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $seller->id])->id,
        ]);
        $other = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $this->admin->id])->id,
        ]);

        $this->actingAs($seller)->post(route('installments.pay-batch'), [
            'installment_ids' => [$own->id, $other->id],
            'payment_method_id' => $this->pixMethod->id,
            'paid_date' => now()->toDateString(),
        ])->assertForbidden();

        $this->assertFalse($own->fresh()->is_paid);
        $this->assertFalse($other->fresh()->is_paid);
    }

    public function test_closed_month_page_uses_frozen_totals_and_can_select_previous_month(): void
    {
        $date = now()->subMonthNoOverflow();
        $close = MonthClose::create([
            'year' => $date->year,
            'month' => $date->month,
            'totals' => [
                'year' => $date->year,
                'month' => $date->month,
                'sales_completed_total' => 10.0,
                'installments_received_total' => 20.0,
                'spot_sales_total' => 30.0,
                'expenses_paid_total' => 5.0,
                'cash_balance' => 45.0,
            ],
            'closed_by' => $this->admin->id,
            'closed_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('receivables.index', ['year' => $date->year, 'month' => $date->month]))
            ->assertInertia(fn ($page) => $page
                ->where('currentMonthClose.id', $close->id)
                ->where('monthSnapshot.cash_balance', 45));
    }

    public function test_cash_balance_subtracts_paid_expenses(): void
    {
        Sale::factory()->create([
            'status' => SaleStatus::Completed,
            'installments' => 1,
            'total_amount' => 100,
            'created_at' => now(),
        ]);
        Expense::factory()->create([
            'amount' => 25,
            'status' => ExpenseStatus::Paid,
            'paid_date' => now()->toDateString(),
        ]);

        $snapshot = app(ReceivablesQuery::class)->monthSnapshot(now()->year, now()->month);

        $this->assertSame(75.0, $snapshot['cash_balance']);
    }

    public function test_batch_payment_pays_all_installments_in_one_request(): void
    {
        $first = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);
        $second = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay-batch'), [
            'installment_ids' => [$first->id, $second->id],
            'payment_method_id' => $this->pixMethod->id,
            'paid_date' => now()->toDateString(),
        ])->assertSessionHas('success');

        $this->assertTrue($first->fresh()->is_paid);
        $this->assertTrue($second->fresh()->is_paid);
    }

    public function test_batch_payment_rolls_back_when_any_installment_fails(): void
    {
        $first = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);
        $alreadyPaid = SaleInstallment::factory()->paid()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay-batch'), [
            'installment_ids' => [$first->id, $alreadyPaid->id],
            'payment_method_id' => $this->pixMethod->id,
            'paid_date' => now()->toDateString(),
        ])->assertSessionHasErrors('installment');

        $this->assertFalse($first->fresh()->is_paid);
        $this->assertTrue($alreadyPaid->fresh()->is_paid);
    }

    public function test_installment_from_closed_month_can_be_paid_in_open_month_by_paid_date(): void
    {
        $previous = now()->subMonthNoOverflow();
        MonthClose::create([
            'year' => $previous->year,
            'month' => $previous->month,
            'totals' => [],
            'closed_by' => $this->admin->id,
            'closed_at' => now(),
        ]);

        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
            'due_date' => $previous->copy()->startOfMonth()->addDays(5)->toDateString(),
            'is_paid' => false,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
            'payment_method_id' => $this->pixMethod->id,
            'paid_date' => now()->toDateString(),
        ])->assertSessionHas('success');

        $this->assertTrue($installment->fresh()->is_paid);
    }

    public function test_listing_serializes_status_and_snake_case_payment_method(): void
    {
        SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
            'payment_method_id' => $this->pixMethod->id,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('receivables.index'))
            ->assertInertia(fn ($page) => $page
                ->component('Receivables/Index', false)
                ->where('installments.data.0.status', 'pending')
                ->where('installments.data.0.payment_method.name', 'PIX'));
    }

    public function test_seller_sees_pay_ability_in_own_listing(): void
    {
        $seller = User::factory()->seller()->create();
        SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $seller->id])->id,
        ]);

        $this->actingAs($seller)
            ->get(route('receivables.index'))
            ->assertInertia(fn ($page) => $page->where('abilities.canPay', true));
    }

    public function test_future_month_cannot_be_closed(): void
    {
        $future = now()->addMonthNoOverflow();

        $this->actingAs($this->admin)
            ->post(route('month-close.store', [$future->year, $future->month]))
            ->assertSessionHasErrors('month_close');

        $this->assertDatabaseCount('month_closes', 0);
    }

    public function test_close_shift_does_not_double_count_installment_sales(): void
    {
        $shift = app(OpenCashShiftAction::class)($this->admin->id, 0.00);
        $sale = Sale::factory()->create([
            'user_id' => $this->admin->id,
            'installments' => 2,
            'payment_method_id' => $this->cashMethod->id,
            'cash_shift_id' => $shift->id,
            'total_amount' => 200.00,
            'status' => SaleStatus::Completed,
        ]);
        SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method_id' => $this->cashMethod->id,
            'amount' => 200.00,
        ]);
        app(AddCashMovementAction::class)->addReceipt($shift, $this->admin->id, 50.00, 'Parcela 1 em dinheiro');

        $closed = app(CloseCashShiftAction::class)($shift, 50.00);

        $this->assertEquals(50.00, $closed->final_amount_expected);
        $this->assertEquals(0.00, $closed->difference);
    }

    public function test_cash_receipt_for_single_installment_is_not_counted_twice(): void
    {
        $shift = app(OpenCashShiftAction::class)($this->admin->id, 0.00);
        $sale = Sale::factory()->create([
            'user_id' => $this->admin->id,
            'installments' => 1,
            'payment_method_id' => $this->cashMethod->id,
            'cash_shift_id' => $shift->id,
            'total_amount' => 75.00,
        ]);
        SalePayment::create([
            'sale_id' => $sale->id,
            'payment_method_id' => $this->cashMethod->id,
            'amount' => 75.00,
        ]);
        $installment = SaleInstallment::factory()->create([
            'sale_id' => $sale->id,
            'amount' => 75.00,
        ]);

        $this->actingAs($this->admin)->post(route('installments.pay', $installment), [
            'payment_method_id' => $this->cashMethod->id,
        ])->assertSessionHas('success');

        $closed = app(CloseCashShiftAction::class)($shift, 75.00);

        $this->assertEquals(75.00, $closed->final_amount_expected);
    }

    public function test_financial_user_can_pay_any_installment(): void
    {
        $financial = User::factory()->financial()->create();
        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => $this->admin->id])->id,
        ]);

        $this->actingAs($financial)
            ->post(route('installments.pay', $installment), [
                'payment_method_id' => $this->pixMethod->id,
            ])
            ->assertSessionHas('success');

        $this->assertTrue($installment->fresh()->is_paid);
    }
}
