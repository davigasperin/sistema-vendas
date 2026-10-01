@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-people"></i> Clientes</h1>
        <a href="{{ route('customers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Novo Cliente
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <h5 class="card-title">Total de Clientes</h5>
                    <h2 class="card-text">{{ $totalCustomers }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title">Novos (Este Mês)</h5>
                    <h2 class="card-text">{{ $newThisMonth }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title">Aniversariantes do Mês</h5>
                    <h2 class="card-text">{{ $recentCustomers->whereBetween('birth_date', [now()->startOfMonth(), now()->endOfMonth()])->count() }}</h2>
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
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-1">
                        <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i> Buscar</button>
                        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-circle"></i></a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($recentCustomers->count() > 0 && !request('search'))
    <div class="card mb-4">
        <div class="card-header"><i class="bi bi-clock-history"></i> Últimos Cadastrados</div>
        <div class="card-body">
            <div class="row">
                @foreach($recentCustomers as $customer)
                <div class="col-md-4 mb-2">
                    <div class="d-flex justify-content-between align-items-center border rounded p-2">
                        <div>
                            <strong>{{ $customer->name }}</strong>
                            @if($customer->email)
                                <br><small class="text-muted">{{ $customer->email }}</small>
                            @endif
                        </div>
                        <small class="text-muted">{{ $customer->created_at->format('d/m/Y') }}</small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Telefone</th>
                        <th>Nascimento</th>
                        <th>Idade</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>
                            <a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a>
                        </td>
                        <td>{{ $customer->email ?? '-' }}</td>
                        <td>{{ $customer->phone ?? '-' }}</td>
                        <td>{{ $customer->birth_date?->format('d/m/Y') ?? '-' }}</td>
                        <td>{{ $customer->age ?? '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST" class="d-inline delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center">Nenhum cliente encontrado.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $customers->withQueryString()->links() }}
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
            <div class="modal-body">Tem certeza que deseja excluir este cliente?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Excluir</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$('.delete-form').on('submit', function(e) {
    e.preventDefault();
    $('#deleteModal').modal('show');
    $('#confirmDelete').data('form', this);
});

$('#confirmDelete').on('click', function() {
    $($(this).data('form')).off('submit').submit();
});
</script>
@endpush
@endsection