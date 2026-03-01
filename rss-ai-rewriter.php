<?php
/**
 * Plugin Name: RSS AI Rewriter
 * Plugin URI: https://example.com
 * Description: Importa noticias desde feeds RSS y las reescribe automáticamente con Google Gemini AI
 * Version: 1.0.0
 * Author: Tu Portal
 * Text Domain: rss-ai-rewriter
 */

if (!defined('ABSPATH')) exit;

define('RAR_VERSION', '1.0.0');
define('RAR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RAR_PLUGIN_URL', plugin_dir_url(__FILE__));

// Incluir archivos
require_once RAR_PLUGIN_DIR . 'includes/class-rss-importer.php';
require_once RAR_PLUGIN_DIR . 'includes/class-gemini-rewriter.php';
require_once RAR_PLUGIN_DIR . 'includes/class-admin-page.php';

// Activación del plugin
register_activation_hook(__FILE__, 'rar_activate');
function rar_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'rar_feeds';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        feed_name varchar(200) NOT NULL,
        feed_url varchar(500) NOT NULL,
        category_id bigint(20) NOT NULL DEFAULT 0,
        prompt_override text,
        active tinyint(1) DEFAULT 1,
        last_fetch datetime DEFAULT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Tabla de noticias importadas (para evitar duplicados)
    $table_imported = $wpdb->prefix . 'rar_imported';
    $sql2 = "CREATE TABLE IF NOT EXISTS $table_imported (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        feed_id mediumint(9) NOT NULL,
        item_guid varchar(500) NOT NULL,
        post_id bigint(20) DEFAULT NULL,
        imported_at datetime DEFAULT CURRENT_TIMESTAMP,
        rewritten tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id),
        UNIQUE KEY item_guid (item_guid(255))
    ) $charset_collate;";
    dbDelta($sql2);

    // Programar cronjob
    if (!wp_next_scheduled('rar_cron_import')) {
        wp_schedule_event(time(), 'hourly', 'rar_cron_import');
    }
    if (!wp_next_scheduled('rar_cron_rewrite')) {
        wp_schedule_event(time() + 300, 'rar_every_30min', 'rar_cron_rewrite');
    }
}

// Desactivación
register_deactivation_hook(__FILE__, 'rar_deactivate');
function rar_deactivate() {
    wp_clear_scheduled_hook('rar_cron_import');
    wp_clear_scheduled_hook('rar_cron_rewrite');
}

// Intervalos personalizados de cron
add_filter('cron_schedules', 'rar_cron_intervals');
function rar_cron_intervals($schedules) {
    $schedules['rar_every_30min'] = array(
        'interval' => 1800,
        'display' => __('Cada 30 minutos')
    );
    return $schedules;
}

// Hooks de cronjob
add_action('rar_cron_import', array('RAR_RSS_Importer', 'run'));
add_action('rar_cron_rewrite', array('RAR_Gemini_Rewriter', 'run'));

// Menu admin
add_action('admin_menu', array('RAR_Admin_Page', 'add_menu'));
add_action('admin_init', array('RAR_Admin_Page', 'register_settings'));
add_action('admin_enqueue_scripts', 'rar_admin_scripts');

function rar_admin_scripts($hook) {
    if (strpos($hook, 'rss-ai-rewriter') === false) return;
    wp_enqueue_style('rar-admin', RAR_PLUGIN_URL . 'assets/admin.css', array(), RAR_VERSION);
    wp_enqueue_script('rar-admin', RAR_PLUGIN_URL . 'assets/admin.js', array('jquery'), RAR_VERSION, true);
    wp_localize_script('rar-admin', 'rarAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('rar_nonce')
    ));
}

// AJAX handlers
add_action('wp_ajax_rar_import_now', 'rar_ajax_import_now');
function rar_ajax_import_now() {
    check_ajax_referer('rar_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    $result = RAR_RSS_Importer::run();
    wp_send_json_success(array('message' => "Importación completada. Posts importados: $result"));
}

add_action('wp_ajax_rar_rewrite_now', 'rar_ajax_rewrite_now');
function rar_ajax_rewrite_now() {
    check_ajax_referer('rar_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    $result = RAR_Gemini_Rewriter::run();
    wp_send_json_success(array('message' => "Reescritura completada. Posts reescritos: $result"));
}

add_action('wp_ajax_rar_add_feed', 'rar_ajax_add_feed');
function rar_ajax_add_feed() {
    check_ajax_referer('rar_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    $table = $wpdb->prefix . 'rar_feeds';
    $wpdb->insert($table, array(
        'feed_name'      => sanitize_text_field($_POST['feed_name']),
        'feed_url'       => esc_url_raw($_POST['feed_url']),
        'category_id'    => intval($_POST['category_id']),
        'prompt_override'=> sanitize_textarea_field($_POST['prompt_override']),
        'active'         => 1,
    ));
    wp_send_json_success(array('id' => $wpdb->insert_id));
}

add_action('wp_ajax_rar_delete_feed', 'rar_ajax_delete_feed');
function rar_ajax_delete_feed() {
    check_ajax_referer('rar_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'rar_feeds', array('id' => intval($_POST['feed_id'])));
    wp_send_json_success();
}

add_action('wp_ajax_rar_toggle_feed', 'rar_ajax_toggle_feed');
function rar_ajax_toggle_feed() {
    check_ajax_referer('rar_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_die();
    global $wpdb;
    $feed = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}rar_feeds WHERE id=%d", intval($_POST['feed_id'])));
    if ($feed) {
        $wpdb->update($wpdb->prefix . 'rar_feeds', array('active' => $feed->active ? 0 : 1), array('id' => $feed->id));
    }
    wp_send_json_success();
}
