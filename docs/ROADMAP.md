# Roadmap de Evolução — Sistema de Vendas v2

Este roadmap organiza a transformação incremental do projeto de teste técnico para aplicação profissional de produção e portfólio de alto nível.

---

## Fase 1: Fundação, Caracterização e Testes de Regressão (Baseline)
- [x] Inicialização do repositório Git com branch `feat/portfolio-v2` e primeiro commit baseline.
- [x] Configuração e correção do ambiente de testes (`phpunit.xml` com SQLite em memória).
- [ ] Criação de Model Factories completas:
  - `CustomerFactory`, `ProductFactory`, `PaymentMethodFactory`, `SaleFactory`, `SaleItemFactory`, `SaleInstallmentFactory`, `ExpenseCategoryFactory`, `ExpenseFactory`.
- [ ] Criação de testes de caracterização para cobrir o comportamento atual dos fluxos críticos antes de qualquer refatoração.
- [ ] Configuração do PHPStan / Larastan e Laravel Pint.

---

## Fase 2: Domínio, Segurança e Consistência Backend
- [ ] **Enums do Domínio**:
  - `UserRole`, `SaleStatus`, `ExpenseStatus`, `ExpenseType`, `InstallmentStatus`, `StockMovementType`.
- [ ] **Auditoria e Ajuste no Banco de Dados**:
  - Migration para adicionar coluna `role` na tabela `users` (removendo e-mail hardcoded de admin).
  - Migration para tabela `stock_movements` (auditoria e rastreabilidade total do estoque).
  - Migration para adicionar `status` à tabela `sales`.
  - Índices em chaves estrangeiras e campos de filtro frequente (`customer_id`, `created_at`, `due_date`, `status`, etc.).
- [ ] **Políticas e Autorização (RBAC)**:
  - Corrigir amarração de Policies nos controllers (`authorizeResource` / `$this->authorize()`).
  - Corrigir rotas no `web.php` (resolver sombreamento de `/expenses/report` e rota de restore com `{sale}` soft-deleted).
- [ ] **DTOs e Form Requests Reforçados**:
  - `CreateSaleDTO`, `UpdateSaleDTO`, `StockAdjustmentDTO`, `ExpenseFilterDTO`.
  - Remoção de valores calculados (preço, subtotal) enviados pelo frontend; o backend assume autoridade total.

---

## Fase 3: Core Transacional — Vendas, Estoque e Parcelas
- [ ] **Concorrência e Locks no Estoque**:
  - Refatorar `CreateSaleAction` e `UpdateSaleAction` com `lockForUpdate()` e transações atômicas.
  - Registro de movimentações na tabela `stock_movements`.
  - Testes cobrindo cenários de tentativa de venda concorrente sem saldo negativo.
- [ ] **Cancelamento e Exclusão Segura de Vendas**:
  - `CancelSaleAction`: devolução transacional ao estoque com tipo `sale_cancel` e cancelamento de parcelas pendentes.
  - Validação para impedir cancelamento/exclusão de vendas com parcelas já liquidadas.
- [ ] **Cálculo Financeiro Exato e Parcelamento**:
  - `GenerateInstallmentsAction` ajustado para garantir que a soma das parcelas coincida com o total líquido ao centavo, distribuindo diferenças de arredondamento.
  - `MarkInstallmentPaidAction` com atualização consistente de data e método de pagamento.

---

## Fase 4: Despesas, Clientes e Dashboard
- [ ] **Módulo de Despesas**:
  - Remoção de mutações dentro de consultas de leitura (eliminar `checkOverdue` em queries).
  - Query Object `ExpenseSummaryQuery` para relatórios financeiros sem carregar coleções inteiras em memória.
- [ ] **Dashboard Otimizado**:
  - `DashboardMetricsQuery`: agregações no banco de dados por período e ano corrente (corrigir bug de `whereMonth` sem ano).
  - Métricas completas: faturamento líquido, despesas pagas/pendentes, ticket médio, produtos com estoque crítico, parcelas a vencer e gráfico de vendas.

---

## Fase 5: API REST v1
- [ ] Versionamento `/api/v1/*` com rotas segregadas do painel web.
- [ ] Implementação de `JsonResource` para `ProductResource`, `CustomerResource`, `SaleResource`, `ExpenseResource`.
- [ ] Endpoints protegidos com Laravel Sanctum, rate limiting adequado e paginação padronizada.
- [ ] Testes de Feature para todos os endpoints da API com asserts de estrutura JSON e HTTP status codes.
- [ ] Documentação OpenAPI / Swagger (L5-Swagger ou especificação YAML limpa).

---

## Fase 6: Modernização do Frontend com Vue 3 + Inertia.js
- [ ] Instalação e configuração de `@inertiajs/vue3`, `vue`, `@vitejs/plugin-vue`, `typescript` e `vue-tsc`.
- [ ] Configuração do `app.ts` e layout base `AppLayout.vue` com Sidebar moderna, Topbar e feedback visual.
- [ ] Migração incremental de páginas:
  1. `Dashboard/Index.vue` (cards de métricas, gráficos e tabelas resumidas).
  2. `Products/Index.vue`, `Products/Form.vue`, `Products/StockModal.vue`.
  3. `Customers/Index.vue`, `Customers/Form.vue`, `Customers/Show.vue`.
  4. `Sales/Index.vue`, `Sales/Create.vue` (vitrine do projeto: autocomplete de cliente/produtos, itens dinâmicos, resumo financeiro em tempo real e parcelamento com prevenção de duplo clique).
  5. `Expenses/Index.vue`, `Expenses/Form.vue`, `Expenses/Report.vue`.
  6. Telas de Auth/Profile migradas com elegância.

---

## Fase 7: Docker, CI/CD e Observabilidade
- [ ] `docker-compose.yml` completo (PHP 8.2-FPM, Nginx, MySQL 8, Redis opcional) com script de inicialização rápido.
- [ ] Pipeline no GitHub Actions (`.github/workflows/ci.yml`) rodando Pint, PHPStan, PHPUnit, vue-tsc e Vite build.
- [ ] Logging estruturado em operações sensíveis (criação de venda, cancelamento, alteração manual de estoque).

---

## Fase 8: Documentação de Portfólio e Finalização
- [ ] `README.md` de nível executivo com badges, arquitetura explicada, prints, guia Docker em 1 comando e decisões de engenharia.
- [ ] Criação de ADRs (`docs/DECISIONS/`) documentando decisões chaves (ex.: concorrência de estoque, Inertia vs SPA desacoplado, precisão monetária).
