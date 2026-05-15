@extends('layouts.app')

@section('title', $customer->name)

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-person"></i> {{ $customer->name }}</h1>
        <div>
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Dados Pessoais</div>
                <div class="card-body">
                    <p><strong>Nome:</strong> {{ $customer->name }}</p>
                    <p><strong>Email:</strong> {{ $customer->email ?? '-' }}</p>
                    <p><strong>Telefone:</strong> {{ $customer->phone ?? '-' }}</p>
                    <p><strong>Data de Nascimento:</strong> {{ $customer->birth_date?->format('d/m/Y') ?? '-' }}</p>
                    <p><strong>Idade:</strong> {{ $customer->age ? $customer->age . ' anos' : '-' }}</p>
                    @if($customer->address)
                        <p><strong>Endereço:</strong><br>{{ $customer->address }}</p>
                    @endif
                    <hr>
                    <p class="text-muted mb-0">
                        <small>Cadastrado em: {{ $customer->created_at->format('d/m/Y H:i') }}</small>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">Estatísticas</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <h3>{{ $customer->sales->count() }}</h3>
                            <p class="mb-0">Total de Compras</p>
                        </div>
                        <div class="col-6">
                            <h3>R$ {{ number_format($customer->sales->sum('total_amount'), 2, ',', '.') }}</h3>
                            <p class="mb-0">Valor Total</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><i class="bi bi-receipt"></i> Histórico de Compras</div>
        <div class="card-body table-responsive">
            @if($customer->sales->count() > 0)
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data</th>
                            <th>Forma de Pagamento</th>
                            <th>Itens</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customer->sales as $sale)
                        <tr>
                            <td>#{{ $sale->id }}</td>
                            <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $sale->paymentMethod->name }}</td>
                            <td>{{ $sale->items->count() }} itens</td>
                            <td class="text-end">R$ {{ number_format($sale->total_amount, 2, ',', '.') }}</td>
                            <td class="text-end">
                                <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('sales.pdf', $sale) }}" class="btn btn-sm btn-outline-danger" target="_blank">
                                    <i class="bi bi-file-pdf"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-center text-muted mb-0">Nenhuma compra registrada para este cliente.</p>
            @endif
        </div>
    </div>
</div>
@endsection