@extends('layouts.app')

@section('title', 'Venda #' . $sale->id)

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-receipt"></i> Venda #{{ $sale->id }}</h1>
        <div>
            <a href="{{ route('sales.pdf', $sale) }}" class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf"></i> Baixar PDF
            </a>
            <a href="{{ route('sales.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">Informações</div>
                <div class="card-body">
                    <p><strong>Data:</strong> {{ $sale->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>Vendedor:</strong> {{ $sale->user->name }}</p>
                    <p><strong>Cliente:</strong> {{ $sale->customer?->name ?? '-' }}</p>
                    <p><strong>Forma de Pagamento:</strong> {{ $sale->paymentMethod->name }}</p>
                    @if($sale->notes)
                        <p><strong>Observações:</strong> {{ $sale->notes }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">Valores</div>
                <div class="card-body">
                    <h3 class="text-success">Total: R$ {{ number_format($sale->total_amount, 2, ',', '.') }}</h3>
                    @if($sale->installments > 0)
                        <p class="mb-1"><strong>Parcelas:</strong> {{ $sale->installments }}x de R$ {{ number_format($sale->total_amount / $sale->installments, 2, ',', '.') }}</p>
                    @endif
                    @if($sale->discount > 0)
                        <p><strong>Desconto:</strong> R$ {{ number_format($sale->discount, 2, ',', '.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-box-seam"></i> Itens</div>
        <div class="card-body table-responsive">
            <table class="table">
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
        </div>
    </div>

    @php $installments = $sale->saleInstallments; @endphp

    @if($installments->count() > 0)
    <div class="card mb-3">
        <div class="card-header"><i class="bi bi-calendar-check"></i> Parcelas</div>
        <div class="card-body table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Vencimento</th>
                        <th class="text-end">Valor</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($installments as $inst)
                    <tr>
                        <td><strong>Parcela {{ $inst->installment_number }}/{{ $sale->installments }}</strong></td>
                        <td><i class="bi bi-calendar"></i> {{ $inst->due_date->format('d/m/Y') }}</td>
                        <td class="text-end"><strong>R$ {{ number_format($inst->amount, 2, ',', '.') }}</strong></td>
                        <td class="text-center">
                            @if($inst->is_paid)
                                <span class="badge bg-success"><i class="bi bi-check-circle"></i> Pago</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> Pendente</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection