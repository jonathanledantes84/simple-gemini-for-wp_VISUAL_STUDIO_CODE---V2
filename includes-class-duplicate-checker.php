<?php
if (!defined('ABSPATH')) exit;

/**
 * PRN_Duplicate_Checker
 * Detecta duplicados por GUID, hash de contenido Y similitud de título (Jaccard).
 */
class PRN_Duplicate_Checker {

    // Umbral de similitud: 0.5 = 50% palabras en común → se considera duplicado
    const SIMILARITY_THRESHOLD = 0.50;

    /**
     * Verifica si un ítem RSS ya fue importado o es muy similar a algo existente.
     * @return bool true = es duplicado (no importar)
     */
    public static function is_duplicate($guid, $title, $content, $feed_id) {
        global $wpdb;

        // 1. Verificar GUID exacto
        if ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}prn_imported WHERE item_guid=%s", $guid
        ))) return true;

        // 2. Verificar hash de contenido (detecta mismo contenido con distinto GUID)
        $hash = md5(preg_replace('/\s+/', ' ', strtolower(strip_tags($content))));
        if ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}prn_imported WHERE item_hash=%s", $hash
        ))) return true;

        // 3. Similitud de título contra posts recientes del mismo feed (últimos 100)
        $recent = $wpdb->get_col($wpdb->prepare(
            "SELECT p.post_title FROM {$wpdb->prefix}posts p
             INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
             WHERE pm.meta_key='_prn_feed_id' AND pm.meta_value=%s
             AND p.post_date > DATE_SUB(NOW(), INTERVAL 7 DAY)
             LIMIT 100",
            $feed_id
        ));

        foreach ($recent as $existing_title) {
            if (self::jaccard_similarity($title, $existing_title) >= self::SIMILARITY_THRESHOLD) {
                return true;
            }
        }

        return false;
    }

    /**
     * Similitud de Jaccard entre dos cadenas basada en palabras.
     */
    public static function jaccard_similarity($a, $b) {
        $words_a = array_unique(preg_split('/\s+/', strtolower(strip_tags($a))));
        $words_b = array_unique(preg_split('/\s+/', strtolower(strip_tags($b))));
        $intersection = count(array_intersect($words_a, $words_b));
        $union        = count(array_unique(array_merge($words_a, $words_b)));
        return $union > 0 ? $intersection / $union : 0;
    }

    /**
     * Calcula hash de contenido para almacenar al importar.
     */
    public static function content_hash($content) {
        return md5(preg_replace('/\s+/', ' ', strtolower(strip_tags($content))));
    }
}
