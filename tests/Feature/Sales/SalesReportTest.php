<?php

namespace Tests\Feature\Sales;

use App\Enums\SaleStatus;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\User;
use App\Queries\SalesReportQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $seller;

    private User $financial;

    private Customer $customer;

    private PaymentMethod $cashMethod;

    private PaymentMethod $pixMethod;

    private PaymentMethod $cardMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->seller = User::factory()->seller()->create();
        $this->financial = User::factory()->financial()->create();
        $this->customer = Customer::factory()->create();

        $this->cashMethod = PaymentMethod::factory()->create([
            'name' => 'Dinheiro',
            'active' => true,
        ]);
        $this->pixMethod = PaymentMethod::factory()->create([
            'name' => 'PIX',
            'active' => true,
        ]);
        $this->cardMethod = PaymentMethod::factory()->create([
            'name' => 'Cartão de Crédito',
            'active' => true,
        ]);
    }

    public function test_access_permissions_for_sales_report(): void
    {
        // 1. Unauthenticated -> Redirect to login
        $this->get(route('sales.report'))
            ->assertRedirect(route('login'));

        // 2. Admin -> Allowed
        $this->actingAs($this->admin)
            ->get(route('sales.report'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Report')
                ->has('report')
                ->has('startDate')
                ->has('endDate')
            );

        // 3. Seller -> Allowed
        $this->actingAs($this->seller)
            ->get(route('sales.report'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Sales/Report'));

        // 4. Financial -> Allowed
        $this->actingAs($this->financial)
            ->get(route('sales.report'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Sales/Report'));

        // 5. Unverified user -> 403 Forbidden
        $unverifiedUser = User::factory()->seller()->unverified()->create();
        $this->actingAs($unverifiedUser)
            ->get(route('sales.report'))
            ->assertForbidden();
    }

    public function test_metrics_calculation_ignores_cancelled_and_soft_deleted_sales(): void
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        // Venda Concluída 1: Líquido 150.00, Desconto 15.00
        Sale::factory()->create([
            'user_id' => $this->seller->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 150.00,
            'discount' => 15.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);

        // Venda Concluída 2: Líquido 350.00, Desconto 35.00
        Sale::factory()->create([
            'user_id' => $this->seller->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->pixMethod->id,
            'total_amount' => 350.00,
            'discount' => 35.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);

        // Venda Cancelada: deve ser ignorada
        Sale::factory()->create([
            'user_id' => $this->seller->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 1000.00,
            'discount' => 100.00,
            'status' => SaleStatus::Cancelled,
            'created_at' => now(),
        ]);

        // Venda Excluída (Soft Delete): deve ser ignorada
        $softDeletedSale = Sale::factory()->create([
            'user_id' => $this->seller->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 500.00,
            'discount' => 50.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);
        $softDeletedSale->delete();

        $query = new SalesReportQuery;
        $report = $query->getReport($startDate, $endDate);

        $this->assertEquals(2, $report['totals']['total_sales']);
        $this->assertEquals(500.00, $report['totals']['net_amount']);
        $this->assertEquals(50.00, $report['totals']['discount_amount']);
        $this->assertEquals(550.00, $report['totals']['gross_amount']);
        $this->assertEquals(250.00, $report['totals']['average_ticket']);
    }

    public function test_aggregates_payment_methods_correctly_with_values_and_percentages(): void
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        // Venda 1: R$ 100 via Dinheiro
        $sale1 = Sale::factory()->create([
            'user_id' => $this->seller->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 100.00,
            'discount' => 0.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);

        // Venda 2: R$ 300 via PIX
        $sale2 = Sale::factory()->create([
            'user_id' => $this->seller->id,
            'payment_method_id' => $this->pixMethod->id,
            'total_amount' => 300.00,
            'discount' => 0.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);

        // Venda 3 (Multi-pagamento): Total 200.00 (PIX 100.00 + Cartão 100.00)
        $sale3 = Sale::factory()->create([
            'user_id' => $this->seller->id,
            'payment_method_id' => $this->pixMethod->id,
            'total_amount' => 200.00,
            'discount' => 0.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);
        // Apaga o pagamento padrão criado pelo factory e insere os multipagamentos
        $sale3->payments()->delete();
        SalePayment::create([
            'sale_id' => $sale3->id,
            'payment_method_id' => $this->pixMethod->id,
            'amount' => 100.00,
            'change_given' => 0.00,
        ]);
        SalePayment::create([
            'sale_id' => $sale3->id,
            'payment_method_id' => $this->cardMethod->id,
            'amount' => 100.00,
            'change_given' => 0.00,
        ]);

        // Venda Cancelada com R$ 800 em dinheiro -> DEVE SER IGNORADA
        $saleCancelled = Sale::factory()->create([
            'user_id' => $this->seller->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 800.00,
            'status' => SaleStatus::Cancelled,
            'created_at' => now(),
        ]);

        $query = new SalesReportQuery;
        $report = $query->getReport($startDate, $endDate);

        // Total apurado = 100 (dinheiro) + 300 (pix) + 100 (pix) + 100 (cartao) = 600.00
        // PIX: 400.00 (66.7%)
        // Dinheiro: 100.00 (16.7%)
        // Cartão: 100.00 (16.7%)
        $paymentMethods = collect($report['payment_methods'])->keyBy('name');

        $this->assertCount(3, $paymentMethods);

        $pix = $paymentMethods->get('PIX');
        $this->assertNotNull($pix);
        $this->assertEquals(400.00, $pix['total_amount']);
        $this->assertEquals(2, $pix['count']);
        $this->assertEquals(66.7, $pix['percentage']);

        $cash = $paymentMethods->get('Dinheiro');
        $this->assertNotNull($cash);
        $this->assertEquals(100.00, $cash['total_amount']);
        $this->assertEquals(1, $cash['count']);
        $this->assertEquals(16.7, $cash['percentage']);

        $card = $paymentMethods->get('Cartão de Crédito');
        $this->assertNotNull($card);
        $this->assertEquals(100.00, $card['total_amount']);
        $this->assertEquals(1, $card['count']);
        $this->assertEquals(16.7, $card['percentage']);
    }

    public function test_aggregates_top_10_products_and_sellers_correctly(): void
    {
        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $productA = Product::factory()->create(['name' => 'Camiseta Algodão', 'price' => 50.00]);
        $productB = Product::factory()->create(['name' => 'Calça Jeans', 'price' => 120.00]);
        $productC = Product::factory()->create(['name' => 'Tênis Esportivo', 'price' => 200.00]);

        $seller2 = User::factory()->seller()->create(['name' => 'Carlos Vendedor']);

        // Venda 1 pelo $seller: 5 camisetas (250.00) + 1 calça (120.00) = 370.00
        $sale1 = Sale::factory()->create([
            'user_id' => $this->seller->id,
            'payment_method_id' => $this->cashMethod->id,
            'total_amount' => 370.00,
            'discount' => 0.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale1->id,
            'product_id' => $productA->id,
            'quantity' => 5,
            'unit_price' => 50.00,
            'subtotal' => 250.00,
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale1->id,
            'product_id' => $productB->id,
            'quantity' => 1,
            'unit_price' => 120.00,
            'subtotal' => 120.00,
        ]);

        // Venda 2 pelo $seller2: 10 calças (1200.00)
        $sale2 = Sale::factory()->create([
            'user_id' => $seller2->id,
            'payment_method_id' => $this->pixMethod->id,
            'total_amount' => 1200.00,
            'discount' => 50.00,
            'status' => SaleStatus::Completed,
            'created_at' => now(),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $sale2->id,
            'product_id' => $productB->id,
            'quantity' => 10,
            'unit_price' => 120.00,
            'subtotal' => 1200.00,
        ]);

        // Venda 3 Cancelada com 50 Tênis -> DEVE SER IGNORADA
        $saleCancelled = Sale::factory()->create([
            'user_id' => $seller2->id,
            'payment_method_id' => $this->cardMethod->id,
            'total_amount' => 10000.00,
            'status' => SaleStatus::Cancelled,
            'created_at' => now(),
        ]);
        SaleItem::factory()->create([
            'sale_id' => $saleCancelled->id,
            'product_id' => $productC->id,
            'quantity' => 50,
            'unit_price' => 200.00,
            'subtotal' => 10000.00,
        ]);

        $query = new SalesReportQuery;
        $report = $query->getReport($startDate, $endDate);

        // Top Produtos:
        // #1: Calça Jeans (11 unidades, R$ 1320.00)
        // #2: Camiseta Algodão (5 unidades, R$ 250.00)
        // Tênis: 0 (pois venda foi cancelada)
        $topProducts = $report['top_products'];
        $this->assertCount(2, $topProducts);
        $this->assertEquals('Calça Jeans', $topProducts[0]['name']);
        $this->assertEquals(11, $topProducts[0]['quantity']);
        $this->assertEquals(1320.00, $topProducts[0]['revenue']);
        $this->assertEquals(120.00, $topProducts[0]['average_price']);

        $this->assertEquals('Camiseta Algodão', $topProducts[1]['name']);
        $this->assertEquals(5, $topProducts[1]['quantity']);
        $this->assertEquals(250.00, $topProducts[1]['revenue']);

        // Resumo Vendedores:
        // Carlos Vendedor: 1 venda, 1200.00 faturado, 50.00 desconto, ticket 1200.00
        // $seller: 1 venda, 370.00 faturado, 0.00 desconto, ticket 370.00
        $sellers = collect($report['sellers'])->keyBy('name');
        $this->assertCount(2, $sellers);

        $carlos = $sellers->get('Carlos Vendedor');
        $this->assertNotNull($carlos);
        $this->assertEquals(1, $carlos['sales_count']);
        $this->assertEquals(1200.00, $carlos['total_amount']);
        $this->assertEquals(50.00, $carlos['total_discount']);
        $this->assertEquals(1200.00, $carlos['average_ticket']);
    }

    public function test_filters_by_custom_date_range(): void
    {
        // Venda mês passado
        Sale::factory()->create([
            'user_id' => $this->seller->id,
            'total_amount' => 100.00,
            'status' => SaleStatus::Completed,
            'created_at' => now()->subMonths(2)->startOfMonth(),
        ]);

        // Venda dentro do período do teste
        $targetDate = now()->startOfMonth()->addDays(5);
        Sale::factory()->create([
            'user_id' => $this->seller->id,
            'total_amount' => 450.00,
            'status' => SaleStatus::Completed,
            'created_at' => $targetDate,
        ]);

        $startDate = now()->startOfMonth()->toDateString();
        $endDate = now()->endOfMonth()->toDateString();

        $response = $this->actingAs($this->admin)
            ->get(route('sales.report', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Sales/Report')
            ->where('report.totals.total_sales', 1)
            ->where('report.totals.net_amount', 450)
            ->has('report.daily_sales', 1)
        );
    }
}
