<?php
if (!defined('ABSPATH')) exit;

class RAR_RSS_Importer {

    public static function run() {
        global $wpdb;
        $feeds = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}rar_feeds WHERE active=1");
        $total_imported = 0;

        foreach ($feeds as $feed) {
            $imported = self::import_feed($feed);
            $total_imported += $imported;
            $wpdb->update(
                $wpdb->prefix . 'rar_feeds',
                array('last_fetch' => current_time('mysql')),
                array('id' => $feed->id)
            );
        }

        return $total_imported;
    }

    private static function import_feed($feed) {
        $rss = fetch_feed($feed->feed_url);
        if (is_wp_error($rss)) return 0;

        $items = $rss->get_items(0, 20);
        $imported = 0;
        global $wpdb;

        foreach ($items as $item) {
            $guid = $item->get_id();
            if (!$guid) $guid = $item->get_permalink();

            // Verificar si ya fue importado
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}rar_imported WHERE item_guid=%s",
                $guid
            ));
            if ($exists) continue;

            // Obtener datos del item
            $title   = $item->get_title();
            $content = $item->get_content();
            if (!$content) $content = $item->get_description();
            $link    = $item->get_permalink();
            $date    = $item->get_date('Y-m-d H:i:s');
            $category_id = $feed->category_id ? $feed->category_id : null;

            // Obtener imagen del RSS
            $image_url = self::extract_image($item, $content);

            // Limpiar contenido HTML
            $clean_content = wp_kses_post($content);

            // Crear post en borrador
            $post_data = array(
                'post_title'   => sanitize_text_field($title),
                'post_content' => $clean_content,
                'post_status'  => 'draft',
                'post_date'    => $date,
                'post_author'  => 1,
                'meta_input'   => array(
                    '_rar_original_url'   => $link,
                    '_rar_original_image' => $image_url,
                    '_rar_feed_id'        => $feed->id,
                    '_rar_rewritten'      => '0',
                    '_rar_feed_name'      => $feed->feed_name,
                ),
            );

            if ($category_id) {
                $post_data['post_category'] = array($category_id);
            }

            $post_id = wp_insert_post($post_data);

            if ($post_id && !is_wp_error($post_id)) {
                // Descargar y asignar imagen destacada
                if ($image_url) {
                    self::set_featured_image($post_id, $image_url, $title);
                }

                // Registrar como importado
                $wpdb->insert($wpdb->prefix . 'rar_imported', array(
                    'feed_id'   => $feed->id,
                    'item_guid' => $guid,
                    'post_id'   => $post_id,
                    'rewritten' => 0,
                ));

                $imported++;
            }
        }

        return $imported;
    }

    private static function extract_image($item, $content) {
        // 1. Intentar enclosure/media
        $enclosure = $item->get_enclosure();
        if ($enclosure) {
            $url = $enclosure->get_link();
            if ($url && preg_match('/\.(jpg|jpeg|png|webp|gif)/i', $url)) {
                return $url;
            }
        }

        // 2. Buscar en media:content
        $media = $item->get_item_tags('http://search.yahoo.com/mrss/', 'content');
        if ($media && isset($media[0]['attribs']['']['url'])) {
            return $media[0]['attribs']['']['url'];
        }

        // 3. Buscar og:image o primera img en el contenido
        if (preg_match('/<img[^>]+src=["\'](https?:\/\/[^"\']+)["\']/i', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private static function set_featured_image($post_id, $image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $tmp = download_url($image_url, 30);
        if (is_wp_error($tmp)) return;

        $file_array = array(
            'name'     => sanitize_file_name(basename(parse_url($image_url, PHP_URL_PATH))),
            'tmp_name' => $tmp,
        );

        $attach_id = media_handle_sideload($file_array, $post_id, $title);
        if (!is_wp_error($attach_id)) {
            set_post_thumbnail($post_id, $attach_id);
        }
        @unlink($tmp);
    }
}
