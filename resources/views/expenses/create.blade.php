@extends('layouts.app')

@section('title', 'Nova Despesa')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-plus-circle"></i> Nova Despesa</h2>
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Descrição *</label>
                        <input type="text" name="description" class="form-control @error('description') is-invalid @enderror" value="{{ old('description') }}" required>
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Categoria *</label>
                        <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Selecione...</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tipo *</label>
                        <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                            <option value="expense" {{ old('type') == 'expense' ? 'selected' : '' }}>Despesa</option>
                            <option value="income" {{ old('type') == 'income' ? 'selected' : '' }}>Receita</option>
                        </select>
                        @error('type')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Valor *</label>
                        <input type="number" name="amount" class="form-control @error('amount') is-invalid @enderror" step="0.01" min="0.01" value="{{ old('amount') }}" required>
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Data de Vencimento *</label>
                        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror" value="{{ old('due_date') }}" required>
                        @error('due_date')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pending" {{ old('status') == 'pending' ? 'selected' : '' }}>Pendente</option>
                            <option value="paid" {{ old('status') == 'paid' ? 'selected' : '' }}>Pago</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Salvar
                        </button>
                        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection