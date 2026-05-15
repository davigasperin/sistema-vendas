@extends('layouts.app')

@section('title', 'Relatório Financeiro')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-graph-up"></i> Relatório Financeiro</h2>
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-4">
            <label class="form-label">Data Início</label>
            <input type="date" name="start_date" class="form-control" value="{{ $startDate }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Data Fim</label>
            <input type="date" name="end_date" class="form-control" value="{{ $endDate }}">
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6>Receitas (Vendas)</h6>
                    <h4>R$ {{ number_format($summary['income']['sales'], 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6>Receitas Manuais</h6>
                    <h4>R$ {{ number_format($summary['income']['manual'], 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h6>Despesas Pagas</h6>
                    <h4>R$ {{ number_format($summary['expenses']['paid'], 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $summary['balance'] >= 0 ? 'bg-primary' : 'bg-warning' }} text-white">
                <div class="card-body">
                    <h6>Saldo do Período</h6>
                    <h4>R$ {{ number_format($summary['balance'], 2, ',', '.') }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Resumo do Período</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Total Receitas</td>
                            <td class="text-success fw-bold">R$ {{ number_format($summary['income']['total'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Total Despesas Pagas</td>
                            <td class="text-danger fw-bold">R$ {{ number_format($summary['expenses']['paid'], 2, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Despesas Pendentes</td>
                            <td class="text-warning fw-bold">R$ {{ number_format($summary['expenses']['pending'], 2, ',', '.') }}</td>
                        </tr>
                        <tr class="table-light">
                            <td><strong>Saldo</strong></td>
                            <td class="{{ $summary['balance'] >= 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                <strong>R$ {{ number_format($summary['balance'], 2, ',', '.') }}</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Contas Vencidas ({{ $summary['overdue_count'] }})</h6>
                </div>
                <div class="card-body">
                    @if($overdueExpenses->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th>Descrição</th>
                                    <th>Valor</th>
                                    <th>Vencimento</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($overdueExpenses as $expense)
                                <tr>
                                    <td>{{ $expense->description }}</td>
                                    <td class="text-danger">R$ {{ number_format($expense->amount, 2, ',', '.') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($expense->due_date)->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-muted text-center">Nenhuma conta vencida.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection