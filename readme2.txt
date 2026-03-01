=== Simple Gemini for WP - Ultra Robusta ===
Contributors: Grok para Traful
Tags: ai, gemini, artificial-intelligence, news, automatic-posting, rss, whatsapp, telegram, gutenberg, editor, content-generator, local-news, patagonia
Requires at least: 5.0
Tested up to: 6.7
Stable tag: 1.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

🤖 IA local con Gemini + imágenes + auto posts desde RSS (noticias, X, IG) + notificaciones WhatsApp/Telegram/Email. Perfecto para portales de noticias locales como Diario Río Negro.

== Description ==

**Simple Gemini for WP** es un plugin de WordPress diseñado para portales de noticias locales en Argentina (especialmente Río Negro, Patagonia, Alto Valle, General Roca).

### Características principales:

*   **🤖 Gemini AI Integration**: Generación de texto con Google Gemini (modelos 2.0 Flash, 2.0 Flash Exp, 1.5 Flash)
*   **🖼️ Generación de Imágenes**: Crea imágenes automáticamente con Gemini
*   **👁️ Gemini Vision (Beta)**: Analiza imágenes de RSS/Instagram para mejores posts
*   **📰 Auto-blogging desde RSS**: Integra noticias desde feeds RSS de:
    *   Noticias locales (Diario Río Negro, ADN, etc.)
    *   X/Twitter
    *   Instagram
*   **📱 Notificaciones en cadena**: Al crear un post, recibe alerta por:
    *   WhatsApp (Meta Cloud API) - Principal
    *   Telegram (con foto) - Fallback
    *   Email - Fallback final
*   **⏰ Cron Automático**: Genera posts automáticamente todos los días a las 8 AM
*   **📝 Prompt Personalizado**: Definí tu propio prompt para el cron
*   **🛡️ Límite Diario Anti-Spam**: Máximo posts por día (recomendado 1-3)
*   **📂 Selector de Categoría**: Elegí la categoría para los posts automáticos
*   **🎯 Publicar directo o Draft**: Elegí si publicar automáticamente o revisar primero
*   **🎨 Sidebar de Gutenberg**: Botón en el editor para generar contenido con IA
*   **🔄 Toggle fácil**: Activa/desactiva el cron desde los ajustes
*   **📋 Logs en vivo**: Ver los logs de ejecución directamente en admin

### Ideal para:

*   Diarios locales y portales de noticias (Diario Río Negro, ADN, etc.)
*   Blogs de nicho en Argentina/Patagonia/Alto Valle
*   Sitios de noticias automáticas
*   Canales de WhatsApp/Telegram de noticias

== Installation ==

1. Subí la carpeta `simple-gemini-for-wp` a `/wp-content/plugins/`
2. Activá el plugin en WordPress → Plugins
3. Andá a **Ajustes → Simple Gemini**
4. Configurá tu API Key de Google AI Studio (es gratis)
5. (Opcional) Agregá URLs RSS de noticias, X e Instagram
6. (Opcional) Configurá WhatsApp, Telegram y Email para notificaciones

== Frequently Asked Questions ==

= ¿Necesito pagar por usar Gemini? =

**No**. Google Gemini 2.0 Flash tiene un tier gratuito muy generoso (~1500 requests/minuto, 1M tokens/minuto). Para un portal de noticias local con 1-5 posts diarios, el costo es **cero**.

= ¿Dónde consigo la API Key? =

1. Ir a [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Crear una cuenta Google (si no tenés)
3. Generar API Key
4. Pegarla en Ajustes → Simple Gemini

= ¿Funciona en cualquier hosting? =

**Sí**. El plugin usa solo funciones nativas de WordPress (`wp_remote_post`, `fetch_feed`) y no requiere Composer ni librerías externas. Funciona en hosting compartido, VPS, etc.

= ¿Cómo funciona el auto-blogging? =

1. Configurá las URLs RSS en los ajustes
2. Activá el cron diario
3. Elegí si publicar directo o como borrador
4. Opcional: definí un prompt personalizado
5. El plugin diariamente:
   - Lee las últimas noticias de los RSS
   - Usa Gemini para reescribir/generar una noticia original
   - Genera una imagen destacada
   - Crea un post (borrador o publicado)
   - Te notifica por WhatsApp/Telegram/Email

= ¿Puedo limitar los posts diarios? =

**Sí**. Tenés un campo "Límite posts/día" en ajustes. Recomendado: 1-3 posts por día para evitar spam.

= ¿Qué pasa si falla Gemini? =

El plugin tiene sistema de retry (2 intentos) y logs detallados. Si falla, te llega igual la notificación de error por email.

= ¿Puedo usar solo el generador manual sin el auto-post? =

**Sí**. Podés usar el botón del sidebar de Gutenberg cuando quieras, sin activar el cron.

== Changelog ==

= 1.6 Ultra Robusta =
* Agregado selector de modelo (gemini-2.0-flash, gemini-2.0-flash-exp, gemini-1.5-flash)
* Agregado prompt personalizado del cron
* Agregado límite diario de posts (anti-spam)
* Agregado selector de categoría para posts automáticos
* Agregado toggle "Publicar directamente" o guardar como borrador
* Agregado Gemini Vision (Beta) - análisis de imágenes
* Agregados logs en vivo en el panel de admin
* Mejorado sistema de notificaciones en cadena

= 1.5 Ultra Robusta =
* Agregado toggle para activar/desactivar cron desde ajustes
* Mejorado manejo de errores con logs
* Sistema de retry para Gemini (2 intentos)
* Notificaciones en cadena: WhatsApp → Telegram → Email
* RSS ultra robustos con manejo de errores
* Imagen destacada automática para posts

= 1.0 =
* Versión inicial
* Integración Gemini texto
* Sidebar Gutenberg
* RSS noticias + X + IG
* WhatsApp + Telegram + Email notifications

== Upgrade Notice ==

= 1.6 =
Esta versión incluye mejoras importantes: prompt personalizado, límite diario anti-spam, selector de categoría y más. Actualizá si usás la versión 1.5 o anterior.
