# ADR-003: Concorrência de Estoque com lockForUpdate

## Status

Aceito

## Contexto

O `CreateSaleAction` original validava estoque com `Product::find()` (leitura sem lock) e depois executava `decrement()`. Duas requisições simultâneas para o mesmo produto com estoque 1 poderiam ambas passar na validação e gerar estoque -1. O mesmo padrão existia em `UpdateSaleAction` e no cancelamento.

## Decisão

Utilizar **`DB::transaction()` + `lockForUpdate()`** em todos os fluxos que mutam estoque:

### Fluxo de Criação de Venda
```php
DB::transaction(function () use ($dto) {
    // 1. Lock em todos os produtos envolvidos
    $products = Product::whereIn('id', $ids)
        ->lockForUpdate()
        ->get()
        ->keyBy('id');

    // 2. Validar estoque com dados travados
    if ($product->stock < $quantity) {
        throw new InsufficientStockException(...);
    }

    // 3. Decrementar e registrar movimentação
    $product->stock = $newStock;
    $product->save();

    StockMovement::create([...]);
});
```

### Fluxo de Cancelamento
- Lock na venda: `Sale::where('id', $sale->id)->lockForUpdate()`
- Lock em cada produto: `Product::where('id', ...)->lockForUpdate()`
- Estorno transacional + registro `StockMovementType::SaleCancel`

### Fluxo de Atualização
- Lock na venda existente
- Lock em todos os produtos (antigos + novos)
- Restaurar estoque antigo → validar novo → decrementar novos — tudo na mesma transação

## Garantias

| Cenário | Resultado |
|---------|-----------|
| 2 vendas simultâneas, estoque 1 | 1 sucesso, 1 `InsufficientStockException` |
| Cancelamento durante venda | Lock na venda serializa as operações |
| Atualização com troca de itens | Estoque antigo restaurado antes de validar novos |

## Consequências

### Positivas
- Estoque nunca fica negativo sob concorrência
- Toda mutação deixa registro em `stock_movements` (auditoria)
- Atomicidade total: falha em qualquer ponto reverte tudo

### Negativas
- `lockForUpdate()` pode causar waiting em alta concorrência (aceitável para volume PME)
- SQLite (testes) não suporta `lockForUpdate` real — testes validam lógica, não lock

## Alternativas Consideradas

1. **`decrement()` atômico sem lock**: Rejeitado — não valida disponibilidade antes de decrementar.
2. **Coluna `version` (optimistic locking)**: Rejeitado — requer retry em conflito, complexidade desnecessária para este volume.
3. **Redis distributed lock**: Rejeitado — MySQL row lock é suficiente e não adiciona infraestrutura.
