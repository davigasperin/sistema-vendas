<?php

namespace Tests\Feature\CashShift;

use App\Enums\UserRole;
use App\Models\CashShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CashShiftControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_move_and_close_cash_shift_via_web(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $this->actingAs($admin);

        $this->post('/cashier/open', ['initial_amount' => 100.00])->assertRedirect();
        $shift = CashShift::firstOrFail();
        $this->assertTrue($shift->isOpen());

        $this->post('/cashier/movement', [
            'type' => 'supply',
            'amount' => 25.00,
            'reason' => 'Troco extra',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_movements', ['cash_shift_id' => $shift->id, 'type' => 'supply']);

        $this->post('/cashier/close', ['reported_amount' => 125.00])->assertRedirect();
        $this->assertFalse($shift->fresh()->isOpen());
        $this->assertEquals(125.00, $shift->fresh()->final_amount_expected);
        $this->assertEquals(0, $shift->fresh()->difference);
    }

    public function test_seller_can_only_read_own_shift_and_blind_props_hide_expected_amount(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller, 'email_verified_at' => now()]);
        $otherSeller = User::factory()->create(['role' => UserRole::Seller, 'email_verified_at' => now()]);
        $ownShift = CashShift::create(['user_id' => $seller->id, 'opened_at' => now(), 'status' => 'closed', 'initial_amount' => 0, 'final_amount_expected' => 100, 'final_amount_reported' => 90, 'difference' => -10]);
        $otherShift = CashShift::create(['user_id' => $otherSeller->id, 'opened_at' => now(), 'status' => 'open', 'initial_amount' => 0]);

        $this->actingAs($seller)
            ->get('/cashier')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cashier/Index')
                ->where('canAudit', false)
                ->has('shifts.data', 1)
                ->missing('shifts.data.0.final_amount_expected'));

        $this->get('/cashier/'.$ownShift->id)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cashier/Show')
                ->missing('shift.final_amount_expected'));

        $this->get('/cashier/'.$otherShift->id)->assertForbidden();
    }

    public function test_financial_user_can_audit_shifts_and_view_expected_amount(): void
    {
        $financial = User::factory()->create(['role' => UserRole::Financial, 'email_verified_at' => now()]);
        $seller = User::factory()->create(['role' => UserRole::Seller, 'email_verified_at' => now()]);
        $shift = CashShift::create(['user_id' => $seller->id, 'opened_at' => now(), 'status' => 'closed', 'initial_amount' => 50, 'final_amount_expected' => 80, 'final_amount_reported' => 80, 'difference' => 0]);

        $this->actingAs($financial)
            ->get('/cashier')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cashier/Index')
                ->where('canAudit', true)
                ->where('shifts.data.0.final_amount_expected', 80));

        $this->get('/cashier/'.$shift->id)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Cashier/Show')
                ->where('canAudit', true)
                ->where('shift.final_amount_expected', 80));
    }

    public function test_money_validation_accepts_two_decimal_digits_and_rejects_more(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller, 'email_verified_at' => now()]);

        $this->actingAs($seller)
            ->post('/cashier/open', ['initial_amount' => 10.555])
            ->assertSessionHasErrors('initial_amount');

        $this->post('/cashier/open', ['initial_amount' => 10.55])
            ->assertSessionHasNoErrors();
    }

    public function test_seller_without_verified_email_cannot_open_shift(): void
    {
        $seller = User::factory()->create(['role' => UserRole::Seller, 'email_verified_at' => null]);

        $this->actingAs($seller)
            ->post('/cashier/open', ['initial_amount' => 10])
            ->assertForbidden();
    }
}
