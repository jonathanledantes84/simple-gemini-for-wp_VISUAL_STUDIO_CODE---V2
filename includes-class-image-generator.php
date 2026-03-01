<?php
if (!defined('ABSPATH')) exit;

/**
 * PRN_Image_Generator
 * Genera imágenes periodísticas usando la API de Gemini Imagen (gratis tier).
 * Nota: usa el endpoint de Imagen 3 (imagen-3.0-generate-002) disponible en el plan gratuito.
 */
class PRN_Image_Generator {

    const IMAGEN_URL = 'https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-002:predict';

    /**
     * Genera una imagen para el post y la establece como imagen destacada.
     * @return bool
     */
    public static function generate_and_set($post_id, $title, $rewritten_content, $api_key) {
        if (!$api_key) return false;

        // Construir prompt de imagen periodística
        $img_prompt = self::build_image_prompt($title, $rewritten_content);

        $url  = self::IMAGEN_URL . '?key=' . $api_key;
        $body = json_encode([
            'instances'  => [['prompt' => $img_prompt]],
            'parameters' => [
                'sampleCount'  => 1,
                'aspectRatio'  => '16:9',
                'safetyFilterLevel' => 'block_few',
            ],
        ]);

        $response = wp_remote_post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => 90,
        ]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return false;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        $b64  = $data['predictions'][0]['bytesBase64Encoded'] ?? null;
        if (!$b64) return false;

        // Guardar imagen en biblioteca de medios
        return self::save_base64_image($post_id, $b64, $title);
    }

    private static function build_image_prompt($title, $content) {
        // Extraer palabras clave del título para el prompt
        $keywords = implode(' ', array_slice(
            array_filter(explode(' ', preg_replace('/[^a-záéíóúüñA-ZÁÉÍÓÚÜÑ\s]/u', '', $title))),
            0, 8
        ));
        $prompt = get_option('prn_image_prompt_template',
            'Professional news photograph for a Patagonia Argentina news article. Subject: {keywords}. ' .
            'Style: photojournalism, newspaper quality, sharp focus, natural lighting, editorial photography. ' .
            'Setting: Patagonia Argentina region, Río Negro or Neuquén province. ' .
            'NO text, NO watermarks, NO logos. High quality, realistic.'
        );
        return str_replace('{keywords}', $keywords, $prompt);
    }

    private static function save_base64_image($post_id, $b64, $title) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $img_data = base64_decode($b64);
        $filename  = 'prn-ai-' . $post_id . '-' . time() . '.jpg';
        $upload    = wp_upload_dir();
        $filepath  = trailingslashit($upload['path']) . $filename;

        if (file_put_contents($filepath, $img_data) === false) return false;

        $filetype  = wp_check_filetype($filename, null);
        $attach_id = wp_insert_attachment([
            'guid'           => trailingslashit($upload['url']) . $filename,
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_text_field($title) . ' — imagen IA',
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $filepath, $post_id);

        if (is_wp_error($attach_id)) return false;

        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);
        set_post_thumbnail($post_id, $attach_id);
        update_post_meta($post_id, '_prn_ai_image', '1');
        return true;
    }
}
