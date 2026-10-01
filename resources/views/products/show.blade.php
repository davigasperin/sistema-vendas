@extends('layouts.app')

@section('title', $product->name)

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-box-seam"></i> {{ $product->name }}</h1>
        <div>
            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('products.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Dados do Produto</div>
                <div class="card-body">
                    <p><strong>Nome:</strong> {{ $product->name }}</p>
                    @if($product->description)
                        <p><strong>Descrição:</strong><br>{{ $product->description }}</p>
                    @endif
                    <p><strong>Preço:</strong> R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                    <p><strong>Estoque:</strong>
                        <span class="{{ $product->stock <= 5 ? 'text-danger fw-bold' : '' }}">
                            {{ $product->stock }} unidades
                            @if($product->stock <= 5)
                                <i class="bi bi-exclamation-triangle"></i>
                            @endif
                        </span>
                    </p>
                    <p><strong>Status:</strong>
                        <span class="badge bg-{{ $product->active ? 'success' : 'secondary' }}">
                            {{ $product->active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </p>
                    <hr>
                    <p class="text-muted mb-0">
                        <small>Cadastrado em: {{ $product->created_at->format('d/m/Y H:i') }}</small>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Estatísticas de Vendas</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3>{{ $totalSold }}</h3>
                            <p class="mb-0">Total Vendido</p>
                        </div>
                        <div class="col-6">
                            <h3 class="text-success">R$ {{ number_format($totalRevenue, 2, ',', '.') }}</h3>
                            <p class="mb-0">Receita Total</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-receipt"></i> Histórico de Vendas</div>
        <div class="card-body table-responsive">
            @if($product->saleItems->count() > 0)
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID Venda</th>
                            <th>Cliente</th>
                            <th>Data</th>
                            <th class="text-center">Qtd Vendida</th>
                            <th class="text-end">Valor Unit.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($product->saleItems->sortByDesc('sale.created_at') as $item)
                        <tr>
                            <td>#{{ $item->sale_id }}</td>
                            <td>{{ $item->sale->customer?->name ?? '-' }}</td>
                            <td>{{ $item->sale->created_at->format('d/m/Y H:i') }}</td>
                            <td class="text-center">{{ $item->quantity }}</td>
                            <td class="text-end">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                            <td class="text-end">R$ {{ number_format($item->subtotal, 2, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-center text-muted mb-0">Nenhuma venda registrada para este produto.</p>
            @endif
        </div>
    </div>
</div>
@endsection