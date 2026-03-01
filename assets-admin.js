
jQuery(document).ready(function($) {
    function showResult(msg, error) {
        var el = $('#rar-action-result');
        el.css('color', error ? '#d63638' : '#2271b1').text(msg);
        setTimeout(function(){ el.text(''); }, 5000);
    }

    $('#rar-import-now').on('click', function() {
        var btn = $(this).prop('disabled', true).text('⏳ Importando...');
        $.post(rarAjax.ajax_url, { action: 'rar_import_now', nonce: rarAjax.nonce }, function(res) {
            btn.prop('disabled', false).text('⬇️ Importar ahora');
            if (res.success) { showResult(res.data.message); location.reload(); }
            else showResult('Error al importar', true);
        });
    });

    $('#rar-rewrite-now').on('click', function() {
        var btn = $(this).prop('disabled', true).text('⏳ Reescribiendo...');
        $.post(rarAjax.ajax_url, { action: 'rar_rewrite_now', nonce: rarAjax.nonce }, function(res) {
            btn.prop('disabled', false).text('✍️ Reescribir pendientes');
            if (res.success) { showResult(res.data.message); location.reload(); }
            else showResult('Error al reescribir', true);
        });
    });

    $('#rar-add-feed').on('click', function() {
        var name = $('#new-feed-name').val().trim();
        var url  = $('#new-feed-url').val().trim();
        var cat  = $('#new-feed-category').val();
        var prompt = $('#new-feed-prompt').val().trim();
        if (!name || !url) { alert('Completa el nombre y la URL del feed.'); return; }
        $(this).prop('disabled', true).text('Guardando...');
        $.post(rarAjax.ajax_url, {
            action: 'rar_add_feed', nonce: rarAjax.nonce,
            feed_name: name, feed_url: url, category_id: cat, prompt_override: prompt
        }, function(res) {
            if (res.success) location.reload();
            else alert('Error al agregar el feed.');
        });
    });

    $(document).on('click', '.rar-delete-feed', function() {
        if (!confirm('¿Eliminar este feed?')) return;
        var id = $(this).data('id');
        $.post(rarAjax.ajax_url, { action: 'rar_delete_feed', nonce: rarAjax.nonce, feed_id: id }, function(res) {
            if (res.success) $('#feed-row-' + id).fadeOut(300, function(){ $(this).remove(); });
        });
    });

    $(document).on('click', '.rar-toggle-feed', function() {
        var id = $(this).data('id');
        $.post(rarAjax.ajax_url, { action: 'rar_toggle_feed', nonce: rarAjax.nonce, feed_id: id }, function(res) {
            if (res.success) location.reload();
        });
    });
});
