/* global jjrcGR, jQuery */
(function ($) {
    'use strict';

    // ---- Modal principal ----
    function openModal() {
        $('#jjrc-modal-overlay').fadeIn(200);
    }
    function closeModal() {
        $('#jjrc-modal-overlay').fadeOut(200);
        resetForm();
    }
    function resetForm() {
        $('#jjrc-form-comercio')[0].reset();
        $('#jjrc-field-id').val(0);
        $('#jjrc-field-place-id').val('');
        $('#jjrc-field-nombre').val('');
        $('#jjrc-key-preview').text('…');
        $('#jjrc-modal-title').text('Nuevo Comercio');
        $('#jjrc-form-message').text('').removeClass('success error');
        $('#jjrc-field-color-primario').val('#f5a623');
        $('#jjrc-field-color-fondo').val('#ffffff');
        $('#jjrc-field-color-texto').val('#333333');
        $('#jjrc-field-cache').val('12');
        $('#jjrc-field-min-rating').val('4');
    }

    $('#jjrc-btn-nuevo').on('click', function () {
        resetForm();
        openModal();
    });
    $('#jjrc-modal-close, #jjrc-btn-cancelar').on('click', closeModal);
    $('#jjrc-modal-overlay').on('click', function (e) {
        if ($(e.target).is('#jjrc-modal-overlay')) closeModal();
    });

    // ---- Preview shortcode key ----
    $('#jjrc-field-key').on('input', function () {
        $('#jjrc-key-preview').text($(this).val() || '…');
    });

    // ---- Modal de búsqueda ----
    var searchTimeout = null;

    function openSearchModal() {
        $('#jjrc-search-input').val('');
        $('#jjrc-search-results').hide().empty();
        $('#jjrc-search-hint').text('Escribe al menos 3 caracteres para buscar.');
        $('#jjrc-search-overlay').fadeIn(200);
        setTimeout(function () { $('#jjrc-search-input').focus(); }, 220);
    }
    function closeSearchModal() {
        $('#jjrc-search-overlay').fadeOut(200);
        clearTimeout(searchTimeout);
    }

    $('#jjrc-btn-search-place').on('click', openSearchModal);
    $('#jjrc-search-close').on('click', closeSearchModal);
    $('#jjrc-search-overlay').on('click', function (e) {
        if ($(e.target).is('#jjrc-search-overlay')) closeSearchModal();
    });

    $('#jjrc-search-input').on('input', function () {
        var val = $(this).val().trim();
        clearTimeout(searchTimeout);

        if (val.length < 3) {
            $('#jjrc-search-results').hide().empty();
            $('#jjrc-search-hint').text('Escribe al menos 3 caracteres para buscar.');
            return;
        }

        $('#jjrc-search-hint').text('Buscando…');

        searchTimeout = setTimeout(function () {
            $.post(jjrcGR.ajaxurl, {
                action: 'jjrc_gr_autocomplete',
                nonce:  jjrcGR.nonce,
                input:  val
            }, function (res) {
                var $list = $('#jjrc-search-results').empty();
                if (res.success && res.data.predictions && res.data.predictions.length) {
                    $('#jjrc-search-hint').text('Selecciona el comercio:');
                    $.each(res.data.predictions, function (i, p) {
                        $('<li></li>')
                            .text(p.description)
                            .data('place_id', p.place_id)
                            .data('nombre', p.description)
                            .appendTo($list);
                    });
                    $list.show();
                } else {
                    $('#jjrc-search-hint').text('Sin resultados. Intenta con otro nombre.');
                    $list.hide();
                }
            }).fail(function () {
                $('#jjrc-search-hint').text('Error al buscar. Verifica tu API Key en Configuración.');
            });
        }, 350);
    });

    $(document).on('click', '#jjrc-search-results li', function () {
        var place_id = $(this).data('place_id');
        var nombre   = $(this).data('nombre');
        if (!place_id) return;

        $('#jjrc-field-place-id').val(place_id);
        $('#jjrc-field-nombre').val(nombre);

        // Auto-fill shortcode key si está vacío
        if (!$('#jjrc-field-key').val()) {
            var key = nombre.toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '')
                .substring(0, 50);
            $('#jjrc-field-key').val(key);
            $('#jjrc-key-preview').text(key);
        }

        closeSearchModal();
    });

    // ---- Guardar comercio ----
    $('#jjrc-form-comercio').on('submit', function (e) {
        e.preventDefault();

        if (!$('#jjrc-field-place-id').val() || !$('#jjrc-field-nombre').val()) {
            showMsg('Debes completar el Place ID y el nombre del comercio.', 'error');
            return;
        }

        var $btn = $('#jjrc-btn-guardar');
        $btn.find('.jjrc-btn-text').hide();
        $btn.find('.jjrc-spinner').show();
        $btn.prop('disabled', true);

        var formData = $(this).serialize();
        formData += '&action=jjrc_gr_save_comercio&nonce=' + jjrcGR.nonce;

        $.post(jjrcGR.ajaxurl, formData, function (res) {
            $btn.find('.jjrc-btn-text').show();
            $btn.find('.jjrc-spinner').hide();
            $btn.prop('disabled', false);

            if (res.success) {
                showMsg(res.data.message, 'success');
                setTimeout(function () {
                    closeModal();
                    location.reload();
                }, 800);
            } else {
                showMsg(res.data.message || 'Error al guardar.', 'error');
            }
        });
    });

    function showMsg(msg, type) {
        $('#jjrc-form-message').text(msg).removeClass('success error').addClass(type);
    }

    // ---- Editar ----
    $(document).on('click', '.jjrc-btn-edit', function () {
        var $btn = $(this);
        resetForm();
        $('#jjrc-modal-title').text('Editar Comercio');
        $('#jjrc-field-id').val($btn.data('id'));
        $('#jjrc-field-place-id').val($btn.data('place_id'));
        $('#jjrc-field-nombre').val($btn.data('nombre'));
        $('#jjrc-field-key').val($btn.data('key'));
        $('#jjrc-key-preview').text($btn.data('key'));
        $('#jjrc-field-vista').val($btn.data('vista'));
        $('#jjrc-field-cache').val($btn.data('cache'));
        $('#jjrc-field-color-primario').val($btn.data('color_primario'));
        $('#jjrc-field-color-fondo').val($btn.data('color_fondo'));
        $('#jjrc-field-color-texto').val($btn.data('color_texto'));
        $('#jjrc-field-min-rating').val($btn.data('min_rating') || '1');
        openModal();
    });

    // ---- Eliminar ----
    $(document).on('click', '.jjrc-btn-delete', function () {
        var id = $(this).data('id');
        if (!confirm('¿Estás seguro de eliminar este comercio y sus datos de cache?')) return;

        $.post(jjrcGR.ajaxurl, {
            action: 'jjrc_gr_delete_comercio',
            nonce:  jjrcGR.nonce,
            id:     id
        }, function (res) {
            if (res.success) {
                $('#jjrc-row-' + id).fadeOut(300, function () {
                    $(this).remove();
                    if (!$('#jjrc-comercios-tbody tr').length) {
                        $('#jjrc-comercios-tbody').html(
                            '<tr id="jjrc-row-empty"><td colspan="6" style="text-align:center;padding:30px;color:#999;">No hay comercios configurados.</td></tr>'
                        );
                    }
                });
            } else {
                alert(res.data.message || 'Error al eliminar.');
            }
        });
    });

    // ---- Refrescar cache ----
    $(document).on('click', '.jjrc-btn-refresh', function () {
        var $btn = $(this);
        var id   = $btn.data('id');
        $btn.prop('disabled', true).text('⏳');

        $.post(jjrcGR.ajaxurl, {
            action: 'jjrc_gr_refresh_cache',
            nonce:  jjrcGR.nonce,
            id:     id
        }, function (res) {
            $btn.prop('disabled', false).text('🔄 Cache');
            if (res.success) {
                var $row = $('#jjrc-row-' + id);
                $row.find('.jjrc-rating').text('⭐ ' + res.data.rating + ' (' + res.data.total + ')');
                $row.find('.jjrc-no-cache').replaceWith('<span class="jjrc-rating">⭐ ' + res.data.rating + ' (' + res.data.total + ')</span>');
            } else {
                alert(res.data.message || 'Error al actualizar cache.');
            }
        });
    });

    // ---- Copiar shortcode ----
    $(document).on('click', '.jjrc-btn-copy', function () {
        var text = $(this).data('text');
        var $btn = $(this);
        navigator.clipboard.writeText(text).then(function () {
            $btn.text('✅').prop('disabled', true);
            setTimeout(function () {
                $btn.text('📋').prop('disabled', false);
            }, 1500);
        });
    });

})(jQuery);
