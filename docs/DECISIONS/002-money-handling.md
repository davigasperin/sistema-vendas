# ADR-002: Manejo de Dinheiro em Centavos Inteiros

## Status

Aceito

## Contexto

Vendas envolvem subtotais, descontos, totais e parcelas. Cálculos com `float` em PHP introduzem erros de precisão binária (ex.: `0.1 + 0.2 !== 0.3`). O `GenerateInstallmentsAction` original usava `round($total / $count, 2)` e aceitava tolerância de R$ 0,05 na soma das parcelas — permitindo perda financeira de até 5 centavos por venda.

## Decisão

Realizar todos os cálculos monetários em **centavos inteiros** (`int`) dentro do backend, convertendo para `float` apenas na persistência em colunas `DECIMAL(10,2)` e na exibição.

### Regras
1. Preço do produto convertido: `(int) round((float) $product->price * 100)`
2. Subtotal do item: `unitPriceCents * quantity`
3. Desconto e total calculados em centavos
4. Parcelas distribuídas com `intdiv` + módulo para distribuir o resíduo
5. Persistência: `round($cents / 100, 2)` para coluna DECIMAL
6. Validação `InstallmentsSumRule` exige igualdade exata ao centavo (sem tolerância)

### Exemplo de Distribuição (R$ 100,00 em 3x)
```
totalCents = 10000
baseCents = intdiv(10000, 3) = 3333
remainder = 10000 % 3 = 1

Parcela 1: 3333 + 1 = 3334 centavos = R$ 33,34
Parcela 2: 3333       = 3333 centavos = R$ 33,33
Parcela 3: 3333       = 3333 centavos = R$ 33,33
Soma: 3334 + 3333 + 3333 = 10000 centavos = R$ 100,00 ✓
```

## Consequências

### Positivas
- Zero perda de centavos em qualquer cenário de parcelamento
- Cálculos determinísticos e testáveis
- Sem dependência de biblioteca externa (Moneyphp/Money, Brick/Money)

### Negativas
- Conversões `cents ↔ float` espalhadas nos Actions (mitigado por helpers nos DTOs)
- PHP não tem tipo `decimal` nativo — disciplina manual necessária

## Alternativas Consideradas

1. **Biblioteca Moneyphp/Money**: Rejeitada — dependência externa para o que `intdiv` + `%` resolve em 5 linhas.
2. **Manter float com round()**: Rejeitado — causa perda silenciosa de centavos.
3. **String decimal**: Rejeitado — operações aritméticas em string são verbosas.
