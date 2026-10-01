@extends('layouts.app')

@section('title', 'Detalhes da Despesa')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-receipt"></i> Detalhes da Despesa</h2>
        <div>
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Voltar
            </a>
            <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Editar
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Informações da Despesa</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Descrição</label>
                            <p class="fw-bold">{{ $expense->description }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Categoria</label>
                            <p>
                                <span class="badge" style="background-color: {{ $expense->category->color }}">
                                    {{ $expense->category->name }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Valor</label>
                            <p class="fw-bold text-success fs-5">R$ {{ number_format($expense->amount, 2, ',', '.') }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Tipo</label>
                            <p>{{ $expense->type == 'expense' ? 'Despesa' : 'Receita' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Data de Vencimento</label>
                            <p>{{ \Carbon\Carbon::parse($expense->due_date)->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Data de Pagamento</label>
                            <p>{{ $expense->paid_date ? \Carbon\Carbon::parse($expense->paid_date)->format('d/m/Y') : '-' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted small">Status</label>
                            <p>
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
                            </p>
                        </div>
                        @if($expense->notes)
                        <div class="col-12 mb-3">
                            <label class="text-muted small">Observações</label>
                            <p>{{ $expense->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Ações</h5>
                </div>
                <div class="card-body">
                    @if($expense->status !== 'paid')
                    <button type="button" class="btn btn-success w-100 mb-2" onclick="markPaid({{ $expense->id }})">
                        <i class="bi bi-check-circle"></i> Marcar como Pago
                    </button>
                    @endif
                    <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100" onclick="return confirm('Tem certeza que deseja excluir esta despesa?')">
                            <i class="bi bi-trash"></i> Excluir
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function markPaid(id) {
    if (confirm('Marcar esta despesa como paga?')) {
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
        });
    }
}
</script>
@endpush
@endsection