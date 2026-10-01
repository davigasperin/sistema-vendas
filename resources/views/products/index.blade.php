@extends('layouts.app')

@section('title', 'Produtos')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-box-seam"></i> Produtos</h1>
        <a href="{{ route('products.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Produto
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total de Produtos</h5>
                    <h2 class="card-text">{{ $totalProducts }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Produtos Ativos</h5>
                    <h2 class="card-text">{{ $activeProducts }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <h5 class="card-title">Estoque Baixo</h5>
                    <h2 class="card-text">{{ $lowStock }}</h2>
                    <small class="text-white">≤ 5 unidades</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Buscar por nome</label>
                    <input type="text" name="search" class="form-control" placeholder="Digite o nome..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="active" class="form-select">
                        <option value="">Todos</option>
                        <option value="1" {{ request('active') === '1' ? 'selected' : '' }}>Ativos</option>
                        <option value="0" {{ request('active') === '0' ? 'selected' : '' }}>Inativos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i> Buscar</button>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
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
                        <th>Nome</th>
                        <th>Preço</th>
                        <th class="text-center">Estoque</th>
                        <th class="text-center">Ações</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                    <tr>
                        <td>
                            <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                            @if($product->description)
                                <br><small class="text-muted">{{ Str::limit($product->description, 50) }}</small>
                            @endif
                        </td>
                        <td>R$ {{ number_format($product->price, 2, ',', '.') }}</td>
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <button type="button" class="btn btn-sm btn-outline-secondary stock-btn" data-action="decrease" data-id="{{ $product->id }}" {{ $product->stock <= 0 ? 'disabled' : '' }}>-</button>
                                <span class="stock-value" id="stock-{{ $product->id }}" style="min-width: 30px; text-align: center;">{{ $product->stock }}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary stock-btn" data-action="increase" data-id="{{ $product->id }}">+</button>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="stock-status @if($product->stock <= 5) text-danger fw-bold @endif">
                                @if($product->stock <= 5)
                                    <i class="bi bi-exclamation-triangle"></i> Baixo
                                @elseif($product->stock <= 10)
                                    <i class="bi bi-exclamation-circle"></i> Médio
                                @else
                                    <i class="bi bi-check-circle"></i> OK
                                @endif
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm {{ $product->active ? 'btn-success' : 'btn-secondary' }} toggle-active" data-id="{{ $product->id }}">
                                {{ $product->active ? 'Ativo' : 'Inativo' }}
                            </button>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('products.destroy', $product) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Nenhum produto encontrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $products->withQueryString()->links() }}
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
            <div class="modal-body">Tem certeza que deseja excluir este produto?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Excluir</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/products.js') }}"></script>
@endpush
@endsection