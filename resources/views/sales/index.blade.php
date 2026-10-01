@extends('layouts.app')

@section('title', 'Vendas')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-receipt"></i> Vendas</h1>
        <a href="{{ route('sales.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Nova Venda
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Data Início</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Data Fim</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Cliente</label>
                    <select name="customer_id" class="form-select select2">
                        <option value="">Todos</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Forma de Pagamento</label>
                    <select name="payment_method_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}" {{ request('payment_method_id') == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i></button>
                        <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Forma de Pagamento</th>
                        <th>Total</th>
                        <th class="text-center">Parcelas</th>
                        <th>Vendedor</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td>#{{ $sale->id }}</td>
                        <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $sale->customer?->name ?? '-' }}</td>
                        <td>{{ $sale->paymentMethod->name }}</td>
                        <td>R$ {{ number_format($sale->total_amount, 2, ',', '.') }}</td>
                        <td class="text-center">{{ $sale->installments ?? 1 }}x</td>
                        <td>{{ $sale->user->name }}</td>
                        <td class="text-end">
                            <a href="{{ route('sales.show', $sale) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('sales.edit', $sale) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            <a href="{{ route('sales.pdf', $sale) }}" class="btn btn-sm btn-outline-danger" target="_blank"><i class="bi bi-file-pdf"></i></a>
                            <form action="{{ route('sales.destroy', $sale) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-dark"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center">Nenhuma venda encontrada.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $sales->withQueryString()->links() }}
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Tem certeza que deseja excluir esta venda?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Excluir</button>
            </div>
        </div>
    </div>
</div>
@endsection