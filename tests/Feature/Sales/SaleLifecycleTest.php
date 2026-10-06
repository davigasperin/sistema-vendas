<?php

namespace Tests\Feature\Sales;

use App\Actions\CancelSaleAction;
use App\Actions\CreateSaleAction;
use App\Actions\UpdateSaleAction;
use App\DTOs\CreateSaleDTO;
use App\DTOs\SaleItemDTO;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\Domain\SaleCancellationException;
use App\Models\Customer;
use App\Models\MonthClose;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Customer $customer;

    private PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create();
    }

    public function test_installments_are_split_without_cent_loss(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
            'stock' => 10,
        ]);

        $dto = new CreateSaleDTO(
            userId: $this->user->id,
            customerId: $this->customer->id,
            paymentMethodId: $this->paymentMethod->id,
            discount: 0.00,
            installments: 3,
            notes: null,
            items: [new SaleItemDTO($product->id, 1)],
        );

        $action = app(CreateSaleAction::class);
        $sale = $action($dto);

        $this->assertEquals(100.00, (float) $sale->total_amount);

        $installments = $sale->saleInstallments()->orderBy('installment_number')->get();
        $this->assertCount(3, $installments);

        // 100 / 3 deve gerar 33.34 na primeira parcela e 33.33 nas outras duas
        $this->assertEquals(33.34, (float) $installments[0]->amount);
        $this->assertEquals(33.33, (float) $installments[1]->amount);
        $this->assertEquals(33.33, (float) $installments[2]->amount);

        $sum = $installments->sum(fn ($i) => (float) $i->amount);
        $this->assertEquals(100.00, $sum);
    }

    public function test_updating_sale_restores_old_stock_and_decrements_new_stock(): void
    {
        $productA = Product::factory()->create(['price' => 50.00, 'stock' => 10]);
        $productB = Product::factory()->create(['price' => 30.00, 'stock' => 5]);

        $dto = new CreateSaleDTO(
            userId: $this->user->id,
            customerId: $this->customer->id,
            paymentMethodId: $this->paymentMethod->id,
            discount: 0,
            installments: 1,
            notes: null,
            items: [new SaleItemDTO($productA->id, 2)], // usa 2 de productA
        );

        $createAction = app(CreateSaleAction::class);
        $sale = $createAction($dto);

        $this->assertEquals(8, $productA->fresh()->stock);

        // Atualizar venda trocando productA por productB
        $updateAction = app(UpdateSaleAction::class);
        $updateAction($sale, [
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
            'installments' => 1,
            'discount' => 0,
            'items' => [
                ['product_id' => $productB->id, 'quantity' => 3],
            ],
        ]);

        // productA teve seu estoque de volta (8 + 2 = 10)
        $this->assertEquals(10, $productA->fresh()->stock);
        // productB consumiu 3 (5 - 3 = 2)
        $this->assertEquals(2, $productB->fresh()->stock);

        $this->assertEquals(90.00, (float) $sale->fresh()->total_amount);
    }

    public function test_cancelling_sale_restores_stock_and_cancels_installments(): void
    {
        $product = Product::factory()->create(['price' => 80.00, 'stock' => 5]);

        $dto = new CreateSaleDTO(
            userId: $this->user->id,
            customerId: $this->customer->id,
            paymentMethodId: $this->paymentMethod->id,
            discount: 0,
            installments: 2,
            notes: null,
            items: [new SaleItemDTO($product->id, 2)],
        );

        $createAction = app(CreateSaleAction::class);
        $sale = $createAction($dto);

        $this->assertEquals(3, $product->fresh()->stock);

        $cancelAction = app(CancelSaleAction::class);
        $cancelledSale = $cancelAction($sale, $this->user->id);

        $this->assertEquals(SaleStatus::Cancelled, $cancelledSale->status);
        $this->assertEquals(5, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => StockMovementType::SaleCancel,
            'quantity' => 2,
            'previous_stock' => 3,
            'new_stock' => 5,
        ]);
    }

    public function test_cannot_cancel_or_update_sale_with_paid_installments(): void
    {
        $product = Product::factory()->create(['price' => 100.00, 'stock' => 5]);

        $dto = new CreateSaleDTO(
            userId: $this->user->id,
            customerId: $this->customer->id,
            paymentMethodId: $this->paymentMethod->id,
            discount: 0,
            installments: 2,
            notes: null,
            items: [new SaleItemDTO($product->id, 1)],
        );

        $createAction = app(CreateSaleAction::class);
        $sale = $createAction($dto);

        // Quita uma das parcelas
        $firstInstallment = $sale->saleInstallments()->first();
        $firstInstallment->markAsPaid();

        $cancelAction = app(CancelSaleAction::class);
        $this->expectException(SaleCancellationException::class);
        $cancelAction($sale);
    }

    public function test_sale_creation_is_blocked_when_current_month_is_closed(): void
    {
        MonthClose::create([
            'year' => now()->year,
            'month' => now()->month,
            'totals' => [],
            'closed_by' => $this->user->id,
            'closed_at' => now(),
        ]);

        $product = Product::factory()->create(['price' => 50.00, 'stock' => 10]);
        $dto = new CreateSaleDTO(
            userId: $this->user->id,
            customerId: $this->customer->id,
            paymentMethodId: $this->paymentMethod->id,
            discount: 0,
            installments: 1,
            notes: null,
            items: [new SaleItemDTO($product->id, 1)],
        );

        try {
            app(CreateSaleAction::class)($dto);
            $this->fail('A criação de venda deveria ser bloqueada com o mês fechado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('month_close', $e->errors());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertEquals(10, $product->fresh()->stock);
    }

    public function test_sale_update_cancel_and_restore_are_blocked_in_closed_origin_month(): void
    {
        $product = Product::factory()->create(['price' => 60.00, 'stock' => 10]);
        $dto = new CreateSaleDTO(
            userId: $this->user->id,
            customerId: $this->customer->id,
            paymentMethodId: $this->paymentMethod->id,
            discount: 0,
            installments: 1,
            notes: null,
            items: [new SaleItemDTO($product->id, 1)],
        );
        $sale = app(CreateSaleAction::class)($dto);
        $softDeleted = app(CreateSaleAction::class)($dto);

        MonthClose::create([
            'year' => $sale->created_at->year,
            'month' => $sale->created_at->month,
            'totals' => [],
            'closed_by' => $this->user->id,
            'closed_at' => now(),
        ]);

        try {
            app(UpdateSaleAction::class)($sale, ['discount' => 1.00, 'installments' => 1]);
            $this->fail('A edição de venda deveria ser bloqueada com o mês de origem fechado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('month_close', $e->errors());
        }

        try {
            app(CancelSaleAction::class)($sale, $this->user->id);
            $this->fail('O cancelamento de venda deveria ser bloqueado com o mês de origem fechado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('month_close', $e->errors());
        }

        $softDeleted->delete();
        try {
            app(SaleService::class)->restoreSale($softDeleted);
            $this->fail('A restauração de venda deveria ser bloqueada com o mês de origem fechado.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('month_close', $e->errors());
        }

        $this->assertEquals(SaleStatus::Completed, $sale->fresh()->status);
        $this->assertTrue($softDeleted->fresh()->trashed());
        $this->assertEquals(8, $product->fresh()->stock);
    }
}
