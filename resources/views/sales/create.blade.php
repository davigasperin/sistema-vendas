@extends('layouts.app')

@section('title', 'Nova Venda')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-plus-circle"></i> Nova Venda</h1>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <form action="{{ route('sales.store') }}" method="POST" id="saleForm">
        @csrf

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person"></i> Cliente (Opcional)</div>
                    <div class="card-body">
                        <select name="customer_id" class="form-select" id="customerSelect" style="width: 100%">
                            <option value="">Selecione...</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-credit-card"></i> Forma de Pagamento</div>
                    <div class="card-body">
                        <select name="payment_method_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-currency-dollar"></i> Informações</div>
                    <div class="card-body">
                        <div class="mb-2">
                            <label class="form-label">Desconto (R$)</label>
                            <input type="number" name="discount" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div>
                            <label class="form-label">Observações</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-box-seam"></i> Itens da Venda</div>
            <div class="card-body">
                <table class="table table-sm" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th width="100">Qtd</th>
                            <th width="120">Preço Unit.</th>
                            <th width="120">Subtotal</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody"></tbody>
                </table>
                <button type="button" class="btn btn-outline-primary btn-sm" id="addItem">
                    <i class="bi bi-plus"></i> Adicionar Item
                </button>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-calendar-check"></i> Parcelas</div>
            <div class="card-body">
                <div class="mb-2">
                    <label class="form-label">Número de Parcelas</label>
                    <input type="number" id="installmentCount" name="installments" class="form-control" min="1" max="12" value="1">
                </div>
                <div id="installmentsContainer"></div>
                <div id="installmentTotal" class="mt-2 text-end"></div>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body text-end">
                <h4>Total: R$ <span id="totalAmount">0,00</span></h4>
            </div>
        </div>

        <div class="mt-3 text-end">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle"></i> Registrar Venda
            </button>
        </div>
    </form>
</div>
@push('scripts')
<script src="{{ asset('js/sales.js') }}"></script>
@endpush
@endsection