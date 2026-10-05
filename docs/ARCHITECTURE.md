# Arquitetura do Sistema de Vendas

## 1. Visão Geral e Filosofia

Este documento estabelece o padrão de arquitetura de software para a evolução do **Sistema de Vendas**. O projeto adota uma **Arquitetura Laravel Pragmática** orientada a casos de uso de negócio, eliminando overengineering (sem DDD cerimonial, sem repositórios fakes sobre o Eloquent, sem microserviços prematuros) e garantindo robustez transacional, segurança defensiva e separação clara de responsabilidades.

A aplicação opera em duas frentes independentes:
1. **Web Admin Application**: Laravel + Inertia.js + Vue 3 + TypeScript + TailwindCSS.
2. **External REST API**: Endpoints versionados em `/api/v1/*` utilizando Laravel Sanctum, JsonResource e Form Requests dedicados.

---

## 2. Diagrama de Camadas da Aplicação

```text
[ Cliente Web: Vue 3 + Inertia ]       [ Cliente Externo: HTTP / REST ]
              │                                        │
              ▼                                        ▼
    [ Web Controllers ]                        [ Api/V1 Controllers ]
              │                                        │
              ├──────────► [ Form Requests ] ◄─────────┤
              │            (Validação Estrutural)
              ▼
       [ Policies / Gates ] (Autorização Efetiva)
              │
              ▼
   [ Actions / Use Cases ] (Regras de Negócio e Casos de Uso Únicos)
              │
              ├──► [ DTOs ] (Transporte Tipado e Imutável)
              ├──► [ Domain Exceptions ] (Falhas Previsíveis de Negócio)
              ├──► [ StockService / Movimentações ] (Lock e Auditoria)
              ├──► [ DB::transaction() + lockForUpdate() ] (Atomicidade)
              │
              ▼
     [ Eloquent Models & Enums ]
              │
              ▼
 [ Database: MySQL / Índices / Foreign Keys / Constraints ]
```

---

## 3. Responsabilidade de Cada Componente

### 3.1 HTTP Controllers
- **Papel**: Adaptadores de entrada HTTP.
- **Regras**:
  - Máximo de 15 a 30 linhas por método.
  - Receber `FormRequest` validado.
  - Executar autorização (`$this->authorize()` ou `authorizeResource`).
  - Mapear request para DTO ou invocar Action correspondente.
  - Retornar resposta (`Inertia::render`, `redirect()`, ou `JsonResource`).
  - **Proibido**: cálculos monetários, transações diretas com queries espalhadas e manipulação de estoque direto no controller.

### 3.2 Form Requests
- **Papel**: Validação estrutural de entrada e autorização básica de formato.
- **Regras**:
  - Regras explícitas com mensagens claras.
  - Não confiar em preços, subtotais ou cálculos enviados pelo frontend.
  - Validação de unicidade e existência com constraints seguras.

### 3.3 Actions (Casos de Uso)
- **Papel**: Cada Action representa uma operação de negócio específica (`CreateSaleAction`, `CancelSaleAction`, `AdjustStockAction`, `PayInstallmentAction`).
- **Regras**:
  - Invocáveis (`__invoke`) ou com métodos descritivos de comando.
  - Executam dentro de `DB::transaction()` quando envolvem múltiplas mutações.
  - Lançam exceções de domínio tipadas (`InsufficientStockException`, `SaleCancellationException`).

### 3.4 Query Objects & Services
- **Query Objects**: Extraem consultas complexas com filtros, agregações e paginações (ex.: `SalesQuery`, `DashboardMetricsQuery`, `ExpenseReportQuery`), prevenindo N+1 e mantendo Controllers e Models limpos.
- **Domain Services**: Utilizados apenas para lógica de domínio compartilhada entre múltiplos casos de uso (ex.: cálculo e distribuição de centavos em parcelas).

### 3.5 Models e Enums
- **Models**: Focados em relacionamentos, casts nativos, scopes e mutators puros.
- **PHP 8.2+ Backed Enums**: Eliminação total de *magic strings*:
  - `SaleStatus` (`pending`, `completed`, `cancelled`)
  - `ExpenseStatus` (`pending`, `paid`, `overdue`, `cancelled`)
  - `ExpenseType` (`expense`, `income`)
  - `InstallmentStatus` (`pending`, `paid`, `overdue`, `cancelled`)
  - `UserRole` (`admin`, `seller`, `financial`)
  - `StockMovementType` (`sale`, `sale_cancel`, `manual_adjustment`, `initial_stock`, `correction`)

---

## 4. Regras Críticas de Domínio

### 4.1 Autoridade Monetária e Preço do Produto
- O frontend envia apenas: `product_id`, `quantity`, `discount` e dados de parcelamento.
- O backend consulta o banco de dados com lock, busca o `price` oficial do produto, calcula `subtotal = price * quantity`, total bruto, valida regras de desconto e calcula total líquido.
- Valores monetários são mantidos em `decimal(10,2)` no banco e manipulados com proteção contra imprecisão de floats (arredondamentos consistentes em centavos e distribuição de sobras em parcelas).

### 4.2 Concorrência de Estoque e Transações
- Ao processar uma venda, o estoque do produto é consultado e travado na transação:
  ```php
  $product = Product::where('id', $item->productId)->lockForUpdate()->firstOrFail();
  if ($product->stock < $item->quantity) {
      throw new InsufficientStockException($product->name, $item->quantity, $product->stock);
  }
  $product->decrement('stock', $item->quantity);
  ```
- Criação imediata do registro em `stock_movements` com `previous_stock`, `new_stock`, `quantity`, `type = 'sale'`, `reference_id = sale.id`.

### 4.3 Ciclo de Vida da Venda e Exclusão vs Cancelamento
- Venda não é apenas soft-deleted. Possui status de negócio:
  - `completed`: venda efetivada, estoque decrementado, parcelas ativas.
  - `cancelled`: venda cancelada, estoque devolvido via `stock_movements` (`sale_cancel`), parcelas abertas canceladas.
  - Vendas com parcelas pagas não podem ser excluídas/canceladas sem estorno formal.
- Soft Delete é mantido para fins de retenção e conformidade, mas não substitui status de domínio.

### 4.4 Autorização e Segurança
- Todo endpoint valida autorização via Policy.
- Uso de `authorizeResource()` nos controllers Resource ou verificação explícita em cada ação.
- Eliminação do método `isAdmin()` baseado em e-mail hardcoded; adoção da coluna `role` com enum `UserRole`.

---

## 5. Arquitetura Frontend: Vue 3 + Inertia.js

- **Paradigma**: Single-Page App feeling com simplicidade de desenvolvimento monolítico via Inertia.js.
- **Padrão**: Vue 3 `<script setup lang="ts">`, Composition API estrita.
- **Tipagem**: Modelos e props tipados via TypeScript interfaces em `resources/js/Types`.
- **Componentização**:
  - `Layouts/`: Layout autenticado com Sidebar responsiva, Topbar, notificações e perfil.
  - `Components/UI/`: Botões, inputs monetários com máscara, selects pesquisáveis, modals, badges, skeletons.
  - `Pages/`: Componentes de página correspondentes às rotas Laravel.

---

## 6. Qualidade de Código e Pipeline de CI

- **Linter e Estilo**: Laravel Pint (PSR-12).
- **Análise Estática**: PHPStan / Larastan no nível 6+.
- **Testes Automatizados**: PHPUnit / Pest cobrindo fluxos críticos de venda, concorrência de estoque, parcelamento, regras de autorização e endpoints da API REST.
- **CI**: GitHub Actions automatizado em todo PR validando linting, análise estática, testes unitários/feature e build de assets frontend.
