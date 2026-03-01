<?php
if (!defined('ABSPATH')) exit;

class PRN_Admin_Page {

    public static function add_menu() {
        add_menu_page(
            'Punto Río Negro AI',
            'Punto Río Negro AI',
            'manage_options',
            PRN_SLUG,
            [__CLASS__, 'render_main_page'],
            'dashicons-welcome-write-blog',
            30
        );
        add_submenu_page(PRN_SLUG, 'Configuración', 'Configuración', 'manage_options', PRN_SLUG . '-settings', [__CLASS__, 'render_settings_page']);
    }

    public static function register_settings() {
        foreach ([
            'prn_gemini_api_key', 'prn_default_prompt', 'prn_auto_publish',
            'prn_posts_per_run',  'prn_gemini_model',   'prn_temperature',
            'prn_min_words',      'prn_max_words',       'prn_tone',
            'prn_generate_images','prn_image_prompt_template',
        ] as $opt) register_setting('prn_settings', $opt);
    }

    public static function render_main_page() {
        global $wpdb;
        $feeds    = $wpdb->get_results("SELECT f.*, c.name as cat_name FROM {$wpdb->prefix}prn_feeds f LEFT JOIN {$wpdb->prefix}term_taxonomy tt ON f.category_id = tt.term_id LEFT JOIN {$wpdb->prefix}terms c ON tt.term_id = c.term_id ORDER BY f.id DESC");
        // Categorías del portal Punto Río Negro
        $prn_cats = [
            'Actualidad'      => 'actualidad',
            'Clima'           => 'clima',
            'Cultura'         => 'cultura',
            'Deportes'        => 'deportes',
            'Economía'        => 'economia',
            'Energía'         => 'energia',
            'Opinión'         => 'opinion',
            'Policiales'      => 'policiales',
            'Política'        => 'politica',
            'Sociedad'        => 'sociedad',
            'Tecnología'      => 'tecnologia',
            'Turismo'         => 'turismo',
            'Últimas noticias'=> 'ultimas-noticias',
            'Portada'         => 'portada',
            'Región'          => 'region',
            'Alto Valle'      => 'alto-valle',
            'Bariloche'       => 'bariloche',
            'Cipolletti'      => 'cipolletti',
            'Neuquén'         => 'neuquen',
            'Roca'            => 'roca',
            'Viedma'          => 'viedma',
        ];
        $cats     = get_categories(['hide_empty' => false]);
        $pending  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}prn_imported WHERE rewritten=0 AND post_id IS NOT NULL");
        $total    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}prn_imported");
        $rewritten= (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}prn_imported WHERE rewritten=1");
        $ai_img   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}postmeta WHERE meta_key='_prn_ai_image'");
        ?>
        <div class="wrap prn-wrap">
            <div class="prn-header">
                <h1>📰 Punto Río Negro AI</h1>
                <p class="prn-subtitle">Importador RSS + Reescritor IA + Generador de imágenes | Google Gemini (gratuito)</p>
            </div>

            <div class="prn-stats">
                <div class="prn-stat"><span class="prn-stat-num"><?= $total ?></span><span class="prn-stat-label">Importados</span></div>
                <div class="prn-stat green"><span class="prn-stat-num"><?= $rewritten ?></span><span class="prn-stat-label">Reescritos</span></div>
                <div class="prn-stat orange"><span class="prn-stat-num"><?= $pending ?></span><span class="prn-stat-label">Pendientes</span></div>
                <div class="prn-stat purple"><span class="prn-stat-num"><?= $ai_img ?></span><span class="prn-stat-label">Imágenes IA</span></div>
                <div class="prn-stat blue"><span class="prn-stat-num"><?= count($feeds) ?></span><span class="prn-stat-label">Feeds</span></div>
            </div>

            <div class="prn-actions">
                <button class="button button-primary prn-btn" id="prn-import-now">⬇️ Importar ahora</button>
                <button class="button prn-btn" id="prn-rewrite-now">✍️ Reescribir + Generar imágenes</button>
                <span id="prn-action-result"></span>
            </div>

            <div class="prn-card">
                <h2>➕ Agregar Feed RSS</h2>
                <table class="form-table">
                    <tr><th>Nombre del medio</th><td><input type="text" id="new-feed-name" class="regular-text" placeholder="ej: Río Negro Online"></td></tr>
                    <tr><th>URL del Feed RSS</th><td><input type="url" id="new-feed-url" class="regular-text" placeholder="https://rionegro.com.ar/feed/"></td></tr>
                    <tr>
                        <th>Categoría WordPress</th>
                        <td>
                            <select id="new-feed-category">
                                <option value="0">— Sin categoría —</option>
                                <?php
                                // Usar categorías reales del portal si existen, sino fallback a WordPress
                                $has_prn_cats = false;
                                foreach ($cats as $cat) {
                                    if (in_array($cat->slug, array_values($prn_cats))) {
                                        $has_prn_cats = true; break;
                                    }
                                }
                                if ($has_prn_cats) {
                                    // Mostrar en el orden del portal
                                    foreach ($prn_cats as $nombre => $slug) {
                                        $cat_obj = get_category_by_slug($slug);
                                        if ($cat_obj) {
                                            $indent = in_array($slug, ['clima','cultura','deportes','economia','energia','opinion','policiales','politica','sociedad','tecnologia','turismo','ultimas-noticias','portada','alto-valle','bariloche','cipolletti','neuquen','roca','viedma']) ? '&nbsp;&nbsp;— ' : '';
                                            echo '<option value="' . $cat_obj->term_id . '">' . $indent . esc_html($nombre) . '</option>';
                                        }
                                    }
                                } else {
                                    foreach ($cats as $cat): ?>
                                        <option value="<?= $cat->term_id ?>"><?= esc_html($cat->name) ?></option>
                                    <?php endforeach;
                                }
                                ?>
                            </select>
                            <p class="description">Categorías del portal Punto Río Negro. Si no aparecen, verificá que el plugin esté activado en tu WordPress.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Prompt personalizado <small>(opcional)</small></th>
                        <td>
                            <textarea id="new-feed-prompt" rows="4" class="large-text" placeholder="Vacío = usa el prompt global. Podés usar {min_words}, {max_words}, {tone}."></textarea>
                        </td>
                    </tr>
                </table>
                <button class="button button-primary" id="prn-add-feed">Agregar Feed</button>
            </div>

            <div class="prn-card">
                <h2>📡 Feeds configurados</h2>
                <?php if (empty($feeds)): ?>
                    <p>No hay feeds. <strong>Feeds sugeridos para Río Negro / Neuquén:</strong></p>
                    <ul>
                        <li>rionegro.com.ar — <code>https://rionegro.com.ar/feed/</code></li>
                        <li>La Mañana Neuquén — <code>https://www.lmneuquen.com/feed/</code></li>
                        <li>Diario Andino — <code>https://diarioandino.com.ar/feed/</code></li>
                        <li>El Cordillerano — <code>https://elcordillerano.com.ar/feed/</code></li>
                        <li>ADN Sur — <code>https://www.adnsur.com.ar/feed/</code></li>
                    </ul>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead><tr><th>Nombre</th><th>URL</th><th>Categoría</th><th>Último fetch</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($feeds as $f): ?>
                        <tr id="feed-row-<?= $f->id ?>">
                            <td><strong><?= esc_html($f->feed_name) ?></strong></td>
                            <td><a href="<?= esc_url($f->feed_url) ?>" target="_blank"><?= esc_url($f->feed_url) ?></a></td>
                            <td><?= $f->cat_name ?: '—' ?></td>
                            <td><?= $f->last_fetch ? human_time_diff(strtotime($f->last_fetch)) . ' atrás' : 'Nunca' ?></td>
                            <td><span class="prn-badge <?= $f->active ? 'active' : 'inactive' ?>"><?= $f->active ? 'Activo' : 'Inactivo' ?></span></td>
                            <td>
                                <button class="button prn-toggle-feed" data-id="<?= $f->id ?>"><?= $f->active ? 'Pausar' : 'Activar' ?></button>
                                <button class="button button-link-delete prn-delete-feed" data-id="<?= $f->id ?>">Eliminar</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public static function render_settings_page() {
        $api_key   = get_option('prn_gemini_api_key', '');
        $prompt    = get_option('prn_default_prompt', PRN_Gemini_Rewriter::get_default_prompt());
        $auto_pub  = get_option('prn_auto_publish', '0');
        $per_run   = get_option('prn_posts_per_run', '5');
        $model     = get_option('prn_gemini_model', 'gemini-1.5-flash');
        $temp      = get_option('prn_temperature', '0.7');
        $min_w     = get_option('prn_min_words', '300');
        $max_w     = get_option('prn_max_words', '500');
        $tone      = get_option('prn_tone', 'profesional periodístico');
        $gen_img   = get_option('prn_generate_images', '1');
        $img_tpl   = get_option('prn_image_prompt_template', 'Professional news photograph for Patagonia Argentina. Subject: {keywords}. Photojournalism style, sharp focus, natural lighting. NO text, NO watermarks.');
        ?>
        <div class="wrap prn-wrap">
            <div class="prn-header"><h1>⚙️ Punto Río Negro AI — Configuración</h1></div>

            <form method="post" action="options.php">
                <?php settings_fields('prn_settings'); ?>

                <!-- API KEY -->
                <div class="prn-card">
                    <h2>🔑 API de Google Gemini</h2>
                    <p>Obtén tu API Key gratis en: <a href="https://aistudio.google.com/app/apikey" target="_blank">aistudio.google.com/app/apikey</a></p>
                    <table class="form-table">
                        <tr>
                            <th>Gemini API Key</th>
                            <td>
                                <input type="password" name="prn_gemini_api_key" value="<?= esc_attr($api_key) ?>" class="regular-text" />
                                <span style="margin-left:8px;"><?= $api_key ? '<span style="color:green">✅ Configurada</span>' : '<span style="color:red">⚠️ Sin configurar</span>' ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- MODELO Y PARÁMETROS IA -->
                <div class="prn-card">
                    <h2>🤖 Opciones avanzadas de IA</h2>
                    <table class="form-table">
                        <tr>
                            <th>Modelo Gemini</th>
                            <td>
                                <select name="prn_gemini_model">
                                    <?php foreach (PRN_Gemini_Rewriter::MODELS as $k => $label): ?>
                                        <option value="<?= $k ?>" <?php selected($model, $k) ?>><?= esc_html($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Temperatura (creatividad)</th>
                            <td>
                                <input type="range" name="prn_temperature" min="0.1" max="1.0" step="0.1" value="<?= esc_attr($temp) ?>" oninput="document.getElementById('prn-temp-val').textContent=this.value" />
                                <strong id="prn-temp-val"><?= esc_attr($temp) ?></strong>
                                <p class="description">0.1 = muy conservador / 1.0 = muy creativo. Recomendado: 0.7</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Cantidad mínima de palabras</th>
                            <td><input type="number" name="prn_min_words" value="<?= esc_attr($min_w) ?>" min="100" max="2000" style="width:90px;" /></td>
                        </tr>
                        <tr>
                            <th>Cantidad máxima de palabras</th>
                            <td><input type="number" name="prn_max_words" value="<?= esc_attr($max_w) ?>" min="200" max="3000" style="width:90px;" /></td>
                        </tr>
                        <tr>
                            <th>Tono de escritura</th>
                            <td>
                                <select name="prn_tone">
                                    <option value="profesional periodístico" <?php selected($tone, 'profesional periodístico') ?>>Profesional periodístico (recomendado)</option>
                                    <option value="formal y serio" <?php selected($tone, 'formal y serio') ?>>Formal y serio</option>
                                    <option value="neutro e informativo" <?php selected($tone, 'neutro e informativo') ?>>Neutro e informativo</option>
                                    <option value="cercano y accesible" <?php selected($tone, 'cercano y accesible') ?>>Cercano y accesible</option>
                                    <option value="idéntico al estilo de rionegro.com.ar" <?php selected($tone, 'idéntico al estilo de rionegro.com.ar') ?>>Estilo rionegro.com.ar</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th>Posts por ejecución</th>
                            <td>
                                <input type="number" name="prn_posts_per_run" value="<?= esc_attr($per_run) ?>" min="1" max="20" style="width:80px;" />
                                <p class="description">Cuántos posts reescribir por ejecución del cron (recomendado: 5)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Auto-publicar al reescribir</th>
                            <td>
                                <label><input type="checkbox" name="prn_auto_publish" value="1" <?php checked($auto_pub, '1') ?> /> Publicar automáticamente (si no, queda en borrador)</label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- IMÁGENES IA -->
                <div class="prn-card">
                    <h2>🖼️ Generación de imágenes con IA</h2>
                    <p>Usa Gemini Imagen 3 (gratuito) para generar imágenes periodísticas cuando el RSS no tenga imagen propia.</p>
                    <table class="form-table">
                        <tr>
                            <th>Activar generación de imágenes</th>
                            <td><label><input type="checkbox" name="prn_generate_images" value="1" <?php checked($gen_img, '1') ?> /> Generar imagen IA para posts sin imagen</label></td>
                        </tr>
                        <tr>
                            <th>Prompt de imagen <small>(en inglés)</small></th>
                            <td>
                                <textarea name="prn_image_prompt_template" rows="4" class="large-text"><?= esc_textarea($img_tpl) ?></textarea>
                                <p class="description">Usá <code>{keywords}</code> donde quieras que se inserten las palabras clave del título.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- PROMPT GLOBAL -->
                <div class="prn-card">
                    <h2>📝 Prompt global de reescritura</h2>
                    <p>Variables disponibles: <code>{min_words}</code>, <code>{max_words}</code>, <code>{tone}</code></p>
                    <textarea name="prn_default_prompt" rows="18" class="large-text" style="font-family:monospace;font-size:12px;"><?= esc_textarea($prompt) ?></textarea>
                </div>

                <?php submit_button('Guardar configuración'); ?>
            </form>
        </div>
        <?php
    }
}
