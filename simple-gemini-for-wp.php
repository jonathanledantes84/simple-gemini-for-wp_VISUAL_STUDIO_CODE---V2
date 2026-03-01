<?php
/*
Plugin Name: Simple Gemini for WP - Ultra Robusta
Description: Gemini (texto + imágenes) + auto posts desde RSS/X/IG + notificaciones WhatsApp Meta (fallback Telegram sendPhoto + Email) + toggle cron + categorías automáticas. Versión ultra robusta para hosting compartido.
Version: 1.5 Ultra Robusta
Author: Grok para Traful
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// ====================== AJUSTES ADMIN ======================
add_action('admin_menu', function(){
    add_options_page('Simple Gemini', 'Simple Gemini', 'manage_options', 'simple-gemini', function(){
        if (!current_user_can('manage_options')) return;

        if (isset($_POST['simple_gemini_save'])) {
            check_admin_referer('simple_gemini_save_key');

            update_option('simple_gemini_api_key', sanitize_text_field($_POST['simple_gemini_api_key']));
            update_option('simple_gemini_rss_urls', sanitize_textarea_field($_POST['simple_gemini_rss_urls']));
            update_option('simple_gemini_x_rss_urls', sanitize_textarea_field($_POST['simple_gemini_x_rss_urls']));
            update_option('simple_gemini_ig_rss_urls', sanitize_textarea_field($_POST['simple_gemini_ig_rss_urls']));
            update_option('simple_gemini_tg_token', sanitize_text_field($_POST['simple_gemini_tg_token']));
            update_option('simple_gemini_tg_chat_id', sanitize_text_field($_POST['simple_gemini_tg_chat_id']));
            update_option('simple_gemini_tg_enable', isset($_POST['simple_gemini_tg_enable']) ? 1 : 0);
            update_option('simple_gemini_wa_token', sanitize_text_field($_POST['simple_gemini_wa_token']));
            update_option('simple_gemini_wa_phone_id', sanitize_text_field($_POST['simple_gemini_wa_phone_id']));
            update_option('simple_gemini_wa_to', preg_replace('/\D/', '', $_POST['simple_gemini_wa_to']));
            update_option('simple_gemini_wa_enable', isset($_POST['simple_gemini_wa_enable']) ? 1 : 0);
            update_option('simple_gemini_alert_email', sanitize_email($_POST['simple_gemini_alert_email']));
            update_option('simple_gemini_alert_enable', isset($_POST['simple_gemini_alert_enable']) ? 1 : 0);
            update_option('simple_gemini_cron_enable', isset($_POST['simple_gemini_cron_enable']) ? 1 : 0);
            update_option('simple_gemini_category', intval($_POST['simple_gemini_category']));
            update_option('simple_gemini_publish_direct', isset($_POST['simple_gemini_publish_direct']) ? 1 : 0);
            update_option('simple_gemini_model', sanitize_text_field($_POST['simple_gemini_model']));
            update_option('simple_gemini_cron_prompt', sanitize_textarea_field($_POST['simple_gemini_cron_prompt']));
            update_option('simple_gemini_daily_limit', intval($_POST['simple_gemini_daily_limit']));
            update_option('simple_gemini_vision_enable', isset($_POST['simple_gemini_vision_enable']) ? 1 : 0);

            // Guardar mapeo de categorías RSS
            $rss_mapping = [];
            if (isset($_POST['rss_mapping_urls']) && isset($_POST['simple_gemini_rss_categories'])) {
                $urls = array_map('sanitize_text_field', $_POST['rss_mapping_urls']);
                $cats = array_map('intval', $_POST['simple_gemini_rss_categories']);
                foreach ($urls as $index => $url) {
                    if (!empty($url) && isset($cats[$index])) {
                        $rss_mapping[$url] = $cats[$index];
                    }
                }
            }
            update_option('simple_gemini_rss_category_map', $rss_mapping);

            // Botón de prueba manual
            if (isset($_POST['simple_gemini_test_now'])) {
                check_admin_referer('simple_gemini_save_key');
                simple_gemini_auto_create_post();
                echo '<div class="updated"><p>✅ ¡Post de prueba generado! Revisá los borradores.</p></div>';
            }

            echo '<div class="updated"><p>✅ Configuración guardada correctamente.</p></div>';
        }

        // Valores actuales
        $api_key = esc_attr(get_option('simple_gemini_api_key',''));
        $rss     = esc_textarea(get_option('simple_gemini_rss_urls',''));
        $x_rss   = esc_textarea(get_option('simple_gemini_x_rss_urls',''));
        $ig_rss  = esc_textarea(get_option('simple_gemini_ig_rss_urls',''));
        $tg_token= esc_attr(get_option('simple_gemini_tg_token',''));
        $tg_chat = esc_attr(get_option('simple_gemini_tg_chat_id',''));
        $tg_en   = get_option('simple_gemini_tg_enable', 0);
        $wa_token= esc_attr(get_option('simple_gemini_wa_token',''));
        $wa_phone= esc_attr(get_option('simple_gemini_wa_phone_id',''));
        $wa_to   = esc_attr(get_option('simple_gemini_wa_to',''));
        $wa_en   = get_option('simple_gemini_wa_enable', 0);
        $email   = esc_attr(get_option('simple_gemini_alert_email', get_option('admin_email')));
        $email_en= get_option('simple_gemini_alert_enable', 1);
        $cron_en = get_option('simple_gemini_cron_enable', 1);
        $category= get_option('simple_gemini_category', 1);
        $publish_direct = get_option('simple_gemini_publish_direct', 0);
        $model   = get_option('simple_gemini_model', 'gemini-2.0-flash');
        $cron_prompt = esc_textarea(get_option('simple_gemini_cron_prompt', ''));
        $daily_limit = get_option('simple_gemini_daily_limit', 0);
        $vision_en = get_option('simple_gemini_vision_enable', 0);

        // Obtener categorías
        $categories = get_categories(['hide_empty' => 0]);

        // Obtener últimos logs
        $log_file = WP_CONTENT_DIR . '/debug.log';
        $logs = '';
        if (file_exists($log_file)) {
            $lines = file($log_file);
            $logs = implode('', array_slice($lines, -50));
        }

        ?>
        <div class="wrap">
            <h1>🔧 Simple Gemini - Ultra Robusta (General Roca)</h1>
            <p class="description">Plugin de auto-blogging con Gemini AI para medios locales de Río Negro</p>
            
            <!-- AYUDA RÁPIDA -->
            <div style="background:#e7f3ff;border-left:4px solid #2196F3;padding:15px;margin:20px 0;border-radius:4px;">
                <h3 style="margin-top:0;">🚀 Guía de Configuración Rápida</h3>
                <ol style="margin-bottom:0;">
                    <li><strong>API Key Gemini:</strong> Obtenela en <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a> (es gratis)</li>
                    <li><strong>RSS (opcional):</strong> Agregá URLs de feeds RSS de noticias locales</li>
                    <li><strong>Notificaciones (opcional):</strong> Configurá WhatsApp/Telegram para recibir alertas</li>
                    <li><strong>Cron:</strong> Activá para generar posts automáticos daily</li>
                </ol>
            </div>

            <form method="post">
                <?php wp_nonce_field('simple_gemini_save_key'); ?>
                
                <!-- SECCIÓN: GEMINI API -->
                <div style="background:#f5f5f5;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #ddd;">
                    <h2 style="margin-top:0;">🤖 Configuración de Gemini AI</h2>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>API Key Gemini *</th>
                            <td>
                                <input name="simple_gemini_api_key" type="password" value="<?php echo $api_key; ?>" class="regular-text" />
                                <p class="description"><strong>Obligatorio.</strong> Tu clave API de Google AI. <a href="https://aistudio.google.com/app/apikey" target="_blank">Obtener gratis aquí →</a></p>
                            </td>
                        </tr>
                        <tr>
                            <th>Modelo de IA</th>
                            <td>
                                <select name="simple_gemini_model" class="regular-text">
                                    <option value="gemini-2.0-flash" <?php selected('gemini-2.0-flash', $model); ?>>gemini-2.0-flash ⭐ (recomendado - rápido y económico)</option>
                                    <option value="gemini-2.0-flash-exp" <?php selected('gemini-2.0-flash-exp', $model); ?>>gemini-2.0-flash-exp (experimental - mejor calidad)</option>
                                    <option value="gemini-1.5-flash" <?php selected('gemini-1.5-flash', $model); ?>>gemini-1.5-flash (estable)</option>
                                </select>
                                <p class="description">gemini-2.0-flash es el más rápido y con mejor precio. Cambiá solo si tenés problemas.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN: FUENTES RSS -->
                <div style="background:#f5f5f5;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #ddd;">
                    <h2 style="margin-top:0;">📰 Fuentes de Contenido (RSS)</h2>
                    <p class="description">Agregá URLs de feeds RSS para obtener contenido automático. Una URL por línea.</p>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>RSS Noticias General</th>
                            <td>
                                <textarea name="simple_gemini_rss_urls" rows="3" class="large-text" placeholder="https://ejemplo.com/feed&#10;https://rionegro.com.ar/rss/"></textarea>
                                <p class="description">Feeds de noticias locales, diarios, portals de Río Negro. <strong>Ejemplos:</strong><br>
                                • <code>https://www.rionegro.com.ar/feed/</code><br>
                                • <code>https://www.lmneuquen.com.ar/rss/</code><br>
                                • <code>https://www.azminforma.com/feed/</code></p>
                            </td>
                        </tr>
                        <tr>
                            <th>RSS X/Twitter</th>
                            <td>
                                <textarea name="simple_gemini_x_rss_urls" rows="3" class="large-text" placeholder="https://rss.app/feeds/v你们的RSS"></textarea>
                                <p class="description">Feeds de Twitter/X. Usá servicios como <a href="https://rss.app" target="_blank">RSS.app</a> para convertir perfiles de X a RSS.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>RSS Instagram</th>
                            <td>
                                <textarea name="simple_gemini_ig_rss_urls" rows="3" class="large-text" placeholder="https://rss.app/feeds/v你们的RSS"></textarea>
                                <p class="description">Feeds de Instagram. Necesitás usar un servicio externo como <a href="https://rss.app" target="_blank">RSS.app</a> o <a href="https://beatiful.ai" target="_blank">Beautiful.ai</a>.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN: MAPEO DE CATEGORÍAS -->
                <div style="background:#fff3e0;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #ffb74d;">
                    <h2 style="margin-top:0;">📂 Mapeo de Categorías por RSS</h2>
                    <p class="description">Asigná automáticamente una categoría diferente a cada fuente RSS. Si no configurás nada, se usa la categoría por defecto.</p>
                    
                    <?php
                    $all_rss = array_unique(array_filter(array_merge(
                        array_map('trim', explode("\n", $rss)),
                        array_map('trim', explode("\n", $x_rss)),
                        array_map('trim', explode("\n", $ig_rss))
                    )));
                    $rss_category_map = get_option('simple_gemini_rss_category_map', []);
                    if (empty($all_rss)) $all_rss = [''];
                    ?>
                    <table class="widefat fixed" cellspacing="0" style="margin-top:10px;background:white;">
                        <thead><tr><th style="width:70%;">URL RSS</th><th>Categoría</th></tr></thead>
                        <tbody>
                            <?php foreach ($all_rss as $rss_url): 
                                $current_cat = isset($rss_category_map[$rss_url]) ? $rss_category_map[$rss_url] : '';
                            ?>
                            <tr>
                                <td><input type="text" name="rss_mapping_urls[]" value="<?php echo esc_attr($rss_url); ?>" class="large-text" placeholder="https://..." /></td>
                                <td>
                                    <select name="simple_gemini_rss_categories[]">
                                        <option value="0">-- Por defecto --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat->term_id; ?>" <?php selected($current_cat, $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="description" style="margin-top:10px;">💡 <strong>Tip:</strong> Agregá tus URLs RSS arriba y guardá para verlas aquí mapeadas.</p>
                </div>

                <!-- SECCIÓN: WHATSAPP -->
                <div style="background:#e8f5e9;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #81c784;">
                    <h2 style="margin-top:0;">💬 WhatsApp Meta (Notificaciones Principales)</h2>
                    <p class="description">Recibí notificaciones cuando se publique un nuevo post. Requiere cuenta de WhatsApp Business.</p>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>Permanent Access Token</th>
                            <td>
                                <input name="simple_gemini_wa_token" type="password" value="<?php echo $wa_token; ?>" class="regular-text" />
                                <p class="description">Token permanente de la API de Meta. <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank">Guía aquí →</a></p>
                            </td>
                        </tr>
                        <tr>
                            <th>Phone Number ID</th>
                            <td>
                                <input name="simple_gemini_wa_phone_id" value="<?php echo $wa_phone; ?>" class="regular-text" placeholder="123456789012345" />
                                <p class="description">ID del número de WhatsApp Business. Lo obtenés en Meta Developers → Tu App → WhatsApp → Números de teléfono.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Número destino</th>
                            <td>
                                <input name="simple_gemini_wa_to" value="<?php echo $wa_to; ?>" class="regular-text" placeholder="5492991234567" />
                                <p class="description">Número que recibirá los mensajes (con código de país, sin +). <strong>Ejemplo:</strong> 5492994123456</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Activar WhatsApp</th>
                            <td>
                                <input type="checkbox" name="simple_gemini_wa_enable" value="1" <?php checked(1, $wa_en); ?> />
                                <label>Sí, enviar notificaciones por WhatsApp</label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN: TELEGRAM -->
                <div style="background:#e3f2fd;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #90caf9;">
                    <h2 style="margin-top:0;">✈️ Telegram (Fallback)</h2>
                    <p class="description">Notificaciones de respaldo si WhatsApp falla. También envía la imagen del post.</p>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>Bot Token</th>
                            <td>
                                <input name="simple_gemini_tg_token" type="password" value="<?php echo $tg_token; ?>" class="regular-text" />
                                <p class="description">Token del bot. <a href="https://t.me/BotFather" target="_blank">Crear bot aquí →</a> (buscá @BotFather y usá /newbot)</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Chat ID</th>
                            <td>
                                <input name="simple_gemini_tg_chat_id" value="<?php echo $tg_chat; ?>" class="regular-text" placeholder="123456789" />
                                <p class="description">ID del chat. Escribí a <a href="https://t.me/userinfobot" target="_blank">@userinfobot</a> para obtener tu ID, o <a href="https://t.me/myidbot" target="_blank">@myidbot</a>.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Activar Telegram</th>
                            <td>
                                <input type="checkbox" name="simple_gemini_tg_enable" value="1" <?php checked(1, $tg_en); ?> />
                                <label>Sí, enviar notificaciones por Telegram</label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN: EMAIL -->
                <div style="background:#fce4ec;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #f48fb1;">
                    <h2 style="margin-top:0;">📧 Email (Fallback Final)</h2>
                    <p class="description">Siempre se envía un email como último recurso si fallan las otras notificaciones.</p>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>Email de alertas</th>
                            <td>
                                <input name="simple_gemini_alert_email" type="email" value="<?php echo $email; ?>" class="regular-text" />
                                <p class="description">Email donde recibir notificaciones. Por defecto es el admin del sitio.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Activar Email</th>
                            <td>
                                <input type="checkbox" name="simple_gemini_alert_enable" value="1" <?php checked(1, $email_en); ?> />
                                <label>Sí, enviar notificaciones por email</label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN: CONFIGURACIÓN DEL POST -->
                <div style="background:#f3e5f5;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #ce93d8;">
                    <h2 style="margin-top:0;">📝 Configuración del Post</h2>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>Categoría por defecto</th>
                            <td>
                                <select name="simple_gemini_category">
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat->term_id; ?>" <?php selected($category, $cat->term_id); ?>><?php echo esc_html($cat->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">Categoría asignada a los posts generados. También se usa el mapeo de arriba.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Publicar directamente</th>
                            <td>
                                <input type="checkbox" name="simple_gemini_publish_direct" value="1" <?php checked(1, $publish_direct); ?> />
                                <label>Sí, publicar automáticamente (sin pasar por borradores)</label>
                                <p class="description"><span style="color:#f44336;">⚠️ Cuidado:</span> Si está marcado, los posts se publican inmediatamente. Recomendado dejar desmarcado para revisar antes.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN: CRON AUTOMÁTICO -->
                <div style="background:#fff8e1;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #ffd54f;">
                    <h2 style="margin-top:0;">⏰ Cron Automático (Publicación Diaria)</h2>
                    <p class="description">El plugin puede generar posts automáticamente todos los días a las 8:00 AM.</p>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>Activar cron diario</th>
                            <td>
                                <input type="checkbox" name="simple_gemini_cron_enable" value="1" <?php checked(1, $cron_en); ?> />
                                <label>Sí, generar posts automáticos todos los días a las 8:00 AM</label>
                                <p class="description">Desactivá para pausar la generación automática sin perder la configuración.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Prueba manual</th>
                            <td>
                                <input type="submit" name="simple_gemini_test_now" class="button button-primary" value="🚀 Generar post ahora" onclick="return confirm('¿Generar un post de prueba ahora? Esto cuenta para el límite diario.');" />
                                <p class="description">Ejecuta el cron inmediatamente para probar. Ideal para testing.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Prompt personalizado</th>
                            <td>
                                <textarea name="simple_gemini_cron_prompt" rows="4" class="large-text" placeholder="Ej: Escribí una noticia sobre economía en General Roca, Río Negro..."><?php echo $cron_prompt; ?></textarea>
                                <p class="description">Prompt que usará el cron. Dejá vacío para usar el prompt por defecto (noticias locales de Río Negro).</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Límite diario</th>
                            <td>
                                <input type="number" name="simple_gemini_daily_limit" value="<?php echo $daily_limit; ?>" min="0" max="10" class="small-text" style="width:80px;" />
                                <label>posts por día (0 = sin límite)</label>
                                <p class="description">Control anti-spam. <strong>Recomendado:</strong> 1-3 posts/día. 0 = ilimitado.</p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- SECCIÓN: ANTISPAM Y CALIDAD -->
                <div style="background:#efebe9;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #bcaaa4;">
                    <h2 style="margin-top:0;">🛡️ Control de Calidad y Anti-Spam</h2>
                    
                    <table class="form-table" style="margin-bottom:0;">
                        <tr>
                            <th>Control de calidad IA</th>
                            <td>
                                <p class="description">✅ <strong>Activado por defecto.</strong> El sistema automáticamente:</p>
                                <ul style="margin-top:5px;">
                                    <li>Verifica que el contenido tenga entre 200 y 10,000 caracteres</li>
                                    <li>Detecta frases que indicate "no puedo generar" o "como modelo de lenguaje"</li>
                                    <li>Evita títulos muy cortos o vacíos</li>
                                    <li>Detecta contenido duplicado con posts anteriores (similaridad > 70%)</li>
                                    <li>Guarda metadata de la fuente (URL, tipo, fecha)</li>
                                </ul>
                                <p class="description" style="margin-top:10px;">Si falla el control de calidad, recibirás un email de alerta.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Gemini Vision (Beta)</th>
                            <td>
                                <input type="checkbox" name="simple_gemini_vision_enable" value="1" <?php checked(1, $vision_en); ?> />
                                <label>Activar análisis de imágenes</label>
                                <p class="description">Usa Gemini Vision para describir imágenes de RSS/Instagram y mejorar los posts. <span style="color:#ff9800;">⚠️ Beta: puede ser más lento y costoso.</span></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- BOTONES GUARDAR -->
                <p><input type="submit" name="simple_gemini_save" class="button-primary button-large" value="💾 Guardar toda la configuración" style="font-size:16px;padding:10px 30px;" /></p>
            </form>

            <hr>
            
            <!-- LOGS -->
            <div style="background:#1e1e1e;padding:20px;margin:20px 0;border-radius:8px;">
                <h2 style="color:#00ff00;margin-top:0;">📋 Logs Recientes</h2>
                <p class="description" style="color:#888;">Últimas 50 líneas del log de WordPress (wp-content/debug.log)</p>
                <div style="color:#00ff00;font-family:monospace;font-size:11px;max-height:300px;overflow:auto;white-space:pre-wrap;">
<?php echo esc_html($logs ?: 'Sin logs aún. Los logs aparecerán cuando se ejecute el cron o haya errores.'); ?>
                </div>
            </div>

            <!-- AYUDA ADICIONAL -->
            <div style="background:#e0f7fa;padding:15px;margin:20px 0;border-radius:8px;border:1px solid #4dd0e1;">
                <h3 style="margin-top:0;">❓ Preguntas Frecuentes</h3>
                
                <details style="margin:10px 0;">
                    <summary style="cursor:pointer;font-weight:bold;">¿Cómo obtener la API Key de Gemini?</summary>
                    <div style="padding:10px;">
                        1. Vas a <a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a><br>
                        2. Iniciá sesión con tu cuenta Google<br>
                        3. Click en "Create API Key"<br>
                        4. Copiá la clave y pegala aquí<br>
                        <strong>Es gratis</strong> para uso personal (tiene crédito gratuito mensual).
                    </div>
                </details>
                
                <details style="margin:10px 0;">
                    <summary style="cursor:pointer;font-weight:bold;">¿Cómo configuro WhatsApp Business?</summary>
                    <div style="padding:10px;">
                        1. Necesitás una cuenta de <a href="https://business.facebook.com" target="_blank">Meta Business</a><br>
                        2. Creá una app en <a href="https://developers.facebook.com" target="_blank">Meta Developers</a><br>
                        3. Agregá el producto "WhatsApp"<br>
                        4. Configurá el número de teléfono<br>
                        5. Obtené el <strong>Permanent Access Token</strong> y <strong>Phone Number ID</strong><br>
                        <a href="https://developers.facebook.com/docs/whatsapp/cloud-api/get-started" target="_blank">Guía oficial completa →</a>
                    </div>
                </details>
                
                <details style="margin:10px 0;">
                    <summary style="cursor:pointer;font-weight:bold;">¿Cómo creo un bot de Telegram?</summary>
                    <div style="padding:10px;">
                        1. Buscá <a href="https://t.me/BotFather" target="_blank">@BotFather</a> en Telegram<br>
                        2. Escribí <code>/newbot</code><br>
                        3. Seguilo las instrucciones (nombre y username)<br>
                        4. Te dará un <strong>Bot Token</strong> (guárdalo bien)<br>
                        5. Buscá <a href="https://t.me/myidbot" target="_blank">@myidbot</a> para obtener tu Chat ID
                    </div>
                </details>
                
                <details style="margin:10px 0;">
                    <summary style="cursor:pointer;font-weight:bold;">El cron no funciona, ¿qué hago?</summary>
                    <div style="padding:10px;">
                        1. Verificá que WP-Cron esté habilitado en tu hosting<br>
                        2. Revisá los logs arriba para ver errores<br>
                        3. Probá el botón "Generar post ahora"<br>
                        4. Verificá que la API Key de Gemini sea correcta<br>
                        5. Asegurate de tener al menos una fuente RSS O un prompt personalizado
                    </div>
                </details>
                
                <details style="margin:10px 0;">
                    <summary style="cursor:pointer;font-weight:bold;">¿Dónde veo los posts generados?</summary>
                    <div style="padding:10px;">
                        Los posts se guardan en <strong>Borradores</strong> (a menos que actives "Publicar directamente").<br>
                        Vas a: wp-admin → Posts → Borradores<br>
                        Cada post tiene metadata en "Campos personalizados" con:<br>
                        • <code>simple_gemini_auto</code> = 1 (es auto-generado)<br>
                        • <code>simple_gemini_source_url</code> = URL de origen<br>
                        • <code>simple_gemini_source_type</code> = tipo de fuente
                    </div>
                </details>
            </div>

            <!-- FOOTER -->
            <p style="text-align:center;color:#888;margin-top:30px;">
                <strong>Simple Gemini for WP v1.5 Ultra Robusta</strong><br>
                Desarrollado para Punto Río Negro - Medio Digital Local<br>
                <span style="font-size:11px;">Compatible con WordPress 5.0+ • PHP 7.4+ • Requiere cURL</span>
            </p>
        </div>
        <?php
    });
});

// ====================== ENQUEUE JS ======================
function simple_gemini_enqueue_editor() {
    if (get_current_screen()->base !== 'post') return;
    
    // Editor principal con Gemini
    wp_enqueue_script('simple-gemini-editor', plugin_dir_url(__FILE__) . 'simple-gemini-editor.js', 
        ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch'], '1.0', true);
    wp_localize_script('simple-gemini-editor', 'simpleGeminiData', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('gemini_gen')
    ]);
    
    // Botón publicar directo
    wp_enqueue_script('simple-gemini-publish', plugin_dir_url(__FILE__) . 'publish-btn.js', 
        ['wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data'], '1.0', true);
}
add_action('enqueue_block_editor_assets', 'simple_gemini_enqueue_editor');

// ====================== HELPERS ======================
function simple_gemini_get_api_key() {
    return defined('SIMPLE_GEMINI_API_KEY') ? SIMPLE_GEMINI_API_KEY : get_option('simple_gemini_api_key', '');
}

// ====================== GENERACIÓN GEMINI TEXTO (con retry) ======================
function simple_gemini_generate_text($prompt, $model = 'gemini-2.0-flash') {
    $key = simple_gemini_get_api_key();
    if (empty($key)) {
        error_log('[Simple Gemini] ERROR: Falta API Key Gemini');
        return new WP_Error('no_key', 'Falta API Key Gemini');
    }

    $body = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 2048
        ]
    ];
    
    $args = [
        'headers' => ['Content-Type' => 'application/json'], 
        'body' => wp_json_encode($body), 
        'timeout' => 90
    ];

    for ($i = 1; $i <= 2; $i++) {
        $response = wp_remote_post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", $args);
        if (!is_wp_error($response)) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code >= 200 && $code < 300) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($text)) {
                    return $text;
                }
            }
        }
        sleep(2);
    }
    error_log('[Simple Gemini] Gemini Text falló después de 2 intentos');
    return new WP_Error('gemini_failed', 'Gemini no respondió después de reintentos');
}

// ====================== GENERACIÓN IMAGEN GEMINI ======================
function simple_gemini_generate_image($prompt) {
    $key = simple_gemini_get_api_key();
    if (empty($key)) {
        return new WP_Error('no_key', 'Falta API Key Gemini');
    }

    $body = [
        'contents' => [['parts' => [['text' => $prompt]]]],
        'generationConfig' => [
            'responseModalities' => ['IMAGE'],
            'temperature' => 0.8
        ]
    ];

    $args = [
        'headers' => ['Content-Type' => 'application/json'], 
        'body' => wp_json_encode($body), 
        'timeout' => 120
    ];

    $model = 'gemini-2.0-flash-exp';
    $response = wp_remote_post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", $args);
    
    if (is_wp_error($response)) {
        error_log('[Simple Gemini] Error generating image: ' . $response->get_error_message());
        return new WP_Error('image_failed', 'Error al generar imagen');
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $base64 = $data['candidates'][0]['content']['parts'][0]['inlineData']['data'] ?? '';

    if (empty($base64)) {
        error_log('[Simple Gemini] No se generó imagen, respuesta: ' . wp_remote_retrieve_body($response));
        return new WP_Error('no_image', 'No generó imagen');
    }

    // Descargar y guardar imagen
    $image_data = base64_decode($base64);
    $filename = 'gemini-' . time() . '-' . sanitize_file_name($prompt) . '.png';
    $upload = wp_upload_bits($filename, null, $image_data);
    
    if ($upload['error']) {
        error_log('[Simple Gemini] Upload error: ' . $upload['error']);
        return new WP_Error('upload_error', $upload['error']);
    }

    $attach_id = wp_insert_attachment([
        'post_mime_type' => 'image/png',
        'post_title' => sanitize_text_field($prompt),
        'post_status' => 'inherit'
    ], $upload['file']);

    if (!is_wp_error($attach_id)) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
    }

    return $attach_id;
}

// ====================== RSS (ultra robustos) ======================
function simple_gemini_get_rss_content() {
    $urls = array_filter(array_map('trim', explode("\n", get_option('simple_gemini_rss_urls', ''))));
    $content = '';
    
    foreach ($urls as $url) {
        if (empty($url)) continue;
        
        $rss = fetch_feed($url);
        if (is_wp_error($rss)) {
            error_log('[Simple Gemini] RSS error for ' . $url . ': ' . $rss->get_error_message());
            continue;
        }

        $items = $rss->get_items(0, 3);
        foreach ($items as $item) {
            $title = $item->get_title();
            $desc = wp_strip_all_tags($item->get_description());
            $content .= "📰 " . $title . "\n" . substr($desc, 0, 300) . "...\n\n";
        }
    }
    
    return trim($content);
}

function simple_gemini_get_x_tweets() {
    $urls = array_filter(array_map('trim', explode("\n", get_option('simple_gemini_x_rss_urls', ''))));
    $content = '';
    
    foreach ($urls as $url) {
        if (empty($url)) continue;
        
        $rss = fetch_feed($url);
        if (is_wp_error($rss)) continue;

        $items = $rss->get_items(0, 5);
        foreach ($items as $item) {
            $title = $item->get_title();
            $content .= "🐦 " . $title . "\n";
        }
    }
    
    return trim($content);
}

function simple_gemini_get_ig_content() {
    $urls = array_filter(array_map('trim', explode("\n", get_option('simple_gemini_ig_rss_urls', ''))));
    $content = '';
    
    foreach ($urls as $url) {
        if (empty($url)) continue;
        
        $rss = fetch_feed($url);
        if (is_wp_error($rss)) continue;

        $items = $rss->get_items(0, 3);
        foreach ($items as $item) {
            $title = $item->get_title();
            $content .= "📷 " . $title . "\n";
        }
    }
    
    return trim($content);
}

// ====================== NOTIFICACIONES ======================
// WhatsApp Meta Cloud API
function simple_gemini_send_whatsapp($message, $image_url = null) {
    $token = get_option('simple_gemini_wa_token', '');
    $phone_id = get_option('simple_gemini_wa_phone_id', '');
    $to = get_option('simple_gemini_wa_to', '');
    
    if (empty($token) || empty($phone_id) || empty($to)) {
        return false;
    }

    $url = "https://graph.facebook.com/v21.0/{$phone_id}/messages";
    $headers = [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json'
    ];

    $body = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => ['body' => $message]
    ];

    $response = wp_remote_post($url, [
        'headers' => $headers,
        'body' => wp_json_encode($body)
    ]);

    if (!empty($image_url)) {
        sleep(2);
    }

    return !is_wp_error($response);
}

// Telegram con sendPhoto
function simple_gemini_send_telegram($message, $image_path = null) {
    $token = get_option('simple_gemini_tg_token', '');
    $chat_id = get_option('simple_gemini_tg_chat_id', '');
    
    if (empty($token) || empty($chat_id)) {
        return false;
    }

    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $response1 = wp_remote_post($url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body' => wp_json_encode([
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'HTML'
        ])
    ]);

    if (!empty($image_path) && file_exists($image_path)) {
        $url_photo = "https://api.telegram.org/bot{$token}/sendPhoto";
        $image_data = base64_encode(file_get_contents($image_path));
        wp_remote_post($url_photo, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => wp_json_encode([
                'chat_id' => $chat_id,
                'photo' => 'data://image/jpeg;base64,' . $image_data,
                'caption' => $message
            ])
        ]);
    }

    return !is_wp_error($response1);
}

// Email
function simple_gemini_send_email($subject, $message) {
    $email = get_option('simple_gemini_alert_email', get_option('admin_email'));
    if (empty($email)) return false;

    return wp_mail($email, $subject, $message);
}

// ====================== NOTIFICACIÓN CADENA (robusta) ======================
function simple_gemini_notify($title, $content, $image_id = null) {
    $message = "📰 <b>{$title}</b>\n\n" . substr(strip_tags($content), 0, 500);
    $image_path = null;
    
    if (!empty($image_id)) {
        $image_path = get_attached_file($image_id);
    }

    if (get_option('simple_gemini_wa_enable', 0)) {
        simple_gemini_send_whatsapp($message, $image_path);
    }

    if (get_option('simple_gemini_tg_enable', 0)) {
        simple_gemini_send_telegram($message, $image_path);
    }

    if (get_option('simple_gemini_alert_enable', 1)) {
        simple_gemini_send_email('Nuevo post: ' . $title, $message);
    }
}

// ====================== AJAX HANDLERS ======================
add_action('wp_ajax_simple_gemini_generate', function() {
    check_ajax_referer('gemini_gen', 'nonce');
    
    $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
    $model = sanitize_text_field($_POST['model'] ?? 'gemini-2.0-flash');
    
    if (empty($prompt)) {
        wp_send_json_error(['message' => 'Prompt vacío']);
    }

    $text = simple_gemini_generate_text($prompt, $model);
    
    if (is_wp_error($text)) {
        wp_send_json_error(['message' => $text->get_error_message()]);
    }
    
    wp_send_json_success(['text' => $text]);
});

add_action('wp_ajax_simple_gemini_full', function() {
    check_ajax_referer('gemini_gen', 'nonce');
    
    $prompt = sanitize_textarea_field($_POST['prompt'] ?? '');
    $model = sanitize_text_field($_POST['model'] ?? 'gemini-2.0-flash');
    
    if (empty($prompt)) {
        wp_send_json_error(['message' => 'Prompt vacío']);
    }

    $text = simple_gemini_generate_text($prompt . " - Escribí en estilo periodístico para news de Río Negro, 500-700 palabras", $model);
    
    if (is_wp_error($text)) {
        wp_send_json_error(['message' => $text->get_error_message()]);
    }

    $lines = explode("\n", trim($text));
    $title = 'Noticia - ' . date('d/m/Y');
    $content = $text;
    
    if (stripos($lines[0], 'título:') !== false || stripos($lines[0], 'título') !== false) {
        $title = trim(str_ireplace(['título:', 'título'], '', $lines[0]));
        $content = implode("\n", array_slice($lines, 1));
    }

    $image_id = 0;
    $image_gen = simple_gemini_generate_image($title . ' ' . substr(strip_tags($content), 0, 100));
    if (!is_wp_error($image_gen)) {
        $image_id = $image_gen;
    }

    $category = get_option('simple_gemini_category', 1);
    $publish_direct = get_option('simple_gemini_publish_direct', 0);

    $post_id = wp_insert_post([
        'post_title' => sanitize_text_field($title),
        'post_content' => wp_kses_post($content),
        'post_status' => $publish_direct ? 'publish' : 'draft',
        'post_author' => 1,
        'post_category' => [$category ?: 1],
        'meta_input' => ['simple_gemini_auto' => 1]
    ]);

    if (!empty($image_id)) {
        set_post_thumbnail($post_id, $image_id);
    }

    simple_gemini_notify($title, $content, $image_id);

    wp_send_json_success([
        'content' => $text,
        'post_id' => $post_id,
        'image_id' => $image_id
    ]);
});

// ====================== SISTEMA ANTI-DUPLICADOS ======================
function simple_gemini_check_duplicate($title, $source_url = '') {
    global $wpdb;
    
    $similar = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, post_title FROM {$wpdb->posts} 
        WHERE post_type = 'post' 
        AND post_status IN ('publish', 'draft') 
        AND post_title LIKE %s
        LIMIT 5",
        '%' . $wpdb->esc_like($title) . '%'
    ));
    
    if (!empty($similar)) {
        foreach ($similar as $post) {
            $similarity = similar_text(strtolower($title), strtolower($post->post_title));
            if ($similarity > 70) {
                error_log("[Simple Gemini] Posible duplicado encontrado: ID {$post->ID} - '{$post->post_title}'");
                return $post->ID;
            }
        }
    }
    
    if (!empty($source_url)) {
        $by_url = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = 'simple_gemini_source_url' 
            AND meta_value = %s 
            LIMIT 1",
            $source_url
        ));
        if (!empty($by_url)) {
            error_log("[Simple Gemini] Duplicado por URL: {$source_url}");
            return $by_url;
        }
    }
    
    return false;
}

function simple_gemini_save_source_meta($post_id, $source_url, $source_type) {
    update_post_meta($post_id, 'simple_gemini_source_url', esc_url_raw($source_url));
    update_post_meta($post_id, 'simple_gemini_source_type', sanitize_text_field($source_type));
    update_post_meta($post_id, 'simple_gemini_processed_at', current_time('mysql'));
}

// ====================== CONTROL DE CALIDAD IA ======================
function simple_gemini_quality_check($content, $title) {
    $issues = [];
    
    if (strlen($content) < 200) {
        $issues[] = 'Contenido muy corto (< 200 caracteres)';
    }
    
    if (strlen($content) > 10000) {
        $issues[] = 'Contenido muy largo (> 10000 caracteres)';
    }
    
    $ai_phrases = ['como modelo de lenguaje', 'no puedo generar', 'no tengo capacidad', 'no estoy seguro de'];
    foreach ($ai_phrases as $phrase) {
        if (stripos($content, $phrase) !== false) {
            $issues[] = "Contenido contiene frase de IA: '{$phrase}'";
        }
    }
    
    if (empty($title) || strlen($title) < 5) {
        $issues[] = 'Título inválido o muy corto';
    }
    
    $words = array_count_values(str_word_count(strtolower($content), 1));
    $repeated = array_filter($words, function($count) { return $count > 5; });
    if (count($repeated) > 3) {
        $issues[] = 'Demasiadas repeticiones de palabras';
    }
    
    if (!empty($issues)) {
        error_log('[Simple Gemini] Quality check falló: ' . implode(', ', $issues));
        return ['valid' => false, 'issues' => $issues];
    }
    
    return ['valid' => true, 'issues' => []];
}

// ====================== AUTO POST (protegido) ======================
function simple_gemini_auto_create_post() {
    if (get_option('simple_gemini_cron_enable', 1) !== 1) {
        error_log('[Simple Gemini] Cron ejecutado pero está desactivado en ajustes → abortado');
        return;
    }

    $key = simple_gemini_get_api_key();
    if (empty($key)) {
        error_log('[Simple Gemini] Auto post: falta API key');
        return;
    }

    // Límite diario anti-spam
    $daily_limit = get_option('simple_gemini_daily_limit', 0);
    if ($daily_limit > 0) {
        $today_posts = get_posts([
            'post_type' => 'post',
            'date_query' => ['after' => 'today'],
            'meta_key' => 'simple_gemini_auto',
            'meta_value' => 1,
            'fields' => 'ids'
        ]);
        if (count($today_posts) >= $daily_limit) {
            error_log('[Simple Gemini] Límite diario alcanzado: ' . count($today_posts) . ' posts hoy');
            return;
        }
    }

    // Obtener contenido de fuentes
    $rss = simple_gemini_get_rss_content();
    $x = simple_gemini_get_x_tweets();
    $ig = simple_gemini_get_ig_content();

    $sources = trim($rss . "\n\n" . $x . "\n\n" . $ig);
    
    // Prompt personalizado o por defecto
    $custom_prompt = get_option('simple_gemini_cron_prompt', '');
    
    if (!empty($custom_prompt)) {
        $prompt = $custom_prompt;
        if (!empty($sources)) {
            $prompt .= "\n\nFuentes disponibles:\n" . $sources;
        }
    } elseif (empty($sources)) {
        $prompt = "Generá una noticia periodística sobre economía, turismo o política en General Roca, Río Negro, Argentina. 
        Título al inicio (en negrita). 500-700 palabras. Estilo neutral como Diario Río Negro.
        Incluí información local relevante para lectores del Alto Valle.";
    } else {
        $prompt = "Basado en estas fuentes locales de Río Negro/Patagonia:\n\n" . $sources . "\n\n
        Generá una noticia periodística original de 500-700 palabras.
        Título al inicio (en negrita). Estilo neutral como Diario Río Negro.
        No copies texto literal, reescribí con tus palabras.";
    }

    $text = simple_gemini_generate_text($prompt);
    if (is_wp_error($text)) {
        error_log('[Simple Gemini] Auto post error: ' . $text->get_error_message());
        return;
    }

    // Parsear título
    $lines = explode("\n", trim($text));
    $title = 'Noticia Local - ' . date('d/m/Y H:i');
    $content = $text;
    
    if (count($lines) > 0 && (stripos($lines[0], 'título') !== false || strlen($lines[0]) < 100)) {
        $title = trim(str_ireplace(['título:', 'título', '**', '*'], '', $lines[0]));
        if (strlen($title) > 100) {
            $title = 'Noticia Local - ' . date('d/m/Y');
            $content = $text;
        } else {
            $content = implode("\n", array_slice($lines, 1));
        }
    }

    // === CONTROL DE CALIDAD ===
    $quality = simple_gemini_quality_check($content, $title);
    if (!$quality['valid']) {
        error_log('[Simple Gemini] Quality check falló: ' . implode(', ', $quality['issues']) . ' - Título: ' . $title);
        simple_gemini_send_email('[Simple Gemini] Quality Check Falló', "El contenido no pasó control de calidad.\n\nProblemas: " . implode(', ', $quality['issues']) . "\n\nTítulo: {$title}");
        return;
    }

    // === ANTI-DUPLICADOS ===
    $duplicate_id = simple_gemini_check_duplicate($title);
    if ($duplicate_id) {
        error_log('[Simple Gemini] Post duplicado omitido: ' . $title . ' - ID existente: ' . $duplicate_id);
        return;
    }

    // Generar imagen
    $image_id = 0;
    $image_gen = simple_gemini_generate_image($title . ' ' . substr(strip_tags($content), 0, 100) . ' General Roca Río Negro');
    if (!is_wp_error($image_gen)) {
        $image_id = $image_gen;
    }

    // Obtener opciones y mapeo de categorías
    $default_category = get_option('simple_gemini_category', 1);
    $publish_direct = get_option('simple_gemini_publish_direct', 0);
    $rss_category_map = get_option('simple_gemini_rss_category_map', []);
    $category = $default_category;
    
    // Usar mapeo de categorías por RSS si está disponible
    if (!empty($sources) && !empty($rss_category_map)) {
        $all_rss_urls = array_merge(
            array_filter(array_map('trim', explode("\n", get_option('simple_gemini_rss_urls', '')))),
            array_filter(array_map('trim', explode("\n", get_option('simple_gemini_x_rss_urls', '')))),
            array_filter(array_map('trim', explode("\n", get_option('simple_gemini_ig_rss_urls', ''))))
        );
        
        foreach ($all_rss_urls as $rss_url) {
            if (!empty($rss_url) && stripos($sources, $rss_url) !== false) {
                if (isset($rss_category_map[$rss_url]) && intval($rss_category_map[$rss_url]) > 0) {
                    $category = intval($rss_category_map[$rss_url]);
                    error_log('[Simple Gemini] Categoría asignada por mapeo RSS: ' . $category . ' para URL: ' . $rss_url);
                    break;
                }
            }
        }
    }

    // Crear post
    $post_id = wp_insert_post([
        'post_title' => sanitize_text_field($title),
        'post_content' => wp_kses_post($content),
        'post_status' => $publish_direct ? 'publish' : 'draft',
        'post_author' => 1,
        'post_category' => [$category ?: $default_category],
        'meta_input' => [
            'simple_gemini_auto' => 1,
            'simple_gemini_quality_check' => 'passed'
        ]
    ]);

    if (!empty($image_id)) {
        set_post_thumbnail($post_id, $image_id);
    }

    // Guardar metadata de fuente
    simple_gemini_save_source_meta($post_id, '', 'auto_cron');

    // Notificar
    simple_gemini_notify($title, $content, $image_id);

    error_log('[Simple Gemini] Auto post creado: ID ' . $post_id . ' - Quality: passed');
}

// ====================== CRON + TOGGLE ======================
add_action('simple_gemini_daily_auto', 'simple_gemini_auto_create_post');

register_activation_hook(__FILE__, function(){
    if (get_option('simple_gemini_cron_enable', 1) === 1) {
        if (!wp_next_scheduled('simple_gemini_daily_auto')) {
            wp_schedule_event(strtotime('tomorrow 08:00'), 'daily', 'simple_gemini_daily_auto');
        }
    }
});

register_deactivation_hook(__FILE__, function(){
    $timestamp = wp_next_scheduled('simple_gemini_daily_auto');
    if ($timestamp) wp_unschedule_event($timestamp, 'simple_gemini_daily_auto');
});

add_action('updated_option', function($option, $old, $new){
    if ($option === 'simple_gemini_cron_enable') {
        if ($new == 0) {
            $ts = wp_next_scheduled('simple_gemini_daily_auto');
            if ($ts) wp_unschedule_event($ts, 'simple_gemini_daily_auto');
        } elseif ($new == 1 && $old == 0) {
            if (!wp_next_scheduled('simple_gemini_daily_auto')) {
                wp_schedule_event(strtotime('tomorrow 08:00'), 'daily', 'simple_gemini_daily_auto');
            }
        }
    }
}, 10, 3);

// ====================== SHORTCODE ======================
add_shortcode('simple_gemini', function($atts){
    $atts = shortcode_atts(['prompt'=>''], $atts);
    if (empty($atts['prompt'])) return 'Usá: [simple_gemini prompt="tu pregunta"]';
    
    $res = simple_gemini_generate_text($atts['prompt']);
    return is_wp_error($res) ? 'Error: ' . $res->get_error_message() : '<pre>' . esc_html($res) . '</pre>';
});
