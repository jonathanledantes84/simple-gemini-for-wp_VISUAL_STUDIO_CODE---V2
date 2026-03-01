<?php
if (!defined('ABSPATH')) exit;

class RAR_Gemini_Rewriter {

    const API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public static function run($limit = 5) {
        global $wpdb;

        $api_key = get_option('rar_gemini_api_key', '');
        if (!$api_key) return 0;

        // Obtener posts borradores no reescritos
        $posts = get_posts(array(
            'post_status'    => 'draft',
            'posts_per_page' => $limit,
            'meta_query'     => array(
                array(
                    'key'   => '_rar_rewritten',
                    'value' => '0',
                ),
                array(
                    'key'     => '_rar_feed_id',
                    'compare' => 'EXISTS',
                ),
            ),
        ));

        $rewritten = 0;
        foreach ($posts as $post) {
            $success = self::rewrite_post($post, $api_key);
            if ($success) $rewritten++;
            sleep(2); // Respetar rate limit de Gemini
        }

        return $rewritten;
    }

    private static function rewrite_post($post, $api_key) {
        global $wpdb;

        $feed_id = get_post_meta($post->ID, '_rar_feed_id', true);
        $original_url = get_post_meta($post->ID, '_rar_original_url', true);

        // Obtener prompt personalizado del feed
        $custom_prompt = '';
        if ($feed_id) {
            $feed = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}rar_feeds WHERE id=%d", $feed_id
            ));
            if ($feed && $feed->prompt_override) {
                $custom_prompt = $feed->prompt_override;
            }
        }

        // Prompt por defecto si no hay personalizado
        $default_prompt = get_option('rar_default_prompt', self::get_default_prompt());
        $prompt_to_use = $custom_prompt ?: $default_prompt;

        $original_title   = $post->post_title;
        $original_content = wp_strip_all_tags($post->post_content);
        // Limitar contenido para no exceder tokens
        $original_content = substr($original_content, 0, 3000);

        $full_prompt = $prompt_to_use . "\n\n---TÍTULO ORIGINAL---\n" . $original_title
            . "\n\n---CONTENIDO ORIGINAL---\n" . $original_content
            . "\n\n---FIN DEL CONTENIDO---\n\n"
            . "Responde ÚNICAMENTE con un JSON válido con este formato exacto:\n"
            . '{"titulo": "...", "contenido": "..."}';

        $response = self::call_gemini($full_prompt, $api_key);
        if (!$response) return false;

        // Parsear respuesta JSON
        $parsed = self::parse_gemini_response($response);
        if (!$parsed) return false;

        // Actualizar el post
        wp_update_post(array(
            'ID'           => $post->ID,
            'post_title'   => sanitize_text_field($parsed['titulo']),
            'post_content' => wp_kses_post($parsed['contenido']),
        ));

        // Marcar como reescrito
        update_post_meta($post->ID, '_rar_rewritten', '1');

        // Actualizar registro en tabla importados
        $wpdb->update(
            $wpdb->prefix . 'rar_imported',
            array('rewritten' => 1),
            array('post_id' => $post->ID)
        );

        return true;
    }

    private static function call_gemini($prompt, $api_key) {
        $url = self::API_URL . '?key=' . $api_key;
        $body = json_encode(array(
            'contents' => array(array(
                'parts' => array(array('text' => $prompt))
            )),
            'generationConfig' => array(
                'temperature'     => 0.7,
                'maxOutputTokens' => 2048,
            ),
        ));

        $response = wp_remote_post($url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body'    => $body,
            'timeout' => 60,
        ));

        if (is_wp_error($response)) return false;
        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) return false;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? false;
    }

    private static function parse_gemini_response($text) {
        // Extraer JSON de la respuesta
        if (preg_match('/\{[^{}]*"titulo"[^{}]*"contenido"[^{}]*\}/s', $text, $matches)) {
            $json = json_decode($matches[0], true);
            if ($json && isset($json['titulo'], $json['contenido'])) {
                return $json;
            }
        }
        // Intentar parsear todo el texto como JSON
        $json = json_decode($text, true);
        if ($json && isset($json['titulo'], $json['contenido'])) {
            return $json;
        }
        return false;
    }

    public static function get_default_prompt() {
        return 'Eres un redactor periodístico profesional especializado en noticias de la Patagonia argentina (Río Negro y Neuquén). Tu estilo es similar al portal rionegro.com.ar: claro, directo, informativo, con un tono serio y profesional.

INSTRUCCIONES:
1. Reescribe completamente el título y el contenido de la noticia a continuación.
2. El título debe ser atractivo, claro y de no más de 12 palabras.
3. El contenido debe tener entre 300 y 500 palabras.
4. Usa párrafos cortos (2-3 oraciones máximo).
5. Comienza con un párrafo de entrada que resuma lo más importante (estilo pirámide invertida).
6. NO copies frases textuales del original.
7. NO menciones la fuente original.
8. Usa lenguaje periodístico argentino (tuteo NO, voseo SÍ cuando aplique).
9. Incluye datos concretos, cifras y contexto cuando estén disponibles.
10. El contenido debe estar en HTML básico usando <p> para párrafos y <strong> para destacar.';
    }
}
