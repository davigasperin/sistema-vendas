<?php

namespace Tests\Feature\Dashboard;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Enums\SaleStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SaleItem;
use App\Models\User;
use App\Queries\ExpenseSummaryQuery;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->has('salesStats')
            ->has('latestSales')
            ->has('paymentMethods')
            ->has('financialSummary')
            ->has('recentTransactions')
        );
    }

    public function test_dashboard_metrics_exclude_cancelled_sales(): void
    {
        Sale::factory()->create([
            'total_amount' => 300.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);

        Sale::factory()->create([
            'total_amount' => 500.00,
            'status' => SaleStatus::Cancelled,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('salesStats.total', 1)
            ->where('salesStats.month', 300)
        );
    }

    public function test_dashboard_includes_receivable_amount_sums_excluding_invalid_sales(): void
    {
        $validSale = Sale::factory()->create(['status' => SaleStatus::Completed, 'installments' => 2]);
        $cancelledSale = Sale::factory()->create(['status' => SaleStatus::Cancelled, 'installments' => 2]);

        SaleInstallment::factory()->create([
            'sale_id' => $cancelledSale->id,
            'amount' => 999.00,
            'due_date' => now()->subDay()->toDateString(),
            'is_paid' => false,
        ]);
        SaleInstallment::factory()->create([
            'sale_id' => $validSale->id,
            'amount' => 100.00,
            'due_date' => now()->addDays(5)->toDateString(),
            'is_paid' => false,
        ]);
        SaleInstallment::factory()->create([
            'sale_id' => $validSale->id,
            'amount' => 40.00,
            'due_date' => now()->subDay()->toDateString(),
            'is_paid' => false,
        ]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard/Index')
            ->where('salesStats.pendingInstallmentsCount', 2)
            ->where('salesStats.pendingInstallmentsAmount', 140)
            ->where('salesStats.overdueInstallmentsCount', 1)
            ->where('salesStats.overdueInstallmentsAmount', 40)
        );
    }

    public function test_product_service_stats_aggregates_without_n_plus_one(): void
    {
        $product = Product::factory()->create(['price' => 25.00]);
        $sale = Sale::factory()->create();

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 4,
            'unit_price' => 25.00,
            'subtotal' => 100.00,
        ]);

        $service = app(ProductService::class);
        $stats = $service->getProductStats($product);

        $this->assertEquals(4, $stats['totalSold']);
        $this->assertEquals(100.00, $stats['totalRevenue']);
    }

    public function test_financial_summary_calculates_balance_and_overdue(): void
    {
        $category = ExpenseCategory::factory()->create();

        // Receita manual paga: R$ 200,00
        Expense::factory()->create([
            'category_id' => $category->id,
            'type' => ExpenseType::Income,
            'status' => ExpenseStatus::Paid,
            'amount' => 200.00,
            'paid_date' => now()->format('Y-m-d'),
        ]);

        // Despesa paga: R$ 50,00
        Expense::factory()->create([
            'category_id' => $category->id,
            'type' => ExpenseType::Expense,
            'status' => ExpenseStatus::Paid,
            'amount' => 50.00,
            'paid_date' => now()->format('Y-m-d'),
        ]);

        // Despesa vencida: R$ 30,00
        Expense::factory()->create([
            'category_id' => $category->id,
            'type' => ExpenseType::Expense,
            'status' => ExpenseStatus::Pending,
            'amount' => 30.00,
            'due_date' => now()->subDays(2)->format('Y-m-d'),
        ]);

        $summaryQuery = new ExpenseSummaryQuery;
        $summary = $summaryQuery->getSummaryByPeriod();

        $this->assertEquals(200.00, $summary['income']['manual']);
        $this->assertEquals(50.00, $summary['expenses']['paid']);
        $this->assertEquals(30.00, $summary['expenses']['pending']);
        $this->assertEquals(1, $summary['overdue_count']);
        $this->assertEquals(150.00, $summary['balance']);
    }
}
