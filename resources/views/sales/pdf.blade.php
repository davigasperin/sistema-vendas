<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Venda #{{ $sale->id }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { margin: 0; }
        .info-grid { display: table; width: 100%; margin-bottom: 20px; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; font-weight: bold; width: 30%; padding: 5px; background: #f5f5f5; }
        .info-value { display: table-cell; padding: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        .text-end { text-align: right; }
        .total { font-size: 18px; font-weight: bold; text-align: right; margin-top: 20px; }
        .footer { text-align: center; font-size: 10px; color: #666; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Sistema de Vendas</h1>
        <p>Comprovante de Venda #{{ $sale->id }}</p>
    </div>

    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Data:</div>
            <div class="info-value">{{ $sale->created_at->format('d/m/Y H:i') }}</div>
            <div class="info-label">Vendedor:</div>
            <div class="info-value">{{ $sale->user->name }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Cliente:</div>
            <div class="info-value">{{ $sale->customer?->name ?? 'Não informado' }}</div>
            <div class="info-label">Parcelas:</div>
            <div class="info-value">{{ $sale->installments }}x</div>
        </div>
        <div class="info-row">
            <div class="info-label">Forma de Pagamento:</div>
            <div class="info-value">{{ $sale->paymentMethod->name }}</div>
            <div class="info-label">Desconto:</div>
            <div class="info-value">R$ {{ number_format($sale->discount ?? 0, 2, ',', '.') }}</div>
        </div>
        @if($sale->notes)
        <div class="info-row">
            <div class="info-label">Observações:</div>
            <div class="info-value" colspan="3">{{ $sale->notes }}</div>
        </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Produto</th>
                <th class="text-end">Qtd</th>
                <th class="text-end">Preço Unit.</th>
                <th class="text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ $item->product->name }}</td>
                <td class="text-end">{{ $item->quantity }}</td>
                <td class="text-end">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                <td class="text-end">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($sale->saleInstallments->count() > 0)
    <table>
        <thead>
            <tr>
                <th>Nº</th>
                <th class="text-end">Valor</th>
                <th>Vencimento</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->saleInstallments as $inst)
            <tr>
                <td>{{ $inst->installment_number }}/{{ $sale->installments }}</td>
                <td class="text-end">R$ {{ number_format($inst->amount, 2, ',', '.') }}</td>
                <td>{{ $inst->due_date->format('d/m/Y') }}</td>
                <td>
                    @if($inst->is_paid)
                        <span style="color: green;">Pago</span>
                    @elseif($inst->due_date < now())
                        <span style="color: red;">Vencido</span>
                    @else
                        <span style="color: orange;">Pendente</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="total">
        Total: R$ {{ number_format($sale->total_amount, 2, ',', '.') }}
    </div>

    <div class="footer">
        Documento gerado automaticamente em {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>