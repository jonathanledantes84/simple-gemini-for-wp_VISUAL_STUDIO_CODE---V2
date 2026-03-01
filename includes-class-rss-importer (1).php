<?php
if (!defined('ABSPATH')) exit;

class PRN_RSS_Importer {

    public static function run() {
        global $wpdb;
        $feeds = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}prn_feeds WHERE active=1");
        $total = 0;
        foreach ($feeds as $feed) {
            $total += self::import_feed($feed);
            $wpdb->update($wpdb->prefix . 'prn_feeds', ['last_fetch' => current_time('mysql')], ['id' => $feed->id]);
        }
        return $total;
    }

    private static function import_feed($feed) {
        $rss = fetch_feed($feed->feed_url);
        if (is_wp_error($rss)) return 0;

        $items    = $rss->get_items(0, 20);
        $imported = 0;
        global $wpdb;

        foreach ($items as $item) {
            $guid    = $item->get_id() ?: $item->get_permalink();
            $title   = $item->get_title();
            $content = $item->get_content() ?: $item->get_description();
            $link    = $item->get_permalink();
            $date    = $item->get_date('Y-m-d H:i:s');

            // ── Detección avanzada de duplicados ────────────────────────────
            if (PRN_Duplicate_Checker::is_duplicate($guid, $title, $content, $feed->id)) continue;

            $image_url   = self::extract_image($item, $content);
            $clean_html  = wp_kses_post($content);

            $post_data = [
                'post_title'   => sanitize_text_field($title),
                'post_content' => $clean_html,
                'post_status'  => 'draft',
                'post_date'    => $date,
                'post_author'  => 1,
                'meta_input'   => [
                    '_prn_original_url'   => $link,
                    '_prn_original_image' => $image_url,
                    '_prn_feed_id'        => $feed->id,
                    '_prn_rewritten'      => '0',
                    '_prn_feed_name'      => $feed->feed_name,
                ],
            ];

            if ($feed->category_id) $post_data['post_category'] = [$feed->category_id];

            $post_id = wp_insert_post($post_data);
            if (!$post_id || is_wp_error($post_id)) continue;

            // Descargar imagen del feed original
            if ($image_url) self::set_featured_image($post_id, $image_url, $title);

            $wpdb->insert($wpdb->prefix . 'prn_imported', [
                'feed_id'   => $feed->id,
                'item_guid' => $guid,
                'item_hash' => PRN_Duplicate_Checker::content_hash($content),
                'post_id'   => $post_id,
                'rewritten' => 0,
            ]);
            $imported++;
        }
        return $imported;
    }

    private static function extract_image($item, $content) {
        $enc = $item->get_enclosure();
        if ($enc) {
            $url = $enc->get_link();
            if ($url && preg_match('/\.(jpg|jpeg|png|webp|gif)/i', $url)) return $url;
        }
        $media = $item->get_item_tags('http://search.yahoo.com/mrss/', 'content');
        if ($media && isset($media[0]['attribs']['']['url'])) return $media[0]['attribs']['']['url'];
        if (preg_match('/<img[^>]+src=["\'](https?:\/\/[^"\']+)["\']/i', $content, $m)) return $m[1];
        return '';
    }

    public static function set_featured_image($post_id, $image_url, $title) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $tmp = download_url($image_url, 30);
        if (is_wp_error($tmp)) return;
        $file = ['name' => sanitize_file_name(basename(parse_url($image_url, PHP_URL_PATH))), 'tmp_name' => $tmp];
        $id   = media_handle_sideload($file, $post_id, $title);
        if (!is_wp_error($id)) set_post_thumbnail($post_id, $id);
        @unlink($tmp);
    }
}
