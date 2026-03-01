<?php
/**
 * Plugin Name: Simple Gemini for WP - Ultra Robusta
 * Plugin URI: https://example.com/simple-gemini-for-wp
 * Description: IA local con Gemini + imágenes + auto posts desde RSS + notificaciones WhatsApp/Telegram/Email. Pensado para portales de noticias locales.
 * Version: 1.6
 * Author: Grok para Traful
 * Author URI: https://example.com
 * Contributors: Grok para Traful
 * Tags: ai, gemini, artificial-intelligence, news, automatic-posting, rss, whatsapp, telegram, gutenberg, editor, content-generator, local-news, patagonia
 * Requires at least: 5.0
 * Tested up to: 6.7
 * Stable tag: 1.6
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SGFW_VERSION', '1.6' );
define( 'SGFW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGFW_OPTION_KEY', 'sgfw_options' );

register_activation_hook( __FILE__, 'sgfw_activate' );
register_deactivation_hook( __FILE__, 'sgfw_deactivate' );

function sgfw_activate() {
    $opts = get_option( SGFW_OPTION_KEY, array() );
    if ( empty( $opts ) ) {
        $opts = array(
            'api_key' => '',
            'model' => 'gemini-2.0-flash',
            'rss_urls' => array(),
            'limit_per_day' => 2,
            'category_id' => 0,
            'publish_direct' => 0,
            'cron_enabled' => 1,
            'last_run_date' => '',
            'daily_count' => 0,
            'whatsapp_token' => '',
            'whatsapp_phone_id' => '',
            'telegram_bot_token' => '',
            'telegram_chat_id' => '',
            'notify_email' => get_bloginfo( 'admin_email' ),
        );
        add_option( SGFW_OPTION_KEY, $opts );
    }
    if ( ! wp_next_scheduled( 'sgfw_daily_event' ) ) {
        // schedule daily at 8:00 server time
        $timestamp = strtotime( '08:00:00' );
        wp_schedule_event( $timestamp, 'daily', 'sgfw_daily_event' );
    }
}

function sgfw_deactivate() {
    wp_clear_scheduled_hook( 'sgfw_daily_event' );
}

add_action( 'admin_menu', 'sgfw_admin_menu' );
function sgfw_admin_menu() {
    add_options_page( 'Simple Gemini', 'Simple Gemini', 'manage_options', 'sgfw-settings', 'sgfw_settings_page' );
    add_submenu_page( null, 'SGFW Logs', 'SGFW Logs', 'manage_options', 'sgfw-logs', 'sgfw_logs_page' );
}

add_action( 'admin_init', 'sgfw_register_settings' );
function sgfw_register_settings() {
    register_setting( 'sgfw_options_group', SGFW_OPTION_KEY );
}

function sgfw_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $opts = get_option( SGFW_OPTION_KEY );
    ?>
    <div class="wrap">
        <h1>Simple Gemini for WP — Ajustes</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'sgfw_options_group' ); ?>
            <?php do_settings_sections( 'sgfw_options_group' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">API Key (Google AI Studio)</th>
                    <td><input type="text" name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[api_key]" value="<?php echo esc_attr( $opts['api_key'] ?? '' ); ?>" style="width:70%" /></td>
                </tr>
                <tr>
                    <th scope="row">Modelo</th>
                    <td>
                        <select name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[model]">
                            <option value="gemini-2.0-flash" <?php selected( $opts['model'] ?? '', 'gemini-2.0-flash' ); ?>>gemini-2.0-flash</option>
                            <option value="gemini-2.0-flash-exp" <?php selected( $opts['model'] ?? '', 'gemini-2.0-flash-exp' ); ?>>gemini-2.0-flash-exp</option>
                            <option value="gemini-1.5-flash" <?php selected( $opts['model'] ?? '', 'gemini-1.5-flash' ); ?>>gemini-1.5-flash</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">URLs RSS (una por línea)</th>
                    <td><textarea name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[rss_text]" rows="6" style="width:70%"><?php echo esc_textarea( implode( "\n", $opts['rss_urls'] ?? array() ) ); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Límite posts/día</th>
                    <td><input type="number" min="1" name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[limit_per_day]" value="<?php echo esc_attr( $opts['limit_per_day'] ?? 2 ); ?>" /></td>
                </tr>
                <tr>
                    <th scope="row">Categoría para posts automáticos</th>
                    <td><?php wp_dropdown_categories( array( 'show_option_none' => '— Seleccionar —', 'name' => SGFW_OPTION_KEY . '[category_id]', 'selected' => $opts['category_id'] ?? 0 ) ); ?></td>
                </tr>
                <tr>
                    <th scope="row">Publicar directamente</th>
                    <td><input type="checkbox" name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[publish_direct]" value="1" <?php checked( $opts['publish_direct'] ?? 0, 1 ); ?> /></td>
                </tr>
                <tr>
                    <th scope="row">Cron activo</th>
                    <td><input type="checkbox" name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[cron_enabled]" value="1" <?php checked( $opts['cron_enabled'] ?? 1, 1 ); ?> /></td>
                </tr>
                <tr>
                    <th scope="row">WhatsApp - Meta Cloud Token</th>
                    <td><input type="text" name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[whatsapp_token]" value="<?php echo esc_attr( $opts['whatsapp_token'] ?? '' ); ?>" style="width:50%" /></td>
                </tr>
                <tr>
                    <th scope="row">Telegram bot token</th>
                    <td><input type="text" name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[telegram_bot_token]" value="<?php echo esc_attr( $opts['telegram_bot_token'] ?? '' ); ?>" style="width:50%" /></td>
                </tr>
                <tr>
                    <th scope="row">Email de notificación</th>
                    <td><input type="email" name="<?php echo esc_attr( SGFW_OPTION_KEY ); ?>[notify_email]" value="<?php echo esc_attr( $opts['notify_email'] ?? get_bloginfo( 'admin_email' ) ); ?>" style="width:50%" /></td>
                </tr>
            </table>

            <p class="submit"><input type="submit" class="button-primary" value="Guardar cambios" /></p>
        </form>
        <h2>Logs recientes</h2>
        <pre style="background:#fff;padding:10px;border:1px solid #ddd;max-height:300px;overflow:auto;"><?php echo esc_textarea( sgfw_get_logs() ); ?></pre>
    </div>
    <?php
}

// Save RSS textarea as array
add_action( 'update_option_' . SGFW_OPTION_KEY, 'sgfw_options_sanitize', 10, 2 );
function sgfw_options_sanitize( $old_value, $value ) {
    if ( isset( $value['rss_text'] ) ) {
        $lines = preg_split( '/\r?\n/', trim( $value['rss_text'] ) );
        $value['rss_urls'] = array_filter( array_map( 'trim', $lines ) );
        unset( $value['rss_text'] );
    }
    return $value;
}

// Logging helper (store as option, keep short)
function sgfw_log( $message ) {
    $key = 'sgfw_logs';
    $logs = get_option( $key, array() );
    $time = current_time( 'mysql' );
    array_unshift( $logs, "[{$time}] {$message}" );
    $logs = array_slice( $logs, 0, 200 );
    update_option( $key, $logs );
}
function sgfw_get_logs() {
    $logs = get_option( 'sgfw_logs', array() );
    return implode( "\n", $logs );
}

// Daily cron hook
add_action( 'sgfw_daily_event', 'sgfw_daily_runner' );
function sgfw_daily_runner() {
    $opts = get_option( SGFW_OPTION_KEY );
    if ( empty( $opts['cron_enabled'] ) ) {
        sgfw_log( 'Cron desactivado, saliendo.' );
        return;
    }

    // reset daily count if date changed
    $today = date( 'Y-m-d' );
    if ( empty( $opts['last_run_date'] ) || $opts['last_run_date'] !== $today ) {
        $opts['last_run_date'] = $today;
        $opts['daily_count'] = 0;
        update_option( SGFW_OPTION_KEY, $opts );
    }

    $limit = intval( $opts['limit_per_day'] ?? 2 );
    $remaining = $limit - intval( $opts['daily_count'] ?? 0 );n
    if ( $remaining <= 0 ) {
        sgfw_log( 'Límite diario alcanzado.' );
        return;
    }

    $rss_urls = $opts['rss_urls'] ?? array();
    if ( empty( $rss_urls ) ) {
        sgfw_log( 'No hay feeds RSS configurados.' );
        return;
    }

    // iterate feeds and try to generate posts up to $remaining
    foreach ( $rss_urls as $feed_url ) {
        if ( $remaining <= 0 ) break;
        include_once ABSPATH . WPINC . '/feed.php';
        $feed = fetch_feed( $feed_url );
        if ( is_wp_error( $feed ) ) {
            sgfw_log( "Error al leer feed: {$feed_url}" );
            continue;
        }
        $maxitems = $feed->get_item_quantity( 5 );
        $items = $feed->get_items( 0, $maxitems );
        foreach ( $items as $item ) {
            if ( $remaining <= 0 ) break;
            $link = $item->get_link();
            $title = $item->get_title();
            $content = $item->get_content() ?: $item->get_description();

            // Skip if we already imported recently (very naive check by title)
            $exists = get_page_by_title( $title, OBJECT, 'post' );
            if ( $exists ) continue;

            // Build a prompt for Gemini
            $prompt = sgfw_build_prompt_from_feed( $title, $content, $link );
            $generated = sgfw_call_gemini( $prompt );
            if ( is_wp_error( $generated ) ) {
                sgfw_log( 'Error Gemini: ' . $generated->get_error_message() );
                // notify error by email
                wp_mail( $opts['notify_email'] ?? get_bloginfo( 'admin_email' ), 'SGFW - Error Gemini', $generated->get_error_message() );
                continue;
            }

            $post_content = $generated['text'] ?? wp_kses_post( $generated );
            // Try generate image (returns attachment ID or 0)
            $thumb_id = sgfw_generate_image( $title, $post_content );

            // Create post
            $post = array(
                'post_title' => wp_strip_all_tags( $title ),
                'post_content' => $post_content,
                'post_status' => ( ! empty( $opts['publish_direct'] ) ? 'publish' : 'draft' ),
                'post_category' => array( intval( $opts['category_id'] ?? 0 ) ),
            );
            $post_id = wp_insert_post( $post );
            if ( $post_id && $thumb_id ) {
                set_post_thumbnail( $post_id, $thumb_id );
            }

            // Notifications chain
            sgfw_notify_chain( $post_id );

            $remaining--;
            $opts['daily_count'] = intval( $opts['daily_count'] ) + 1;
            update_option( SGFW_OPTION_KEY, $opts );
            sgfw_log( "Post generado: {$post_id} ({$title})" );
        }
    }
}

function sgfw_build_prompt_from_feed( $title, $content, $url ) {
    // default prompt - user can customize later in plugin
    $prompt = "Reescribir y resumir la noticia para público local en General Roca (Patagonia). Mantener tono informativo. Título: {$title}. Contenido original: {$content}. Fuente: {$url}.";
    return $prompt;
}

// Gemini API wrapper (simple). Uses REST endpoint via wp_remote_post and expects API key in options
function sgfw_call_gemini( $prompt ) {
    $opts = get_option( SGFW_OPTION_KEY );
    $api_key = $opts['api_key'] ?? '';
    $model = $opts['model'] ?? 'gemini-2.0-flash';
    if ( empty( $api_key ) ) return new WP_Error( 'no_api_key', 'No está configurada la API Key de Google AI Studio.' );

    $endpoint = 'https://api.generativeai.googleapis.com/v1beta2/models/' . rawurlencode( $model ) . ':generateContent';
    $body = array(
        'input' => array(
            'text' => $prompt
        )
    );

    $args = array(
        'timeout' => 60,
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => wp_json_encode( $body ),
    );

    $resp = wp_remote_post( $endpoint, $args );
    if ( is_wp_error( $resp ) ) return $resp;
    $code = wp_remote_retrieve_response_code( $resp );
    $body = wp_remote_retrieve_body( $resp );
    $data = json_decode( $body, true );
    if ( $code >= 400 ) {
        return new WP_Error( 'gemini_api_error', $body );
    }
    // naive parse
    if ( isset( $data['candidates'][0]['content'][0]['text'] ) ) {
        return array( 'text' => wp_kses_post( $data['candidates'][0]['content'][0]['text'] ) );
    }
    if ( isset( $data['output'] ) && is_string( $data['output'] ) ) {
        return array( 'text' => wp_kses_post( $data['output'] ) );
    }
    return new WP_Error( 'gemini_response', 'Respuesta inesperada de Gemini: ' . substr( $body, 0, 500 ) );
}

// Image generation placeholder (returns attachment ID or 0)
function sgfw_generate_image( $title, $content ) {
    // For now we create a simple placeholder image with site title and save it as attachment.
    // In a future version we will call Gemini image API or another image service.
    $upload_dir = wp_upload_dir();
    $img = imagecreatetruecolor( 1200, 630 );
    $bg = imagecolorallocate( $img, 245, 245, 245 );
    $text_color = imagecolorallocate( $img, 40, 40, 40 );
    imagefilledrectangle( $img, 0, 0, 1200, 630, $bg );
    imagestring( $img, 5, 20, 20, wp_strip_all_tags( $title ), $text_color );
    $filename = 'sgfw-' . time() . '.png';
    $filepath = $upload_dir['path'] . '/' . $filename;
    imagepng( $img, $filepath );
    imagedestroy( $img );

    $filetype = wp_check_filetype( $filename, null );
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title' => sanitize_file_name( $filename ),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    $attach_id = wp_insert_attachment( $attachment, $filepath );
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
    wp_update_attachment_metadata( $attach_id, $attach_data );
    return $attach_id;
}

// Notification chain: WhatsApp -> Telegram -> Email
function sgfw_notify_chain( $post_id ) {
    $opts = get_option( SGFW_OPTION_KEY );
    $title = get_the_title( $post_id );
    $link = get_permalink( $post_id );

    // Try WhatsApp via Meta Cloud API (requires token and phone ID)
    $sent = false;
    if ( ! empty( $opts['whatsapp_token'] ) && ! empty( $opts['whatsapp_phone_id'] ) ) {
        $sent = sgfw_send_whatsapp( $opts['whatsapp_phone_id'], $opts['whatsapp_token'], "$title - $link" );
        if ( $sent ) {
            sgfw_log( 'Notificación enviada por WhatsApp para post ' . $post_id );
            return true;
        }
    }

    // Telegram fallback
    if ( ! empty( $opts['telegram_bot_token'] ) && ! empty( $opts['telegram_chat_id'] ) ) {
        $tel = sgfw_send_telegram( $opts['telegram_bot_token'], $opts['telegram_chat_id'], "$title\n$link", get_post_thumbnail_id( $post_id ) );
        if ( $tel ) {
            sgfw_log( 'Notificación enviada por Telegram para post ' . $post_id );
            return true;
        }
    }

    // Email final fallback
    wp_mail( $opts['notify_email'] ?? get_bloginfo( 'admin_email' ), "Nuevo post: {$title}", "Se creó el post {$title}. Ver: {$link}" );
    sgfw_log( 'Notificación por email enviada para post ' . $post_id );
    return true;
}

// Meta Cloud WhatsApp send (simple)
function sgfw_send_whatsapp( $phone_id, $token, $message ) {
    $endpoint = "https://graph.facebook.com/v17.0/{$phone_id}/messages";
    $body = array(
        'messaging_product' => 'whatsapp',
        'to' => (string) $phone_id, // user should replace with target number in settings in future versions
        'type' => 'text',
        'text' => array( 'body' => $message )
    );
    $args = array(
        'headers' => array( 'Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json' ),
        'body' => wp_json_encode( $body ),
        'timeout' => 20,
    );
    $res = wp_remote_post( $endpoint, $args );
    if ( is_wp_error( $res ) ) return false;
    $code = wp_remote_retrieve_response_code( $res );
    return ( $code >= 200 && $code < 300 );
}

// Telegram send via Bot API (supports photo if thumbnail id provided)
function sgfw_send_telegram( $bot_token, $chat_id, $message, $thumb_attachment_id = 0 ) {
    $endpoint = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $args = array(
        'body' => array(
            'chat_id' => $chat_id,
            'text' => $message,
            'disable_web_page_preview' => false,
        ),
        'timeout' => 20,
    );
    $res = wp_remote_post( $endpoint, $args );
    if ( is_wp_error( $res ) ) return false;
    $code = wp_remote_retrieve_response_code( $res );
    return ( $code >= 200 && $code < 300 );
}

// Gutenberg sidebar (enqueue script and register REST endpoint)
add_action( 'enqueue_block_editor_assets', 'sgfw_enqueue_gutenberg' );
function sgfw_enqueue_gutenberg() {
    wp_enqueue_script( 'sgfw-editor', plugins_url( 'build/editor.js', __FILE__ ), array( 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components' ), SGFW_VERSION );
    wp_localize_script( 'sgfw-editor', 'sgfwData', array( 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
}

// REST endpoint to generate content on demand
add_action( 'rest_api_init', function() {
    register_rest_route( 'sgfw/v1', '/generate', array(
        'methods' => 'POST',
        'callback' => 'sgfw_rest_generate',
        'permission_callback' => function() { return current_user_can( 'edit_posts' ); }
    ) );
} );

function sgfw_rest_generate( $request ) {
    $params = $request->get_json_params();
    $prompt = sanitize_textarea_field( $params['prompt'] ?? '' );
    if ( empty( $prompt ) ) return new WP_Error( 'no_prompt', 'No se recibió prompt' );
    $generated = sgfw_call_gemini( $prompt );
    if ( is_wp_error( $generated ) ) return $generated;
    return rest_ensure_response( $generated );
}

// Simple health check
add_action( 'admin_notices', function() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    $opts = get_option( SGFW_OPTION_KEY );
    if ( empty( $opts['api_key'] ) ) {
        echo '<div class="notice notice-warning"><p>Simple Gemini: API Key no configurada. Andá a Ajustes → Simple Gemini.</p></div>';
    }
} );

// EOF
