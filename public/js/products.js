$(document).ready(function() {
    $('.stock-btn').on('click', function() {
        const btn = $(this);
        const productId = btn.data('id');
        const action = btn.data('action');
        const adjustment = action === 'increase' ? 1 : -1;

        $.ajax({
            url: `/products/${productId}/adjust-stock`,
            type: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                adjustment: adjustment
            },
            success: function(response) {
                if (response.success) {
                    $(`#stock-${productId}`).text(response.stock);

                    const decreaseBtn = $(`.stock-btn[data-id="${productId}"][data-action="decrease"]`);
                    if (response.stock <= 0) {
                        decreaseBtn.prop('disabled', true);
                    } else {
                        decreaseBtn.prop('disabled', false);
                    }

                    if (response.stock === 0) {
                        toastr.warning('Estoque zerado!');
                    } else {
                        toastr.success(response.message);
                    }
                }
            },
            error: function() {
                toastr.error('Erro ao atualizar estoque');
            }
        });
    });

    $('.toggle-active').on('click', function() {
        const btn = $(this);
        const productId = btn.data('id');

        $.ajax({
            url: `/products/${productId}/toggle-active`,
            type: 'PATCH',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    if (response.active) {
                        btn.removeClass('btn-secondary').addClass('btn-success');
                        btn.text('Ativo');
                    } else {
                        btn.removeClass('btn-success').addClass('btn-secondary');
                        btn.text('Inativo');
                    }
                    toastr.success('Status atualizado');
                }
            },
            error: function() {
                toastr.error('Erro ao atualizar status');
            }
        });
    });

    $('.delete-form').on('submit', function(e) {
        e.preventDefault();
        $('#deleteModal').modal('show');
        $('#confirmDelete').data('form', this);
    });

    $('#confirmDelete').on('click', function() {
        $($(this).data('form')).off('submit').submit();
    });
});