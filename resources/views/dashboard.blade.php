@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container">
    <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>

    <div class="row mt-4">
        <div class="col-lg-8">
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Vendas Hoje</h5>
                            <h2 class="card-text">{{ $salesStats['today'] }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Vendido (Mês)</h5>
                            <h2 class="card-text">R$ {{ number_format($salesStats['month'], 2, ',', '.') }}</h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Vendas Totais</h5>
                            <h2 class="card-text">{{ $salesStats['total'] }}</h2>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="bi bi-arrow-up-circle"></i> Receitas (Mês)</h6>
                        </div>
                        <div class="card-body">
                            <h4 class="text-success">R$ {{ number_format($financialSummary['income']['total'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Vendas: R$ {{ number_format($financialSummary['income']['sales'], 2, ',', '.') }}</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="bi bi-arrow-down-circle"></i> Despesas (Mês)</h6>
                        </div>
                        <div class="card-body">
                            <h4 class="text-danger">R$ {{ number_format($financialSummary['expenses']['paid'], 2, ',', '.') }}</h4>
                            <small class="text-muted">Pendente: R$ {{ number_format($financialSummary['expenses']['pending'], 2, ',', '.') }}</small>
                        </div>
                    </div>
                </div>
            </div>

            @if($financialSummary['overdue_count'] > 0)
            <div class="alert alert-danger mb-4">
                <i class="bi bi-exclamation-triangle"></i>
                <strong>{{ $financialSummary['overdue_count'] }}</strong> conta(s) vencida(s)!
                <a href="{{ route('expenses.index') }}" class="alert-link">Ver contas</a>
            </div>
            @endif

            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Venda Rápida</h5>
                </div>
                <div class="card-body">
                    <form id="quickSaleForm">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Cliente (Opcional)</label>
                                <select name="customer_id" id="quickCustomerSelect" class="form-select" style="width: 100%">
                                    <option value="">Selecione...</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Produto *</label>
                                <select name="product_id" id="quickProductSelect" class="form-select" style="width: 100%" required>
                                    <option value="">Buscar produto...</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quantidade *</label>
                                <input type="number" name="quantity" id="quickQuantity" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Preço Unit.</label>
                                <input type="number" id="quickUnitPrice" class="form-control" step="0.01" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Forma Pagamento *</label>
                                <select name="payment_method_id" class="form-select" required>
                                    <option value="">Selecione...</option>
                                    @foreach($paymentMethods as $pm)
                                        <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Parcelas</label>
                                <input type="number" name="installments" id="quickInstallments" class="form-control" min="1" max="12" value="1">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Dia Venc.</label>
                                <input type="number" name="installment_day" id="quickInstallmentDay" class="form-control" min="1" max="31" value="{{ now()->day }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Subtotal</label>
                                <input type="number" id="quickSubtotal" class="form-control" step="0.01" readonly>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="w-100 text-end">
                                    <h4 class="text-success">Total: R$ <span id="quickTotal">0,00</span></h4>
                                </div>
                            </div>
                            <div class="col-12">
                                <div id="installmentsPreview" class="mt-3 mb-3 p-3 bg-light rounded" style="display: none;">
                                    <h6 class="mb-2"><i class="bi bi-calendar-check"></i> Parcelas:</h6>
                                    <div id="installmentsList" class="small"></div>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Registrar Venda
                                </button>
                                <a href="{{ route('sales.create') }}" class="btn btn-outline-secondary ms-2">
                                    <i class="bi bi-plus-circle"></i> Venda Completa
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Últimas Vendas</h5>
                </div>
                <div class="card-body">
                    @if($latestSales->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Data</th>
                                        <th>Cliente</th>
                                        <th>Valor Total</th>
                                        <th>Método Pagamento</th>
                                        <th>Status</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($latestSales as $sale)
                                        <tr>
                                            <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                            <td>{{ $sale->customer->name ?? 'Sem cliente' }}</td>
                                            <td>
                                                <span class="fw-bold text-success">
                                                    R$ {{ number_format($sale->total_amount, 2, ',', '.') }}
                                                </span>
                                            </td>
                                            <td>{{ $sale->paymentMethod->name ?? '-' }}</td>
                                            <td>
                                                @if($sale->installments == 1 || $sale->installments == 0)
                                                    <span class="badge bg-success">Pago à vista</span>
                                                @elseif($sale->installments > 1)
                                                    <span class="badge bg-warning text-dark">{{ $sale->installments }}x Parcelado</span>
                                                @else
                                                    <span class="badge bg-secondary">Pendente</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('sales.show', $sale->id) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="bi bi-cart-x fs-1"></i>
                            <p class="mt-2">Nenhuma venda encontrada.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0"><i class="bi bi-graph-up"></i> Resumo Rápido</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Vendas Hoje:</span>
                        <strong>{{ $salesStats['today'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Vendas Semana:</span>
                        <strong>{{ $salesStats['week'] }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Média por Venda:</span>
                        <strong>R$ {{ number_format($salesStats['average'], 2, ',', '.') }}</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Ticket Médio:</span>
                        <strong class="text-success">R$ {{ number_format($salesStats['averageThisMonth'], 2, ',', '.') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('#quickCustomerSelect').select2({
        language: 'pt-BR',
        width: '100%',
        minimumInputLength: 0,
        placeholder: 'Selecione...',
        ajax: {
            url: '/api/customers/search',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function (item) {
                        return { id: item.id, text: item.name };
                    })
                };
            }
        }
    });

    $('#quickProductSelect').select2({
        language: 'pt-BR',
        width: '100%',
        minimumInputLength: 0,
        placeholder: 'Buscar produto...',
        ajax: {
            url: '/api/products/search',
            dataType: 'json',
            delay: 250,
            processResults: function (data) {
                return {
                    results: data.map(function (product) {
                        return {
                            id: product.id,
                            text: product.name + ' - R$ ' + parseFloat(product.price).toFixed(2).replace('.', ',') + ' (Est: ' + product.stock + ')',
                            price: product.price,
                            stock: product.stock
                        };
                    })
                };
            }
        }
    }).on('select2:select', function(e) {
        let price = parseFloat(e.params.data.price) || 0;
        let stock = parseInt(e.params.data.stock) || 0;
        $('#quickUnitPrice').val(price.toFixed(2));
        $('#quickQuantity').attr('max', stock);
        calculateQuickTotal();
    });

    $('#quickQuantity').on('input', function() {
        calculateQuickTotal();
        renderInstallments();
    });

    $('#quickInstallments, #quickInstallmentDay').on('input', renderInstallments);

    function calculateQuickTotal() {
        let qty = parseFloat($('#quickQuantity').val()) || 0;
        let price = parseFloat($('#quickUnitPrice').val()) || 0;
        let total = qty * price;
        $('#quickSubtotal').val(total.toFixed(2));
        $('#quickTotal').text(total.toFixed(2).replace('.', ','));
    }

    function renderInstallments() {
        let total = parseFloat($('#quickSubtotal').val()) || 0;
        let numInstallments = parseInt($('#quickInstallments').val()) || 1;
        let day = parseInt($('#quickInstallmentDay').val()) || 1;

        if (numInstallments <= 1) {
            $('#installmentsPreview').hide();
            return;
        }

        let amount = total / numInstallments;
        let amounts = [];
        let dates = [];
        let today = new Date();

        let lastAmount = amount * numInstallments;
        let diff = total - lastAmount;
        amount = amount + diff;

        for (let i = 0; i < numInstallments; i++) {
            let dueDate = new Date(today);
            dueDate.setMonth(dueDate.getMonth() + i + 1);
            dueDate.setDate(day);

            if (dueDate.getMonth() === today.getMonth() && i === 0) {
                dueDate.setDate(day);
                if (dueDate < today) {
                    dueDate.setMonth(dueDate.getMonth() + 1);
                }
            }

            let year = dueDate.getFullYear();
            let month = String(dueDate.getMonth() + 1).padStart(2, '0');
            let dayStr = String(dueDate.getDate()).padStart(2, '0');
            dates.push(`${year}-${month}-${dayStr}`);
            amounts.push((i === numInstallments - 1 ? amount : amount).toFixed(2));
        }

        let html = '';
        let displayAmount = total / numInstallments;
        let lastDisplayAmount = displayAmount + diff;

        for (let i = 0; i < numInstallments; i++) {
            let installmentNum = i + 1;
            let installmentAmount = (i === numInstallments - 1 ? lastDisplayAmount : displayAmount);
            let dueDateFormatted = dates[i].split('-').reverse().join('/');
            html += `<div class="mb-1"><strong>${installmentNum}x</strong>: R$ ${installmentAmount.toFixed(2).replace('.', ',')} - Venc: ${dueDateFormatted}</div>`;
        }

        $('#installmentsList').html(html);
        $('#installmentsPreview').show();
    }

    $('#quickSaleForm').on('submit', function(e) {
        e.preventDefault();

        let form = $(this);
        let productId = $('#quickProductSelect').val();
        let quantity = parseInt($('#quickQuantity').val()) || 1;
        let unitPrice = parseFloat($('#quickUnitPrice').val()) || 0;
        let subtotal = quantity * unitPrice;
        let numInstallments = parseInt($('#quickInstallments').val()) || 1;
        let day = parseInt($('#quickInstallmentDay').val()) || 1;

        if (!productId) {
            alert('Por favor, selecione um produto.');
            return;
        }

        let installmentDates = [];
        let installmentAmounts = [];

        if (numInstallments > 1) {
            let amount = subtotal / numInstallments;
            let lastAmount = amount * numInstallments;
            let diff = subtotal - lastAmount;
            amount = amount + diff;

            let today = new Date();
            for (let i = 0; i < numInstallments; i++) {
                let dueDate = new Date(today);
                dueDate.setMonth(dueDate.getMonth() + i + 1);
                dueDate.setDate(day);

                if (dueDate.getMonth() === today.getMonth() && i === 0) {
                    dueDate.setDate(day);
                    if (dueDate < today) {
                        dueDate.setMonth(dueDate.getMonth() + 1);
                    }
                }

                let year = dueDate.getFullYear();
                let month = String(dueDate.getMonth() + 1).padStart(2, '0');
                let dayStr = String(dueDate.getDate()).padStart(2, '0');
                installmentDates.push(`${year}-${month}-${dayStr}`);
                installmentAmounts.push((i === numInstallments - 1 ? amount : amount).toFixed(2));
            }
        } else {
            installmentDates.push(new Date().toISOString().split('T')[0]);
            installmentAmounts.push(subtotal.toFixed(2));
        }

        let data = {
            _token: '{{ csrf_token() }}',
            customer_id: $('#quickCustomerSelect').val() || null,
            payment_method_id: form.find('[name="payment_method_id"]').val(),
            installments: numInstallments,
            items: [{
                product_id: productId,
                quantity: quantity,
                unit_price: unitPrice,
                subtotal: subtotal
            }],
            installment_dates: installmentDates,
            installment_amounts: installmentAmounts
        };

        let submitBtn = form.find('button[type="submit"]');
        let originalBtnText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span> Processando...');

        $.ajax({
            url: '{{ route("sales.store") }}',
            method: 'POST',
            data: data,
            success: function(response) {
                alert('Venda registrada com sucesso!');
                window.location.href = '{{ route("dashboard") }}';
            },
            error: function(xhr) {
                let msg = 'Erro ao registrar venda.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                alert(msg);
                submitBtn.prop('disabled', false).html(originalBtnText);
            }
        });
    });
});
</script>
@endpush
@endsection