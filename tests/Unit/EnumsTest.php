<?php

namespace Tests\Unit;

use App\Enums\ExpenseStatus;
use App\Enums\ExpenseType;
use App\Enums\InstallmentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class EnumsTest extends TestCase
{
    public function test_user_roles_have_expected_values_and_labels(): void
    {
        $this->assertSame('admin', UserRole::Admin->value);
        $this->assertSame('seller', UserRole::Seller->value);
        $this->assertSame('financial', UserRole::Financial->value);

        $this->assertSame('Administrador', UserRole::Admin->label());
        $this->assertSame('Vendedor', UserRole::Seller->label());
        $this->assertSame('Financeiro', UserRole::Financial->label());
    }

    public function test_sale_statuses_have_expected_values_and_labels(): void
    {
        $this->assertSame('pending', SaleStatus::Pending->value);
        $this->assertSame('completed', SaleStatus::Completed->value);
        $this->assertSame('cancelled', SaleStatus::Cancelled->value);

        $this->assertSame('Pendente', SaleStatus::Pending->label());
        $this->assertSame('Concluída', SaleStatus::Completed->label());
        $this->assertSame('Cancelada', SaleStatus::Cancelled->label());
    }

    public function test_stock_movement_types_and_polarities(): void
    {
        $this->assertTrue(StockMovementType::SaleCancel->isPositive());
        $this->assertTrue(StockMovementType::InitialStock->isPositive());
        $this->assertFalse(StockMovementType::Sale->isPositive());
        $this->assertFalse(StockMovementType::ManualAdjustment->isPositive());
        $this->assertFalse(StockMovementType::Correction->isPositive());

        $this->assertSame('Venda', StockMovementType::Sale->label());
        $this->assertSame('Cancelamento de Venda', StockMovementType::SaleCancel->label());
    }

    public function test_installment_and_expense_enums(): void
    {
        $this->assertSame('paid', InstallmentStatus::Paid->value);
        $this->assertSame('Pago', InstallmentStatus::Paid->label());

        $this->assertSame('expense', ExpenseType::Expense->value);
        $this->assertSame('income', ExpenseType::Income->value);
        $this->assertSame('Despesa', ExpenseType::Expense->label());
        $this->assertSame('Receita', ExpenseType::Income->label());

        $this->assertSame('overdue', ExpenseStatus::Overdue->value);
        $this->assertSame('Vencido', ExpenseStatus::Overdue->label());
    }
}
