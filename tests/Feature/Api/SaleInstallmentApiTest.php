<?php

namespace Tests\Feature\Api;

use App\Models\PaymentMethod;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleInstallmentApiTest extends TestCase
{
    use RefreshDatabase;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentMethod = PaymentMethod::factory()->create(['name' => 'PIX']);
    }

    public function test_financial_user_can_mark_installment_paid_via_api(): void
    {
        $financial = User::factory()->financial()->create();
        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);
        Sanctum::actingAs($financial);

        $response = $this->patchJson("/api/v1/installments/{$installment->id}/mark-paid", [
            'payment_method_id' => $this->paymentMethod->id,
            'paid_date' => now()->toDateString(),
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.is_paid', true);
        $response->assertJsonPath('data.payment_method_id', $this->paymentMethod->id);
        $this->assertTrue($installment->fresh()->is_paid);
    }

    public function test_seller_cannot_mark_other_sales_installment_paid_via_api(): void
    {
        $seller = User::factory()->seller()->create();
        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create(['user_id' => User::factory()->create()->id])->id,
        ]);
        Sanctum::actingAs($seller);

        $this->patchJson("/api/v1/installments/{$installment->id}/mark-paid", [
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertForbidden();

        $this->assertFalse($installment->fresh()->is_paid);
    }

    public function test_already_paid_installment_returns_422_instead_of_server_error_via_api(): void
    {
        $financial = User::factory()->financial()->create();
        $installment = SaleInstallment::factory()->paid()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);
        Sanctum::actingAs($financial);

        $this->patchJson("/api/v1/installments/{$installment->id}/mark-paid", [
            'payment_method_id' => $this->paymentMethod->id,
        ])->assertUnprocessable();
    }

    public function test_inactive_payment_method_is_rejected_via_api(): void
    {
        $financial = User::factory()->financial()->create();
        $inactive = PaymentMethod::factory()->inactive()->create(['name' => 'Cheque']);
        $installment = SaleInstallment::factory()->create([
            'sale_id' => Sale::factory()->create()->id,
        ]);
        Sanctum::actingAs($financial);

        $this->patchJson("/api/v1/installments/{$installment->id}/mark-paid", [
            'payment_method_id' => $inactive->id,
        ])->assertUnprocessable();

        $this->assertFalse($installment->fresh()->is_paid);
    }
}
