<?php
if (!defined('ABSPATH')) exit;

class RAR_Admin_Page {

    public static function add_menu() {
        add_menu_page(
            'RSS AI Rewriter',
            'RSS AI Rewriter',
            'manage_options',
            'rss-ai-rewriter',
            array(__CLASS__, 'render_main_page'),
            'dashicons-rss',
            30
        );
        add_submenu_page(
            'rss-ai-rewriter',
            'Configuración',
            'Configuración',
            'manage_options',
            'rss-ai-rewriter-settings',
            array(__CLASS__, 'render_settings_page')
        );
    }

    public static function register_settings() {
        register_setting('rar_settings', 'rar_gemini_api_key');
        register_setting('rar_settings', 'rar_default_prompt');
        register_setting('rar_settings', 'rar_auto_publish');
        register_setting('rar_settings', 'rar_posts_per_run');
    }

    public static function render_main_page() {
        global $wpdb;
        $feeds = $wpdb->get_results("SELECT f.*, c.name as cat_name FROM {$wpdb->prefix}rar_feeds f LEFT JOIN {$wpdb->prefix}term_taxonomy tt ON f.category_id = tt.term_id LEFT JOIN {$wpdb->prefix}terms c ON tt.term_id = c.term_id ORDER BY f.id DESC");
        $categories = get_categories(array('hide_empty' => false));
        $pending_rewrite = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rar_imported WHERE rewritten=0 AND post_id IS NOT NULL");
        $total_imported  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rar_imported");
        $total_rewritten = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}rar_imported WHERE rewritten=1");
        ?>
        <div class="wrap rar-wrap">
            <h1>📰 RSS AI Rewriter</h1>
            <p class="rar-subtitle">Importa noticias desde RSS y reescríbelas automáticamente con Google Gemini</p>

            <!-- Stats -->
            <div class="rar-stats">
                <div class="rar-stat-box">
                    <span class="rar-stat-num"><?php echo $total_imported; ?></span>
                    <span class="rar-stat-label">Total importados</span>
                </div>
                <div class="rar-stat-box green">
                    <span class="rar-stat-num"><?php echo $total_rewritten; ?></span>
                    <span class="rar-stat-label">Reescritos</span>
                </div>
                <div class="rar-stat-box orange">
                    <span class="rar-stat-num"><?php echo $pending_rewrite; ?></span>
                    <span class="rar-stat-label">Pendientes reescritura</span>
                </div>
                <div class="rar-stat-box blue">
                    <span class="rar-stat-num"><?php echo count($feeds); ?></span>
                    <span class="rar-stat-label">Feeds activos</span>
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="rar-actions">
                <button class="button button-primary rar-btn" id="rar-import-now">⬇️ Importar ahora</button>
                <button class="button rar-btn" id="rar-rewrite-now">✍️ Reescribir pendientes</button>
                <span id="rar-action-result" style="margin-left:10px;color:#2271b1;font-weight:bold;"></span>
            </div>

            <!-- Agregar Feed -->
            <div class="rar-card">
                <h2>➕ Agregar Feed RSS</h2>
                <table class="form-table">
                    <tr>
                        <th>Nombre del medio</th>
                        <td><input type="text" id="new-feed-name" class="regular-text" placeholder="ej: Río Negro Online"></td>
                    </tr>
                    <tr>
                        <th>URL del Feed RSS</th>
                        <td><input type="url" id="new-feed-url" class="regular-text" placeholder="https://ejemplo.com/feed/"></td>
                    </tr>
                    <tr>
                        <th>Categoría WordPress</th>
                        <td>
                            <select id="new-feed-category">
                                <option value="0">— Sin categoría —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat->term_id; ?>"><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Prompt personalizado <small>(opcional)</small></th>
                        <td>
                            <textarea id="new-feed-prompt" rows="4" class="large-text" placeholder="Deja vacío para usar el prompt global..."></textarea>
                            <p class="description">Si dejas esto vacío, se usará el prompt global de la página de Configuración.</p>
                        </td>
                    </tr>
                </table>
                <button class="button button-primary" id="rar-add-feed">Agregar Feed</button>
            </div>

            <!-- Lista de Feeds -->
            <div class="rar-card">
                <h2>📡 Feeds configurados</h2>
                <?php if (empty($feeds)): ?>
                    <p>No hay feeds configurados todavía. ¡Agrega el primero arriba!</p>
                    <p><strong>Feeds sugeridos para Río Negro / Neuquén:</strong></p>
                    <ul>
                        <li>rionegro.com.ar — <code>https://rionegro.com.ar/feed/</code></li>
                        <li>La Mañana Neuquén — <code>https://www.lmneuquen.com/feed/</code></li>
                        <li>Diario Andino — <code>https://diarioandino.com.ar/feed/</code></li>
                        <li>El Cordillerano — <code>https://elcordillerano.com.ar/feed/</code></li>
                        <li>ADN Sur — <code>https://www.adnsur.com.ar/feed/</code></li>
                    </ul>
                <?php else: ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>URL del Feed</th>
                                <th>Categoría</th>
                                <th>Último fetch</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feeds as $feed): ?>
                            <tr id="feed-row-<?php echo $feed->id; ?>">
                                <td><strong><?php echo esc_html($feed->feed_name); ?></strong></td>
                                <td><a href="<?php echo esc_url($feed->feed_url); ?>" target="_blank"><?php echo esc_url($feed->feed_url); ?></a></td>
                                <td><?php echo $feed->cat_name ?: '—'; ?></td>
                                <td><?php echo $feed->last_fetch ? human_time_diff(strtotime($feed->last_fetch)) . ' atrás' : 'Nunca'; ?></td>
                                <td>
                                    <span class="rar-badge <?php echo $feed->active ? 'active' : 'inactive'; ?>">
                                        <?php echo $feed->active ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="button rar-toggle-feed" data-id="<?php echo $feed->id; ?>">
                                        <?php echo $feed->active ? 'Pausar' : 'Activar'; ?>
                                    </button>
                                    <button class="button button-link-delete rar-delete-feed" data-id="<?php echo $feed->id; ?>">Eliminar</button>
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
        $api_key    = get_option('rar_gemini_api_key', '');
        $prompt     = get_option('rar_default_prompt', RAR_Gemini_Rewriter::get_default_prompt());
        $auto_pub   = get_option('rar_auto_publish', '0');
        $per_run    = get_option('rar_posts_per_run', '5');
        ?>
        <div class="wrap rar-wrap">
            <h1>⚙️ Configuración RSS AI Rewriter</h1>
            <form method="post" action="options.php">
                <?php settings_fields('rar_settings'); ?>
                <div class="rar-card">
                    <h2>🤖 API de Google Gemini (gratuita)</h2>
                    <p>Obtén tu API Key gratis en: <a href="https://aistudio.google.com/app/apikey" target="_blank">aistudio.google.com/app/apikey</a></p>
                    <table class="form-table">
                        <tr>
                            <th>Gemini API Key</th>
                            <td>
                                <input type="password" name="rar_gemini_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                                <?php if ($api_key): ?>
                                    <span style="color:green;margin-left:8px;">✅ Configurada</span>
                                <?php else: ?>
                                    <span style="color:red;margin-left:8px;">⚠️ Sin configurar</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Posts por ejecución</th>
                            <td>
                                <input type="number" name="rar_posts_per_run" value="<?php echo esc_attr($per_run); ?>" min="1" max="20" style="width:80px;" />
                                <p class="description">Cuántos posts reescribir por ejecución del cron (recomendado: 5)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Auto-publicar al reescribir</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="rar_auto_publish" value="1" <?php checked($auto_pub, '1'); ?> />
                                    Publicar automáticamente una vez reescrito (si no, permanece en borrador)
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="rar-card">
                    <h2>📝 Prompt Global de Reescritura</h2>
                    <p>Este prompt se aplica a todos los feeds que no tengan un prompt personalizado.</p>
                    <textarea name="rar_default_prompt" rows="15" class="large-text" style="font-family:monospace;"><?php echo esc_textarea($prompt); ?></textarea>
                </div>
                <?php submit_button('Guardar configuración'); ?>
            </form>
        </div>
        <?php
    }
}
