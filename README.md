<div align="center">

# Sistema de Vendas

**Aplicação ERP para gestão de vendas, estoque, clientes e finanças**

[![CI](https://github.com/davigasperin/sistema-vendas/actions/workflows/ci.yml/badge.svg)](https://github.com/davigasperin/sistema-vendas/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Vue.js](https://img.shields.io/badge/Vue.js-3-4FC08D?logo=vuedotjs&logoColor=white)](https://vuejs.org/)
[![TypeScript](https://img.shields.io/badge/TypeScript-strict-3178C6?logo=typescript&logoColor=white)](https://www.typescriptlang.org/)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

</div>

---

## Sobre o Projeto

Sistema completo de gerenciamento de vendas desenvolvido para operações reais de pequenos e médios negócios. Resolve o problema de controle manual de vendas, estoque desatualizado e falta de visibilidade financeira — substituindo planilhas por uma aplicação web com autoridade total do backend sobre preços, estoque concorrente travado e parcelamento sem perda de centavos.

## Funcionalidades

| Módulo | Capabilities |
|--------|-------------|
| **Vendas** | Registro atômico, cálculo de preço pelo servidor, múltiplas formas de pagamento, cancelamento com estorno de estoque |
| **Parcelamento** | Divisão exata em centavos, controle de vencimentos, quitação individual, proteção contra cancelamento com parcelas pagas |
| **Estoque** | Decremento transacional com `lockForUpdate`, movimentações auditáveis, alerta de estoque baixo, ajuste manual |
| **Clientes** | Cadastro completo, histórico de compras, busca por nome/email/telefone |
| **Despesas** | Contas a pagar, receitas manuais, categorias coloridas, relatório financeiro com balanço |
| **Dashboard** | Faturamento do mês, ticket médio, balanço líquido, parcelas vencidas, últimas vendas |
| **API REST** | Endpoints versionados `/api/v1`, autenticação Sanctum, rate limiting, respostas padronizadas |
| **Relatórios** | Geração de PDF de vendas via DomPDF |
| **RBAC** | Roles `admin`, `seller`, `financial` com políticas granulares por recurso |

## Arquitetura

```
app/
├── Actions/              # Casos de uso transacionais
│   ├── CreateSaleAction      # Backend price authority + lockForUpdate
│   ├── UpdateSaleAction      # Restauração atômica de estoque
│   ├── CancelSaleAction      # Estorno + cancelamento de parcelas
│   └── GenerateInstallmentsAction  # Distribuição exata de centavos
├── DTOs/                 # Transporte imutável tipado
├── Enums/                # PHP 8.2 Backed Enums (SaleStatus, UserRole...)
├── Exceptions/Domain/    # Exceções de domínio tipadas
├── Http/
│   ├── Controllers/
│   │   ├── Web/              # Inertia + Vue 3
│   │   └── Api/V1/           # JSON REST + Sanctum
│   ├── Requests/         # Validação estrutural
│   ├── Resources/V1/     # JsonResource padronizado
│   └── Middleware/        # HandleInertiaRequests, SecurityHeaders
├── Models/               # Eloquent com casts de Enum e relações tipadas
├── Policies/             # Autorização por recurso (authorizeResource)
├── Queries/              # Agregações no banco (DashboardMetrics, ExpenseSummary)
└── Services/             # Domain services reutilizáveis

resources/js/
├── app.ts                # Entry Inertia + Vue 3 + TypeScript
├── Components/UI/        # StatCard, Badge, Modal, Pagination, Toast
├── Layouts/              # AppLayout (Sidebar responsiva)
├── Pages/                # 22 páginas Vue Composition API
├── Types/                # Interfaces TypeScript
└── compose.yaml          # Docker Sail (PHP 8.4, MySQL 8.4, Redis)
```

**Princípios:**
- Backend é a autoridade única sobre preços, totais e estoque
- Transações atômicas com `DB::transaction()` + `lockForUpdate()`
- Valores monetários calculados em centavos inteiros
- Enums tipados eliminando magic strings
- Políticas de autorização aplicadas via `authorizeResource()`

## Stack Tecnológica

| Camada | Tecnologia |
|--------|-----------|
| **Backend** | Laravel 12, PHP 8.2+ |
| **Frontend** | Vue 3 (Composition API), TypeScript strict, Inertia.js |
| **Estilos** | TailwindCSS 4 |
| **Banco** | MySQL 8.4 |
| **Cache/Queue** | Redis (opcional), Database |
| **Auth** | Laravel Breeze (web) + Sanctum (API) |
| **Build** | Vite 7 |
| **Testes** | PHPUnit (62 testes, 261 assertions) |
| **Análise** | PHPStan/Larastan (nível 5), Laravel Pint |
| **Docker** | Laravel Sail (PHP 8.4, MySQL, Redis) |
| **CI** | GitHub Actions (Pint, PHPStan, PHPUnit, vue-tsc, build) |

## Instalação

### Opção A — Docker (recomendado)

```bash
git clone https://github.com/davigasperin/sistema-vendas.git
cd sistema-vendas

# Subir containers (PHP, MySQL, Redis)
./vendor/bin/sail up -d

# Instalar dependências e configurar
composer install
npm install
cp .env.example .env
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
npm run build

# Acessar
open http://localhost
```

### Opção B — Local (sem Docker)

```bash
git clone https://github.com/davigasperin/sistema-vendas.git
cd sistema-vendas

composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Acesse `http://localhost:8000`.

## Testes

```bash
php artisan test                    # 62 testes, 261 assertions
./vendor/bin/pint --test            # Code style PSR-12
./vendor/bin/phpstan analyse        # Static analysis nível 5
npx vue-tsc --noEmit                # TypeScript strict
npm run build                       # Build de produção
```

## API REST

Base URL: `http://localhost/api/v1`

```bash
# Autenticação
POST /api/v1/login
GET  /api/v1/me

# Produtos
GET    /api/v1/products
GET    /api/v1/products/{id}
POST   /api/v1/products
PATCH  /api/v1/products/{id}/adjust-stock
GET    /api/v1/products/search?q=

# Clientes
GET   /api/v1/customers
POST  /api/v1/customers
GET   /api/v1/customers/search?q=

# Vendas
GET  /api/v1/sales
POST /api/v1/sales
POST /api/v1/sales/{id}/cancel

# Despesas
GET    /api/v1/expenses
POST   /api/v1/expenses
PATCH  /api/v1/expenses/{id}/mark-paid
```

Documentação completa em [`docs/API.md`](docs/API.md).

## Decisões Técnicas

| Decisão | Justificativa | ADR |
|---------|--------------|-----|
| Backend price authority | Frontend nunca envia preço confiável; servidor busca `product.price` com `lockForUpdate` | [003](docs/DECISIONS/003-stock-concurrency.md) |
| Centavos inteiros | Evita perda de precisão float em parcelas (R$100/3 ≠ 3×R$33,33) | [002](docs/DECISIONS/002-money-handling.md) |
| Inertia + Vue | SPA sem API interna desnecessária; Laravel mantém controle de rotas e auth | [001](docs/DECISIONS/001-inertia-vue.md) |
| API versionada /v1 | Compatibilidade retroativa; mudanças breaking em `/v2` | [004](docs/DECISIONS/004-api-versioning.md) |
| Enums PHP 8.2+ | Elimina magic strings; refatoração segura com verificação de tipos | — |
| Query Objects | Agregações complexas fora de Models/Services; testáveis isoladamente | — |

## Roadmap

| Fase | Status |
|------|--------|
| 1. Characterization (factories, testes) | Concluída |
| 2. Backend Foundation (enums, migrations, policies) | Concluída |
| 3. Sales + Stock (transações, lock, cancelamento) | Concluída |
| 4. Expenses + Dashboard (query objects, N+1) | Concluída |
| 5. API V1 (Sanctum, resources, documentação) | Concluída |
| 6. Vue 3 + Inertia (22 páginas, TypeScript) | Concluída |
| 7. Docker + CI (Sail, GitHub Actions) | Concluída |
| 8. Portfolio (README, ADRs, docs) | Concluída |

Roadmap detalhado em [`docs/ROADMAP.md`](docs/ROADMAP.md).

## Estrutura de Testes

```
tests/Feature/
├── Api/                    # 5 testes de endpoints REST
├── Auth/                   # 7 testes de autenticação Breeze
├── Customers/              # 3 testes de CRUD
├── Dashboard/              # 4 testes de métricas
├── Expenses/               # 3 testes de despesas
├── Products/               # 4 testes de produtos
├── Sales/                  # 6 testes de vendas e ciclo de vida
├── AuthorizationTest.php   # 5 testes de RBAC
├── FactorySmokeTest.php    # 1 teste de integridade de factories
└── ProfileTest.php         # 5 testes de perfil
```

## Documentação

- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — Arquitetura e princípios de design
- [`docs/ROADMAP.md`](docs/ROADMAP.md) — Plano incremental de execução
- [`docs/API.md`](docs/API.md) — Documentação da API REST v1
- [`docs/DECISIONS/`](docs/DECISIONS/) — Architecture Decision Records

## Licença

MIT License. Veja [`LICENSE`](LICENSE) para detalhes.
