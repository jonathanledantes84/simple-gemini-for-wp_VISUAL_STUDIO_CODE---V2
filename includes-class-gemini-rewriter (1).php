<?php
if (!defined('ABSPATH')) exit;

class PRN_Gemini_Rewriter {

    // Modelos disponibles (gratuitos)
    const MODELS = [
        'gemini-1.5-flash'   => 'Gemini 1.5 Flash (recomendado, gratis, rápido)',
        'gemini-1.5-pro'     => 'Gemini 1.5 Pro (más potente, límite menor)',
        'gemini-2.0-flash'   => 'Gemini 2.0 Flash (más nuevo, experimental)',
    ];

    const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public static function run() {
        $api_key  = get_option('prn_gemini_api_key', '');
        if (!$api_key) return 0;

        $per_run  = (int) get_option('prn_posts_per_run', 5);
        $posts    = get_posts([
            'post_status'    => 'draft',
            'posts_per_page' => $per_run,
            'meta_query'     => [
                ['key' => '_prn_rewritten', 'value' => '0'],
                ['key' => '_prn_feed_id',   'compare' => 'EXISTS'],
            ],
        ]);

        $rewritten = 0;
        foreach ($posts as $post) {
            if (self::rewrite_post($post, $api_key)) $rewritten++;
            sleep(2);
        }
        return $rewritten;
    }

    private static function rewrite_post($post, $api_key) {
        global $wpdb;

        $feed_id = get_post_meta($post->ID, '_prn_feed_id', true);
        $feed    = $feed_id ? $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}prn_feeds WHERE id=%d", $feed_id
        )) : null;

        // ── Opciones avanzadas ───────────────────────────────────────────────
        $model       = get_option('prn_gemini_model', 'gemini-1.5-flash');
        $temperature = (float) get_option('prn_temperature', 0.7);
        $min_words   = (int)   get_option('prn_min_words', 300);
        $max_words   = (int)   get_option('prn_max_words', 500);
        $tone        = get_option('prn_tone', 'profesional');

        $prompt = ($feed && $feed->prompt_override)
            ? $feed->prompt_override
            : get_option('prn_default_prompt', self::get_default_prompt());

        // Inyectar parámetros dinámicos en el prompt
        $prompt = str_replace(
            ['{min_words}', '{max_words}', '{tone}'],
            [$min_words,    $max_words,    $tone],
            $prompt
        );

        $original_title   = $post->post_title;
        $original_content = substr(wp_strip_all_tags($post->post_content), 0, 3000);

        $full_prompt = $prompt
            . "\n\n---TÍTULO ORIGINAL---\n" . $original_title
            . "\n\n---CONTENIDO ORIGINAL---\n" . $original_content
            . "\n\n---FIN---\n\n"
            . 'Responde ÚNICAMENTE con un JSON válido con este formato: {"titulo":"...","contenido":"...","descripcion_imagen":"..."}';

        $response = self::call_gemini($full_prompt, $api_key, $model, $temperature);
        if (!$response) return false;

        $parsed = self::parse_response($response);
        if (!$parsed) return false;

        // ── Actualizar post ──────────────────────────────────────────────────
        $new_status = get_option('prn_auto_publish', '0') === '1' ? 'publish' : 'draft';
        wp_update_post([
            'ID'           => $post->ID,
            'post_title'   => sanitize_text_field($parsed['titulo']),
            'post_content' => wp_kses_post($parsed['contenido']),
            'post_status'  => $new_status,
        ]);

        update_post_meta($post->ID, '_prn_rewritten', '1');
        $wpdb->update($wpdb->prefix . 'prn_imported', ['rewritten' => 1], ['post_id' => $post->ID]);

        // ── Generar imagen IA (si está habilitado) ───────────────────────────
        if (get_option('prn_generate_images', '1') === '1') {
            // Solo genera imagen IA si no tiene imagen del RSS
            $has_thumb = has_post_thumbnail($post->ID);
            if (!$has_thumb) {
                PRN_Image_Generator::generate_and_set(
                    $post->ID,
                    sanitize_text_field($parsed['titulo']),
                    $parsed['contenido'],
                    $api_key
                );
            }
        }

        return true;
    }

    private static function call_gemini($prompt, $api_key, $model, $temperature) {
        $url  = self::API_BASE . $model . ':generateContent?key=' . $api_key;
        $body = json_encode([
            'contents'         => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature'     => $temperature,
                'maxOutputTokens' => 2048,
            ],
        ]);
        $res = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => 60,
        ]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) return false;
        $data = json_decode(wp_remote_retrieve_body($res), true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? false;
    }

    private static function parse_response($text) {
        // Limpiar posibles bloques markdown
        $text = preg_replace('/^```jsons*/m', '', $text);
        $text = preg_replace('/^```s*/m', '', $text);

        // Intentar extraer JSON con regex flexible
        if (preg_match('/\{.*?"titulo".*?"contenido".*?\}/s', $text, $m)) {
            $json = json_decode($m[0], true);
            if ($json && isset($json['titulo'], $json['contenido'])) return $json;
        }
        $json = json_decode($text, true);
        if ($json && isset($json['titulo'], $json['contenido'])) return $json;
        return false;
    }

    public static function get_default_prompt() {
        return 'Eres un redactor periodístico profesional de Punto Río Negro, portal de noticias de la Patagonia argentina (Río Negro y Neuquén). Tu estilo es similar al de rionegro.com.ar: claro, directo, conciso, con tono {tone} y estructura periodística profesional.

INSTRUCCIONES:
1. Reescribe completamente el título y el contenido. NO copies frases del original.
2. El título debe ser atractivo, informativo, máximo 12 palabras.
3. El contenido debe tener entre {min_words} y {max_words} palabras.
4. Usa párrafos cortos (2-3 oraciones). Aplica la pirámide invertida.
5. NO menciones la fuente original. NO incluyas disclaimers.
6. Lenguaje periodístico rioplatense (voseo cuando corresponda).
7. Incluye datos concretos, cifras y contexto cuando estén disponibles.
8. El contenido en HTML usando <p> y <strong>.
9. En "descripcion_imagen" escribe en INGLÉS una descripción concisa (15 palabras) de una fotografía periodística ideal para ilustrar esta noticia en la Patagonia argentina.';
    }
}
