@extends('layouts.app')

@section('title', 'Despesas')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-currency-dollar"></i> Despesas</h2>
        <div>
            <a href="{{ route('expenses.report') }}" class="btn btn-outline-primary">
                <i class="bi bi-graph-up"></i> Relatório
            </a>
            <a href="{{ route('expenses.create') }}" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Nova Despesa
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-4">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Buscar despesa..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">Todos os status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendente</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Pago</option>
                        <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Vencido</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="type" class="form-select">
                        <option value="">Todos os tipos</option>
                        <option value="expense" {{ request('type') == 'expense' ? 'selected' : '' }}>Despesa</option>
                        <option value="income" {{ request('type') == 'income' ? 'selected' : '' }}>Receita</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($expenses as $expense)
                        <tr>
                            <td>{{ $expense->description }}</td>
                            <td>
                                <span class="badge" style="background-color: {{ $expense->category->color }}">
                                    {{ $expense->category->name }}
                                </span>
                            </td>
                            <td class="fw-bold">R$ {{ number_format($expense->amount, 2, ',', '.') }}</td>
                            <td>{{ \Carbon\Carbon::parse($expense->due_date)->format('d/m/Y') }}</td>
                            <td>
                                @switch($expense->status)
                                    @case('pending')
                                        <span class="badge bg-warning text-dark">Pendente</span>
                                        @break
                                    @case('paid')
                                        <span class="badge bg-success">Pago</span>
                                        @break
                                    @case('overdue')
                                        <span class="badge bg-danger">Vencido</span>
                                        @break
                                    @case('cancelled')
                                        <span class="badge bg-secondary">Cancelado</span>
                                        @break
                                @endswitch
                            </td>
                            <td>
                                <a href="{{ route('expenses.show', $expense->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @if($expense->status !== 'paid')
                                <button type="button" class="btn btn-sm btn-outline-success" onclick="markPaid({{ $expense->id }})">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">Nenhuma despesa encontrada.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-center">
                {{ $expenses->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function markPaid(id) {
    if (confirm('Marcar esta despesa como paga?')) {
        let btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        
        fetch(`/expenses/${id}/mark-paid`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle"></i>';
        });
    }
}
</script>
@endpush
@endsection