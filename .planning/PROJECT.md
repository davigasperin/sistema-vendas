in# Sistema de Registro de Vendas — Laravel 11

## What This Is

Aplicação web completa de registro de vendas em PHP 8.2+ / Laravel 11, com banco de dados MySQL, frontend Bootstrap 5 + jQuery, geração de PDF com DomPDF e autenticação via Laravel Breeze. O sistema permite cadastro, listagem, edição, exclusão (soft delete) e exportação em PDF de vendas, com suporte a múltiplos itens por venda, parcelas editáveis e filtros avançados.

## Core Value

Fornecer ao vendedor uma interface ágil para registrar vendas completas (itens, forma de pagamento, parcelas, cliente opcional) e emitir comprovantes em PDF, com rastreabilidade por usuário autenticado.

## Context

- **Stack:** PHP 8.2+, Laravel 11, MySQL, Bootstrap 5, jQuery, DomPDF
- **Autenticação:** Laravel Breeze (Blade scaffolding)
- **Deploy target:** Ambiente local / servidor Linux (Apache/Nginx)
- **Escopo:** Greenfield — repositório novo

## Requirements

### Validated

(None yet — projeto não iniciado)

### Active

- [ ] Autenticação completa com Laravel Breeze (login/logout, middleware auth)
- [ ] CRUD de vendas com soft delete (SaleController resource)
- [ ] Itens da venda dinâmicos via jQuery (adicionar/remover, auto-fill de preço via AJAX)
- [ ] Parcelas editáveis com geração automática e validação de total
- [ ] Listagem paginada (15/página) com filtros (período, cliente, vendedor, forma de pagamento)
- [ ] Exportação de venda em PDF (DomPDF, template Blade separado)
- [ ] Form Request SaleRequest com regras completas incluindo custom rule de soma de parcelas
- [ ] SaleService com createSale, updateSale, calculateTotal, generateInstallments
- [ ] API endpoint GET /api/products/{id} retornando JSON {id, name, price}
- [ ] Seeders: 1 admin, 5 formas de pagamento, 10 produtos, 3 clientes
- [ ] Migrations para todas as tabelas definidas
- [ ] Layout Bootstrap 5 com navbar mostrando usuário logado

### Out of Scope

- Perfis de usuário avançados (RBAC multi-nível) — apenas auth básica no MVP
- Relatórios gerenciais / dashboards analytics — fora do escopo inicial
- API REST completa — apenas endpoint de produto necessário para AJAX

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Laravel Breeze com Blade | Scaffolding rápido, sem SPA overhead | Aprovado |
| DomPDF via barryvdh/laravel-dompdf | Biblioteca madura, integração nativa Laravel | Aprovado |
| SaleService para lógica de negócio | Separação de concerns, Controller magro | Aprovado |
| Soft delete nas vendas | Preservar histórico, evitar perda de dados | Aprovado |
| Select2 + jQuery | UX aprimorada para selects grandes, dinâmica de itens | Aprovado |

## Evolution

Este documento evolui a cada fase concluída.

---
*Last updated: 2026-05-13 — Inicialização do projeto (auto mode)*
