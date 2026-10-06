<?php

namespace Tests\Feature\CashShift;

use App\Actions\AddCashMovementAction;
use App\Actions\CloseCashShiftAction;
use App\Actions\OpenCashShiftAction;
use App\Enums\CashMovementType;
use App\Enums\CashShiftStatus;
use App\Enums\SaleStatus;
use App\Enums\UserRole;
use App\Models\CashShift;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashShiftLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_cash_shift(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $openAction = app(OpenCashShiftAction::class);

        $shift = $openAction($user->id, 150.00, 'Abertura de teste com R$ 150');

        $this->assertEquals(CashShiftStatus::Open, $shift->status);
        $this->assertEquals(150.00, $shift->initial_amount);
        $this->assertNull($shift->closed_at);
        $this->assertDatabaseHas('cash_shifts', [
            'id' => $shift->id,
            'user_id' => $user->id,
            'initial_amount' => 150.00,
            'status' => 'open',
        ]);
    }

    public function test_user_cannot_open_two_shifts_simultaneously(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $openAction = app(OpenCashShiftAction::class);

        $openAction($user->id, 100.00);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Já existe um caixa aberto para este operador.');

        $openAction($user->id, 50.00);
    }

    public function test_can_add_supply_and_bleed_movements(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $openAction = app(OpenCashShiftAction::class);
        $moveAction = app(AddCashMovementAction::class);

        $shift = $openAction($user->id, 100.00);

        $supply = $moveAction($shift, $user->id, CashMovementType::Supply, 50.00, 'Fundo adicional');
        $bleed = $moveAction($shift, $user->id, CashMovementType::Bleed, 30.00, 'Sangria de segurança');

        $this->assertEquals(50.00, $supply->amount);
        $this->assertEquals(30.00, $bleed->amount);
        $this->assertDatabaseHas('cash_movements', ['id' => $supply->id, 'type' => 'supply']);
        $this->assertDatabaseHas('cash_movements', ['id' => $bleed->id, 'type' => 'bleed']);
    }

    public function test_stale_shift_cannot_receive_movement_after_database_closure(): void
    {
        $user = User::factory()->create(['role' => UserRole::Seller, 'email_verified_at' => now()]);
        $shift = app(OpenCashShiftAction::class)($user->id, 10);
        $staleShift = CashShift::findOrFail($shift->id);

        $shift->update(['status' => CashShiftStatus::Closed, 'closed_at' => now()]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Não é possível movimentar um caixa fechado.');

        app(AddCashMovementAction::class)($staleShift, $user->id, CashMovementType::Supply, 5, 'Troco');
    }

    public function test_stale_shift_cannot_be_closed_twice(): void
    {
        $user = User::factory()->create(['role' => UserRole::Seller, 'email_verified_at' => now()]);
        $shift = app(OpenCashShiftAction::class)($user->id, 10);
        $staleShift = CashShift::findOrFail($shift->id);

        app(CloseCashShiftAction::class)($shift, 10);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Este caixa já se encontra fechado.');

        app(CloseCashShiftAction::class)($staleShift, 10);
    }

    public function test_close_cash_shift_calculates_expected_amount_and_difference(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $customer = Customer::factory()->create();
        $dinheiro = PaymentMethod::factory()->create(['name' => 'Dinheiro']);
        $pix = PaymentMethod::factory()->create(['name' => 'PIX']);

        $openAction = app(OpenCashShiftAction::class);
        $moveAction = app(AddCashMovementAction::class);
        $closeAction = app(CloseCashShiftAction::class);

        // Initial: 100
        $shift = $openAction($user->id, 100.00);

        // Supply +50, Bleed -20 -> Expected cash so far: 130
        $moveAction($shift, $user->id, CashMovementType::Supply, 50.00, 'Troco');
        $moveAction($shift, $user->id, CashMovementType::Bleed, 20.00, 'Sangria');

        // Cash Sale: +70
        $saleCash = Sale::factory()->create([
            'user_id' => $user->id,
            'cash_shift_id' => $shift->id,
            'customer_id' => $customer->id,
            'payment_method_id' => $dinheiro->id,
            'status' => SaleStatus::Completed,
            'total_amount' => 70.00,
        ]);
        SalePayment::create([
            'sale_id' => $saleCash->id,
            'payment_method_id' => $dinheiro->id,
            'amount' => 70.00,
        ]);

        // PIX Sale: +200 (Not cash in drawer)
        $salePix = Sale::factory()->create([
            'user_id' => $user->id,
            'cash_shift_id' => $shift->id,
            'customer_id' => $customer->id,
            'payment_method_id' => $pix->id,
            'status' => SaleStatus::Completed,
            'total_amount' => 200.00,
        ]);
        SalePayment::create([
            'sale_id' => $salePix->id,
            'payment_method_id' => $pix->id,
            'amount' => 200.00,
        ]);

        // Expected in drawer = 100 + 50 - 20 + 70 = 200.00
        // Operator counts 195.00 (R$ 5 missing)
        $closedShift = $closeAction($shift, 195.00, 'Falta de R$ 5,00');

        $this->assertEquals(CashShiftStatus::Closed, $closedShift->status);
        $this->assertEquals(200.00, $closedShift->final_amount_expected);
        $this->assertEquals(195.00, $closedShift->final_amount_reported);
        $this->assertEquals(-5.00, $closedShift->difference);
        $this->assertNotNull($closedShift->closed_at);
    }

    public function test_manual_movement_endpoint_rejects_receipt_type(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        app(OpenCashShiftAction::class)($user->id, 100.00);

        $this->actingAs($user)->post(route('cashier.movement'), [
            'type' => CashMovementType::Receipt->value,
            'amount' => 10.00,
            'reason' => 'Tentativa de forjar recebimento',
        ])->assertSessionHasErrors('type');

        $this->assertDatabaseCount('cash_movements', 0);
    }

    public function test_manual_action_cannot_create_receipt_movements(): void
    {
        $user = User::factory()->create(['role' => UserRole::Admin, 'email_verified_at' => now()]);
        $shift = app(OpenCashShiftAction::class)($user->id, 100.00);

        try {
            app(AddCashMovementAction::class)($shift, $user->id, CashMovementType::Receipt, 10.00, 'Forjado');
            $this->fail('O registro manual de recebimento deveria ser bloqueado.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('baixa de parcelas', $e->getMessage());
        }

        $this->assertDatabaseCount('cash_movements', 0);
    }
}
