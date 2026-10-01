# Roadmap de Evolução — Sistema de Vendas v2

Transformação incremental de teste técnico para aplicação profissional de produção e portfólio.

**Status geral: 8/8 fases concluídas** | 62 testes | Pint ✓ | PHPStan ✓ | vue-tsc ✓ | Build ✓

---

## Fase 1: Fundação, Caracterização e Testes de Regressão ✅

- [x] Repositório Git inicializado em `feat/portfolio-v2`
- [x] `phpunit.xml` corrigido com SQLite em memória
- [x] 8 Model Factories (`Customer`, `Product`, `PaymentMethod`, `Sale`, `SaleItem`, `SaleInstallment`, `ExpenseCategory`, `Expense`)
- [x] Testes de caracterização: Sales, Products, Customers, Expenses (14 testes)
- [x] Laravel Pint e Larastan/PHPStan configurados (nível 5)

**Commit:** `78dd62a` — feat: establish domain factories characterization tests and static analysis

---

## Fase 2: Domínio, Segurança e Consistência Backend ✅

- [x] 6 Backed Enums: `UserRole`, `SaleStatus`, `ExpenseStatus`, `ExpenseType`, `InstallmentStatus`, `StockMovementType`
- [x] 5 migrations: `role` em users, `status` em sales, `payment_method_id` em installments, tabela `stock_movements`, índices compostos
- [x] `authorizeResource()` ativo em 4 controllers
- [x] Rotas corrigidas: `/expenses/report` antes do resource, `sales/{sale}/restore` com `withTrashed()`
- [x] DTOs imutáveis: `CreateSaleDTO`, `SaleItemDTO`, `StockAdjustmentDTO`
- [x] RBAC com `UserRole` enum (admin, seller, financial)

**Commit:** `0d66291` — feat: implement domain enums, migrations, stock movements, and policy authorization

---

## Fase 3: Core Transacional — Vendas, Estoque e Parcelas ✅

- [x] Backend price authority: servidor ignora `unit_price`/`subtotal` do cliente
- [x] `lockForUpdate()` em `CreateSaleAction`, `UpdateSaleAction`, `CancelSaleAction`
- [x] `StockMovement` auditável em toda mutação de estoque
- [x] `CancelSaleAction` com estorno transacional e proteção contra parcelas pagas
- [x] `GenerateInstallmentsAction` com distribuição exata de centavos (intdiv + %)
- [x] `InstallmentsSumRule` sem tolerância (igualdade ao centavo)
- [x] Testes: preço manipulado ignorado, ciclo de vida, centavos, proteção parcelas pagas

**Commit:** `2791601` — feat: implement transactional sales core with price authority stock locking and exact installments

---

## Fase 4: Despesas, Clientes e Dashboard ✅

- [x] `checkOverdue()` removido de consultas de leitura (sem UPDATE em SELECT)
- [x] `DashboardMetricsQuery`: agregações no banco, filtro por ano+mes, exclui canceladas
- [x] `ExpenseSummaryQuery`: balanço de receitas/despesas por período
- [x] `ProductService::getProductStats`: eliminado N+1 (agregação SQL com `SUM`)
- [x] Dashboard: 4 testes cobrindo métricas e balanço

**Commit:** `1bb35b2` — feat: implement query objects for dashboard and expenses and eliminate n+1 in product stats

---

## Fase 5: API REST v1 ✅

- [x] 6 JsonResources: `Product`, `Customer`, `Sale`, `SaleItem`, `SaleInstallment`, `Expense`
- [x] 5 Controllers: `Auth`, `Product`, `Customer`, `Sale`, `Expense` em `Api/V1`
- [x] Rotas `/api/v1/*` com Sanctum + `throttle:api`
- [x] `docs/API.md` com endpoints, payloads e exemplos
- [x] 12 testes de API (auth, products, customers, sales, expenses)

**Commit:** `ad7937a` — feat: implement versioned REST API v1 with Sanctum JsonResources and OpenAPI documentation

---

## Fase 6: Vue 3 + TypeScript + Inertia.js ✅

- [x] Inertia Laravel, Vue 3, TypeScript strict, Vite config com plugin Vue
- [x] `HandleInertiaRequests` middleware com auth + flash compartilhados
- [x] 5 componentes UI: `StatCard`, `Badge`, `Modal`, `Pagination`, `Toast`
- [x] `AppLayout.vue` com Sidebar responsiva e navegação
- [x] 22 páginas Vue: Dashboard (1), Products (4), Customers (4), Sales (4), Expenses (5)
- [x] 5 controllers migrados para `Inertia::render()`
- [x] `vue-tsc --noEmit` sem erros, build de produção OK

**Commit:** `faec4e9` — feat: migrate frontend to Vue 3 with TypeScript and Inertia.js

---

## Fase 7: Docker, CI/CD e Observabilidade ✅

- [x] `compose.yaml` (Laravel Sail): PHP 8.4, MySQL 8.4, Redis com healthchecks
- [x] `.env.example` atualizado com hosts do Sail
- [x] GitHub Actions: backend (Pint → PHPStan → PHPUnit) + frontend (vue-tsc → build)
- [x] Logging estruturado: criação/cancelamento de venda, ajuste de estoque

**Commit:** `4851659` — feat: add Docker Sail environment GitHub Actions CI and structured logging

---

## Fase 8: Documentação de Portfólio ✅

- [x] `README.md` profissional com badges, arquitetura, stack, guia Docker, API, decisões
- [x] 4 ADRs: Inertia+Vue, Money Handling, Stock Concurrency, API Versioning
- [x] `docs/ARCHITECTURE.md` e `docs/ROADMAP.md` atualizados
