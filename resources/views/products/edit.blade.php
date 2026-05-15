@extends('layouts.app')

@section('title', 'Editar Produto')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-pencil"></i> Editar Produto</h1>
        <a href="{{ route('products.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('products.update', $product) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome do Produto *</label>
                            <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $product->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="price" class="form-label">Preço *</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="number" id="price" class="form-control @error('price') is-invalid @enderror" name="price" step="0.01" min="0" value="{{ old('price', $product->price) }}" required>
                                @error('price')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="mb-3">
                            <label for="stock" class="form-label">Estoque *</label>
                            <input type="number" id="stock" class="form-control @error('stock') is-invalid @enderror" name="stock" min="0" value="{{ old('stock', $product->stock) }}" required>
                            @error('stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descrição</label>
                    <textarea id="description" class="form-control @error('description') is-invalid @enderror" name="description" rows="3">{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="active" name="active" value="1" {{ $product->active ? 'checked' : '' }}>
                        <label class="form-check-label" for="active">Produto Ativo</label>
                    </div>
                    <small class="text-muted">Produtos inativos não aparecem na listagem de vendas.</small>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle"></i> Atualizar Produto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection