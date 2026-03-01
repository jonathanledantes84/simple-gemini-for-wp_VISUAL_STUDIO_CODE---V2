
jQuery(document).ready(function($) {
    function showResult(msg, error) {
        $('#prn-action-result').css('color', error ? '#d63638' : '#00a32a').text(msg);
        setTimeout(() => $('#prn-action-result').text(''), 6000);
    }

    $('#prn-import-now').on('click', function() {
        var btn = $(this).prop('disabled', true).text('⏳ Importando...');
        $.post(prnAjax.ajax_url, { action: 'prn_import_now', nonce: prnAjax.nonce }, function(res) {
            btn.prop('disabled', false).text('⬇️ Importar ahora');
            if (res.success) { showResult('✅ ' + res.data.message); setTimeout(() => location.reload(), 1500); }
            else showResult('Error al importar', true);
        });
    });

    $('#prn-rewrite-now').on('click', function() {
        var btn = $(this).prop('disabled', true).text('⏳ Reescribiendo...');
        $.post(prnAjax.ajax_url, { action: 'prn_rewrite_now', nonce: prnAjax.nonce }, function(res) {
            btn.prop('disabled', false).text('✍️ Reescribir + Generar imágenes');
            if (res.success) { showResult('✅ ' + res.data.message); setTimeout(() => location.reload(), 1500); }
            else showResult('Error al reescribir', true);
        });
    });

    $('#prn-add-feed').on('click', function() {
        var name   = $('#new-feed-name').val().trim();
        var url    = $('#new-feed-url').val().trim();
        var cat    = $('#new-feed-category').val();
        var prompt = $('#new-feed-prompt').val().trim();
        if (!name || !url) { alert('Completá el nombre y la URL del feed.'); return; }
        $(this).prop('disabled', true).text('Guardando...');
        $.post(prnAjax.ajax_url, { action: 'prn_add_feed', nonce: prnAjax.nonce, feed_name: name, feed_url: url, category_id: cat, prompt_override: prompt }, function(res) {
            if (res.success) location.reload();
            else alert('Error al agregar el feed.');
        });
    });

    $(document).on('click', '.prn-delete-feed', function() {
        if (!confirm('¿Eliminar este feed?')) return;
        var id = $(this).data('id');
        $.post(prnAjax.ajax_url, { action: 'prn_delete_feed', nonce: prnAjax.nonce, feed_id: id }, function(res) {
            if (res.success) $('#feed-row-' + id).fadeOut(400, function(){ $(this).remove(); });
        });
    });

    $(document).on('click', '.prn-toggle-feed', function() {
        $.post(prnAjax.ajax_url, { action: 'prn_toggle_feed', nonce: prnAjax.nonce, feed_id: $(this).data('id') }, function(res) {
            if (res.success) location.reload();
        });
    });
});
