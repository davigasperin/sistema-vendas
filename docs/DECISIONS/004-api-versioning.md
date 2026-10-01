# ADR-004: Versionamento da API REST

## Status

Aceito

## Contexto

A API original misturava rotas com controllers web (`CustomerController` de `routes/web.php`) e não tinha versionamento. Mudanças em formatos de resposta quebrariam consumidores existentes. A estrutura era: `/products/search`, `/customers/search`, `/login` — sem consistência.

## Decisão

Criar **namespace dedicado `App\Http\Controllers\Api\V1`** com prefixo de rota `/api/v1`:

### Estrutura
```
routes/api.php                    # Roteamento versionado
app/Http/Controllers/Api/V1/     # Controllers da v1
app/Http/Resources/V1/           # JsonResource da v1
```

### Regras
1. Todas as rotas novas ficam sob `/api/v1/*`
2. Rotas legadas preservadas para compatibilidade (deprecated)
3. Respostas padronizadas com `JsonResource` (wrapper `data`)
4. Autenticação via Sanctum Bearer Token
5. Rate limiting `throttle:api` (60 req/min)
6. Controllers da API são independentes dos controllers web

### Exemplo de Response
```json
{
    "data": {
        "id": 1,
        "name": "Produto",
        "price": 99.90,
        "stock": 10
    }
}
```

## Consequências

### Positivas
- Breaking changes vão para `/v2` sem afetar consumidores da v1
- Separação clara entre interface web (Inertia) e API externa
- Controllers web não são contaminados por necessidades de API (e vice-versa)
- Documentação OpenAPI por versão

### Negativas
- Manutenção de rotas legadas até migração completa dos consumidores
- Mais controllers para manter (aceitável — responsabilidades distintas)

## Alternativas Consideradas

1. **Header `Accept: application/vnd.api.v1+json`**: Rejeitado — menos discoverable, harder to debug.
2. **Versionamento por query param `?v=1`**: Rejeitado — não separa rotas, difícil de documentar.
3. **Sem versionamento (atual)**: Rejeitado — qualquer mudança é breaking.
