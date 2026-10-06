<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupom Térmico - Venda #{{ $sale->id }}</title>
    <style>
        :root {
            --paper-width: {{ $paperWidth === '58mm' ? '58mm' : '80mm' }};
            --paper-font-size: {{ $paperWidth === '58mm' ? '10px' : '12px' }};
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f1f5f9;
            color: #000;
            font-size: var(--paper-font-size);
            line-height: 1.25;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .controls {
            width: var(--paper-width);
            max-width: 100%;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 10px 14px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            font-family: system-ui, -apple-system, sans-serif;
            font-size: 13px;
        }

        .controls .btn {
            background: #0f172a;
            color: #ffffff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .controls .btn:hover {
            background: #1e293b;
        }

        .controls .toggle-group {
            display: flex;
            gap: 6px;
        }

        .controls .toggle-btn {
            background: #e2e8f0;
            color: #334155;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .controls .toggle-btn.active {
            background: #0f172a;
            color: #ffffff;
        }

        .ticket {
            width: var(--paper-width);
            background: #ffffff;
            padding: 12px 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
            word-wrap: break-word;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .double-divider {
            border-top: 2px dashed #000;
            margin: 6px 0;
        }

        .title {
            font-size: 1.2em;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .subtitle {
            font-size: 0.9em;
        }

        .row {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0;
        }

        .table th, .table td {
            padding: 2px 0;
            font-size: 1em;
        }

        .total-block {
            margin-top: 4px;
        }

        .total-block .row {
            font-size: 1.05em;
        }

        .total-block .row.highlight {
            font-size: 1.25em;
            font-weight: bold;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .controls {
                display: none !important;
            }

            .ticket {
                box-shadow: none !important;
                padding: 4px 2px !important;
                width: 100% !important;
                max-width: var(--paper-width) !important;
            }

            @page {
                size: var(--paper-width) auto;
                margin: 0mm;
            }
        }
    </style>
</head>
<body>
    <div class="controls no-print">
        <div class="toggle-group">
            <a href="?width=80mm" class="toggle-btn {{ $paperWidth === '80mm' ? 'active' : '' }}">80mm</a>
            <a href="?width=58mm" class="toggle-btn {{ $paperWidth === '58mm' ? 'active' : '' }}">58mm</a>
        </div>
        <button class="btn" onclick="window.print()">Imprimir</button>
    </div>

    <div class="ticket">
        <div class="text-center">
            <div class="title">SISTEMA COMERCIAL</div>
            <div class="subtitle">CUPOM NÃO FISCAL</div>
            <div class="subtitle">COMPROVANTE DE VENDA</div>
        </div>

        <div class="divider"></div>

        <div class="row">
            <span>PEDIDO: #{{ $sale->id }}</span>
            <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span>OPERADOR: {{ $sale->user->name }}</span>
            @if($sale->cash_shift_id)
                <span>CX: #{{ $sale->cash_shift_id }}</span>
            @endif
        </div>
        <div class="row">
            <span>CLIENTE: {{ $sale->customer?->name ?? 'CONSUMIDOR FINAL' }}</span>
        </div>

        <div class="double-divider"></div>

        <table class="table">
            <thead>
                <tr>
                    <th class="text-left">ITEM / QTD x UNIT</th>
                    <th class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $idx => $item)
                    <tr>
                        <td class="text-left" colspan="2" style="padding-top: 3px;">
                            {{ $idx + 1 }}. {{ $item->product->name }}
                        </td>
                    </tr>
                    <tr>
                        <td class="text-left" style="padding-left: 8px;">
                            {{ $item->quantity }} x R$ {{ number_format($item->unit_price, 2, ',', '.') }}
                        </td>
                        <td class="text-right bold">
                            R$ {{ number_format($item->subtotal, 2, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <div class="total-block">
            <div class="row">
                <span>SUBTOTAL:</span>
                <span>R$ {{ number_format($sale->items->sum('subtotal'), 2, ',', '.') }}</span>
            </div>
            @if((float) $sale->discount > 0)
                <div class="row">
                    <span>DESCONTO:</span>
                    <span>- R$ {{ number_format($sale->discount, 2, ',', '.') }}</span>
                </div>
            @endif
            <div class="row highlight">
                <span>TOTAL:</span>
                <span>R$ {{ number_format($sale->total_amount, 2, ',', '.') }}</span>
            </div>
        </div>

        <div class="double-divider"></div>

        <div class="bold uppercase" style="margin-bottom: 2px;">PAGAMENTOS:</div>
        @if($sale->payments && $sale->payments->isNotEmpty())
            @foreach($sale->payments as $payment)
                <div class="row">
                    <span>{{ $payment->paymentMethod?->name ?? 'OUTROS' }}:</span>
                    <span class="bold">R$ {{ number_format($payment->amount, 2, ',', '.') }}</span>
                </div>
                @if((float) $payment->change_given > 0)
                    <div class="row" style="padding-left: 8px; font-size: 0.95em;">
                        <span>(TROCO):</span>
                        <span>R$ {{ number_format($payment->change_given, 2, ',', '.') }}</span>
                    </div>
                @endif
            @endforeach
        @else
            <div class="row">
                <span>{{ $sale->paymentMethod->name }}:</span>
                <span class="bold">R$ {{ number_format($sale->total_amount, 2, ',', '.') }}</span>
            </div>
        @endif

        @if($sale->saleInstallments && $sale->saleInstallments->isNotEmpty())
            <div class="divider"></div>
            <div class="bold uppercase" style="margin-bottom: 2px;">PARCELAS ({{ $sale->installments }}X):</div>
            @foreach($sale->saleInstallments as $inst)
                <div class="row">
                    <span>{{ $inst->installment_number }}x ({{ $inst->due_date->format('d/m/Y') }}):</span>
                    <span>R$ {{ number_format($inst->amount, 2, ',', '.') }}</span>
                </div>
            @endforeach
        @endif

        @if($sale->notes)
            <div class="divider"></div>
            <div>OBSERVAÇÕES:</div>
            <div>{{ $sale->notes }}</div>
        @endif

        <div class="divider"></div>

        <div class="text-center" style="margin-top: 8px;">
            <div>OBRIGADO PELA PREFERENCIA!</div>
            <div style="font-size: 0.8em; margin-top: 4px;">VOLTE SEMPRE</div>
        </div>
    </div>

    @if(request()->query('autoprint') === '1')
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>