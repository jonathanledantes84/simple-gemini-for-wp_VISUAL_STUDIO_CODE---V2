<?php
/**
 * Plugin Name: Punto Río Negro AI
 * Plugin URI:  https://puntorionegro.com.ar
 * Description: Importa noticias desde feeds RSS y las reescribe automáticamente con Google Gemini AI. Genera imágenes destacadas, detecta duplicados avanzados y ofrece opciones avanzadas de reescritura.
 * Version:     2.0.0
 * Author:      Punto Río Negro
 * Text Domain: punto-rionegro-ai
 */

if (!defined('ABSPATH')) exit;

define('PRN_VERSION',    '2.0.0');
define('PRN_DIR',        plugin_dir_path(__FILE__));
define('PRN_URL',        plugin_dir_url(__FILE__));
define('PRN_SLUG',       'punto-rionegro-ai');

require_once PRN_DIR . 'includes/class-rss-importer.php';
require_once PRN_DIR . 'includes/class-gemini-rewriter.php';
require_once PRN_DIR . 'includes/class-image-generator.php';
require_once PRN_DIR . 'includes/class-duplicate-checker.php';
require_once PRN_DIR . 'includes/class-admin-page.php';

// ── Activación ──────────────────────────────────────────────────────────────
register_activation_hook(__FILE__, 'prn_activate');
function prn_activate() {
    global $wpdb;
    $c = $wpdb->get_charset_collate();

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}prn_feeds (
        id           mediumint(9) NOT NULL AUTO_INCREMENT,
        feed_name    varchar(200) NOT NULL,
        feed_url     varchar(500) NOT NULL,
        category_id  bigint(20)  NOT NULL DEFAULT 0,
        prompt_override text,
        active       tinyint(1)  DEFAULT 1,
        last_fetch   datetime    DEFAULT NULL,
        created_at   datetime    DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $c;");

    $wpdb->query("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}prn_imported (
        id           mediumint(9)  NOT NULL AUTO_INCREMENT,
        feed_id      mediumint(9)  NOT NULL,
        item_guid    varchar(500)  NOT NULL,
        item_hash    varchar(64)   DEFAULT NULL,
        post_id      bigint(20)    DEFAULT NULL,
        imported_at  datetime      DEFAULT CURRENT_TIMESTAMP,
        rewritten    tinyint(1)    DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY item_guid (item_guid(255))
    ) $c;");

    if (!wp_next_scheduled('prn_cron_import'))  wp_schedule_event(time(),       'hourly',          'prn_cron_import');
    if (!wp_next_scheduled('prn_cron_rewrite')) wp_schedule_event(time() + 300, 'prn_every_30min', 'prn_cron_rewrite');
}

register_deactivation_hook(__FILE__, 'prn_deactivate');
function prn_deactivate() {
    wp_clear_scheduled_hook('prn_cron_import');
    wp_clear_scheduled_hook('prn_cron_rewrite');
}

add_filter('cron_schedules', function($s) {
    $s['prn_every_30min'] = ['interval' => 1800, 'display' => 'Cada 30 minutos'];
    return $s;
});

add_action('prn_cron_import',  ['PRN_RSS_Importer',    'run']);
add_action('prn_cron_rewrite', ['PRN_Gemini_Rewriter', 'run']);

add_action('admin_menu',            ['PRN_Admin_Page', 'add_menu']);
add_action('admin_init',            ['PRN_Admin_Page', 'register_settings']);
add_action('admin_enqueue_scripts', 'prn_admin_scripts');

function prn_admin_scripts($hook) {
    if (strpos($hook, PRN_SLUG) === false) return;
    wp_enqueue_style ('prn-admin', PRN_URL . 'assets/admin.css', [], PRN_VERSION);
    wp_enqueue_script('prn-admin', PRN_URL . 'assets/admin.js',  ['jquery'], PRN_VERSION, true);
    wp_localize_script('prn-admin', 'prnAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('prn_nonce'),
    ]);
}

// ── AJAX ─────────────────────────────────────────────────────────────────────
foreach ([
    'prn_import_now'  => fn() => wp_send_json_success(['message' => 'Importados: ' . PRN_RSS_Importer::run()]),
    'prn_rewrite_now' => fn() => wp_send_json_success(['message' => 'Reescritos: ' . PRN_Gemini_Rewriter::run()]),
] as $action => $cb) {
    add_action("wp_ajax_$action", function() use ($action, $cb) {
        check_ajax_referer('prn_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        $cb();
    });
}

add_action('wp_ajax_prn_add_feed', function() {
    check_ajax_referer('prn_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    $wpdb->insert($wpdb->prefix . 'prn_feeds', [
        'feed_name'       => sanitize_text_field($_POST['feed_name']),
        'feed_url'        => esc_url_raw($_POST['feed_url']),
        'category_id'     => intval($_POST['category_id']),
        'prompt_override' => sanitize_textarea_field($_POST['prompt_override']),
        'active'          => 1,
    ]);
    wp_send_json_success(['id' => $wpdb->insert_id]);
});

add_action('wp_ajax_prn_delete_feed', function() {
    check_ajax_referer('prn_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'prn_feeds', ['id' => intval($_POST['feed_id'])]);
    wp_send_json_success();
});

add_action('wp_ajax_prn_toggle_feed', function() {
    check_ajax_referer('prn_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    $f = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}prn_feeds WHERE id=%d", intval($_POST['feed_id'])));
    if ($f) $wpdb->update($wpdb->prefix . 'prn_feeds', ['active' => $f->active ? 0 : 1], ['id' => $f->id]);
    wp_send_json_success();
});
