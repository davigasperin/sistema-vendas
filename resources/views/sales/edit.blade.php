@extends('layouts.app')

@section('title', 'Editar Venda')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-pencil"></i> Editar Venda #{{ $sale->id }}</h1>
        <a href="{{ route('sales.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar
        </a>
    </div>

    <form action="{{ route('sales.update', $sale) }}" method="POST" id="saleForm">
        @csrf
        @method('PUT')

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-person"></i> Cliente</div>
                    <div class="card-body">
                        <select name="customer_id" class="form-select" id="customerSelect" style="width: 100%">
                            @if($sale->customer)
                                <option value="{{ $sale->customer_id }}" selected>{{ $sale->customer->name }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header"><i class="bi bi-credit-card"></i> Forma de Pagamento</div>
                    <div class="card-body">
                        <select name="payment_method_id" class="form-select" required>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" {{ $sale->payment_method_id == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
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
                            <input type="number" name="discount" class="form-control" step="0.01" min="0" value="{{ $sale->discount }}">
                        </div>
                        <div>
                            <label class="form-label">Observações</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $sale->notes }}</textarea>
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
                    <tbody id="itemsBody">
                        @foreach($sale->items as $item)
                        <tr class="item-row">
                            <td>
                                <select name="items[{{ $loop->index }}][product_id]" class="form-select product-select" required>
                                    <option value="{{ $item->product_id }}" data-price="{{ $item->unit_price }}" selected>{{ $item->product->name }}</option>
                                </select>
                            </td>
                            <td><input type="number" name="items[{{ $loop->index }}][quantity]" class="form-control quantity-input" min="1" value="{{ $item->quantity }}" required></td>
                            <td><input type="number" name="items[{{ $loop->index }}][unit_price]" class="form-control price-input" step="0.01" value="{{ $item->unit_price }}" required></td>
                            <td><input type="number" name="items[{{ $loop->index }}][subtotal]" class="form-control subtotal-input" step="0.01" value="{{ $item->subtotal }}" readonly></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-x"></i></button></td>
                        </tr>
                        @endforeach
                    </tbody>
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
                    <input type="number" id="installmentCount" name="installments" class="form-control" min="1" max="12" value="{{ $sale->installments }}">
                </div>
                <div id="installmentsContainer">
                    @foreach($sale->saleInstallments() as $inst)
                    <div class="row mb-2">
                        <div class="col-md-6">
                            <label class="form-label">Vencimento #{{ $inst->installment_number }}</label>
                            <input type="date" name="installment_dates[]" class="form-control" value="{{ $inst->due_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Valor #{{ $inst->installment_number }}</label>
                            <input type="number" name="installment_amounts[]" class="form-control installment-amount" step="0.01" value="{{ $inst->amount }}" required>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div id="installmentTotal" class="mt-2 text-end"></div>
            </div>
        </div>

        <div class="card bg-light">
            <div class="card-body text-end">
                <h4>Total: R$ <span id="totalAmount">{{ number_format($sale->total_amount, 2, ',', '.') }}</span></h4>
            </div>
        </div>

        <div class="mt-3 text-end">
            <button type="submit" class="btn btn-success btn-lg">
                <i class="bi bi-check-circle"></i> Atualizar Venda
            </button>
        </div>
    </form>
</div>
@push('scripts')
<script src="{{ asset('js/sales.js') }}"></script>
@endpush
@endsection