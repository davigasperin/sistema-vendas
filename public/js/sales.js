$(document).ready(function() {
    let itemIndex = 0;

    function updateTotals() {
        let total = 0;
        $('#itemsBody tr').each(function() {
            let qty = parseFloat($(this).find('.quantity-input').val()) || 0;
            let price = parseFloat($(this).find('.price-input').val()) || 0;
            let subtotal = qty * price;
            $(this).find('.subtotal-input').val(subtotal.toFixed(2));
            total += subtotal;
        });

        let discount = parseFloat($('input[name="discount"]').val()) || 0;
        total = Math.max(0, total - discount);
        $('#totalAmount').text(total.toFixed(2).replace('.', ','));
        updateInstallments(total);
    }

    function updateInstallments(total) {
        let count = parseInt($('#installmentCount').val()) || 1;
        if (count < 1) count = 1;

        let amount = (total / count).toFixed(2);
        let today = new Date();

        $('#installmentsContainer .row').each(function(i) {
            let date = new Date(today);
            date.setMonth(date.getMonth() + i);
            let dateStr = date.toISOString().split('T')[0];
            $(this).find('input[type="date"]').val(dateStr);
            $(this).find('.installment-amount').val(amount);
        });

        let sum = 0;
        $('.installment-amount').each(function() {
            sum += parseFloat($(this).val()) || 0;
        });
        $('#installmentTotal').html(`<strong>Soma das parcelas: R$ ${sum.toFixed(2).replace('.', ',')}</strong>`);
    }

    function initProductSelect2(selector) {
        $(selector).select2({
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
                                price: product.price
                            };
                        })
                    };
                }
            }
        }).on('select2:select', function(e) {
            let price = e.params.data.price;
            let row = $(this).closest('tr');
            row.find('.quantity-input').val(1);
            row.find('.price-input').val(price).trigger('input');
            row.find('.subtotal-input').val((price * 1).toFixed(2));
            updateTotals();
        });
    }

    function addItem() {
        let html = `<tr class="item-row">
            <td>
                <select name="items[${itemIndex}][product_id]" class="form-select product-select" required>
                    <option value="">Buscar produto...</option>
                </select>
            </td>
            <td><input type="number" name="items[${itemIndex}][quantity]" class="form-control quantity-input" min="1" value="1" required></td>
            <td><input type="number" name="items[${itemIndex}][unit_price]" class="form-control price-input" step="0.01" required></td>
            <td><input type="number" name="items[${itemIndex}][subtotal]" class="form-control subtotal-input" step="0.01" readonly></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-item"><i class="bi bi-x"></i></button></td>
        </tr>`;
        $('#itemsBody').append(html);
        initProductSelect2(`#itemsBody tr:last .product-select`);
        itemIndex++;
    }

    $(document).on('change', '.product-select', function() {
        let option = $(this).find(':selected');
        let price = parseFloat(option.data('price')) || 0;
        if (price > 0) {
            let row = $(this).closest('tr');
            row.find('.quantity-input').val(1);
            row.find('.price-input').val(price).trigger('input');
            row.find('.subtotal-input').val((price * 1).toFixed(2));
            updateTotals();
        }
    });

    $(document).on('input', '.quantity-input, .price-input', updateTotals);
    $('input[name="discount"]').on('input', updateTotals);

    $(document).on('click', '.remove-item', function() {
        $(this).closest('tr').remove();
        updateTotals();
    });

    $('#addItem').on('click', addItem);

    $('#installmentCount').on('input', function() {
        let count = parseInt($(this).val()) || 1;
        if (count < 1) count = 1;

        let container = $('#installmentsContainer');

        while (container.find('.row').length < count) {
            let idx = container.find('.row').length;
            container.append(`<div class="row mb-2">
                <div class="col-md-6">
                    <label class="form-label">Vencimento #${idx + 1}</label>
                    <input type="date" name="installment_dates[]" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valor #${idx + 1}</label>
                    <input type="number" name="installment_amounts[]" class="form-control installment-amount" step="0.01" required>
                </div>
            </div>`);
        }

        while (container.find('.row').length > count) {
            container.find('.row').last().remove();
        }

        let total = parseFloat($('#totalAmount').text().replace(',', '.')) || 0;
        updateInstallments(total);
    });

    $(document).on('input', '.installment-amount', function() {
        let sum = 0;
        $('.installment-amount').each(function() {
            sum += parseFloat($(this).val()) || 0;
        });
        $('#installmentTotal').html(`<strong>Soma das parcelas: R$ ${sum.toFixed(2).replace('.', ',')}</strong>`);
    });

    $('#saleForm').on('submit', function() {
        let count = parseInt($('#installmentCount').val()) || 1;
        if (count === 1 && $('#installmentsContainer').find('.row').length === 0) {
            $('<input>').attr({type: 'hidden', name: 'installment_dates[]', value: new Date().toISOString().split('T')[0]}).appendTo(this);
            $('<input>').attr({type: 'hidden', name: 'installment_amounts[]', value: $('#totalAmount').text().replace(',', '.')}).appendTo(this);
        }
    });

    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        $('#deleteModal').modal('show');
        $('#confirmDelete').data('form', this);
    });

    $('#confirmDelete').on('click', function() {
        $($(this).data('form')).off('submit').submit();
    });

    if ($('#customerSelect').length) {
        $('#customerSelect, .product-select').filter(function() {
        return !$(this).closest('.select2-container').length;
    }).each(function() {
        let $select = $(this);
        let isProduct = $select.hasClass('product-select');
        let url = isProduct ? '/api/products/search' : '/api/customers/search';
        
        $select.select2({
            language: 'pt-BR',
            width: '100%',
            minimumInputLength: 0,
            placeholder: isProduct ? 'Buscar produto...' : 'Selecione...',
            ajax: {
                url: url,
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: data.map(function (item) {
                            if (isProduct) {
                                return {
                                    id: item.id,
                                    text: item.name + ' - R$ ' + parseFloat(item.price).toFixed(2).replace('.', ',') + ' (Est: ' + item.stock + ')',
                                    price: item.price
                                };
                            }
                            return { id: item.id, text: item.name };
                        })
                    };
                }
            }
        });
    });
    }

    if ($('#itemsBody').length && $('#itemsBody tr').length === 0) {
        addItem();
    }

    updateTotals();
    if ($('#installmentCount').length) {
        $('#installmentCount').trigger('input');
    }
});