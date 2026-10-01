# Documentação da API REST v1 — Sistema de Vendas

A API REST do Sistema de Vendas foi projetada para integrações externas robustas, clientes mobile e serviços de terceiros, utilizando **Laravel Sanctum** para autenticação baseada em Bearer Token e **JsonResources** para respostas previsíveis e estruturadas.

---

## 1. Visão Geral

- **Base URL**: `http://localhost:8000/api/v1`
- **Headers Padrão**:
  ```http
  Accept: application/json
  Content-Type: application/json
  Authorization: Bearer <seu-token-sanctum>
  ```
- **Rate Limiting**: 60 requisições por minuto por IP/Token (`throttle:api`).

---

## 2. Autenticação

### `POST /api/v1/login`
Autentica o usuário e retorna o token de acesso pessoal Sanctum.

**Request:**
```json
{
  "email": "admin@sistema.com",
  "password": "password"
}
```

**Response (200 OK):**
```json
{
  "data": {
    "user": {
      "id": 1,
      "name": "Administrador",
      "email": "admin@sistema.com",
      "role": "admin",
      "role_label": "Administrador"
    },
    "token": "1|qWeRtY...",
    "token_type": "Bearer"
  },
  "message": "Autenticação realizada com sucesso."
}
```

### `GET /api/v1/me`
Retorna os dados do usuário atualmente autenticado.

### `POST /api/v1/logout`
Revoga o token de acesso atual.

---

## 3. Produtos

### `GET /api/v1/products`
Listagem paginada de produtos.

**Query Parameters**:
- `search` (string): Busca por nome do produto.
- `active` (boolean): Filtra produtos ativos/inativos.
- `low_stock` (boolean): Retorna apenas produtos em estoque crítico (`stock <= low_stock_threshold`).
- `per_page` (int, default: 15, max: 100).

**Response (200 OK):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Teclado Mecânico RGB",
      "description": "Switch Blue",
      "price": 250.00,
      "stock": 12,
      "active": true,
      "low_stock_threshold": 5,
      "is_low_stock": false,
      "created_at": "2026-05-16T10:00:00Z",
      "updated_at": "2026-05-16T10:00:00Z"
    }
  ],
  "links": { ... },
  "meta": { "current_page": 1, "per_page": 15, "total": 1 }
}
```

### `PATCH /api/v1/products/{id}/adjust-stock`
Ajusta a quantidade de estoque manualmente.

**Request:**
```json
{
  "adjustment": 5
}
```

---

## 4. Clientes

### `GET /api/v1/customers`
Listagem paginada de clientes com busca por nome, e-mail ou telefone.

### `POST /api/v1/customers`
Cadastra um novo cliente.

**Request:**
```json
{
  "name": "João Pereira",
  "email": "joao@empresa.com",
  "phone": "(11) 98888-7777",
  "address": "Rua Augusta, 500",
  "birth_date": "1988-12-05"
}
```

---

## 5. Vendas

### `GET /api/v1/sales`
Lista vendas com filtros por `status`, `customer_id`, `date_from`, `date_to`.

### `POST /api/v1/sales`
Registra uma nova venda com recálculo atômico pelo servidor e lock de estoque.

**Request:**
```json
{
  "customer_id": 1,
  "payment_method_id": 2,
  "discount": 10.00,
  "installments": 2,
  "notes": "Entrega expressa",
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    }
  ],
  "installment_amounts": [245.00, 245.00],
  "installment_dates": [
    "2026-06-16",
    "2026-07-16"
  ]
}
```

**Response (201 Created):**
```json
{
  "data": {
    "id": 1,
    "customer": { "id": 1, "name": "João Pereira" },
    "payment_method": { "id": 2, "name": "Cartão de Crédito" },
    "status": "completed",
    "status_label": "Concluída",
    "total_amount": 490.00,
    "discount": 10.00,
    "installments_count": 2,
    "items": [
      {
        "id": 1,
        "product_id": 1,
        "quantity": 2,
        "unit_price": 250.00,
        "subtotal": 500.00
      }
    ],
    "installments": [
      {
        "id": 1,
        "installment_number": 1,
        "amount": 245.00,
        "due_date": "2026-06-16",
        "is_paid": false,
        "status": "pending"
      },
      {
        "id": 2,
        "installment_number": 2,
        "amount": 245.00,
        "due_date": "2026-07-16",
        "is_paid": false,
        "status": "pending"
      }
    ]
  }
}
```

### `POST /api/v1/sales/{id}/cancel`
Cancela a venda, estorna os itens para o estoque e invalida as parcelas abertas.

---

## 6. Despesas

### `GET /api/v1/expenses`
Lista despesas e receitas manuais com filtros por `status`, `type` e `category_id`.

### `PATCH /api/v1/expenses/{id}/mark-paid`
Marca a despesa como paga registrando data de quitação.
