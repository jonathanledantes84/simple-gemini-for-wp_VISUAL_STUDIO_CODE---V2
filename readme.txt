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

= ¿Cómo configuro la API Key de Gemini? =

1. Ve a [Google AI Studio](https://aistudio.google.com/app/apikey)
2. Inicia sesión con tu cuenta Google
3. Haz clic en "Create API Key"
4. Copia la clave generada
5. Ve a WordPress → Ajustes → Simple Gemini
6. Pega la API Key en el campo correspondiente
7. ¡Listo! Ya podés usar Gemini

**Nota:** El tier gratuito es muy generoso (~1500 requests/minuto). Para un portal local con 1-5 posts diarios, el costo es **cero**.

= ¿Dónde consigo URLs RSS de noticias? =

La mayoria de diarios y portales de noticias tienen feeds RSS. Alguns ejemplos para Río Negro/Patagonia:

- Diario Río Negro: `https://www.rionegro.com.ar/feed/`
- LM Neuquén: `https://www.lmneuquen.com.ar/rss/`
- Azminforma: `https://www.azminforma.com/feed/`
- Río Negro Radio: `https://www.rionegro.com.ar/feed/radionoticias/`

**Cómo encontrar RSS de cualquier sitio:**
1. Agregá `/feed/` al final de la URL del sitio
2. O buscá el icono de RSS en el navegador
3. O usá servicios como [RSSFeed](https://rssfeed.org)

= ¿Cómo configuro RSS de Twitter/X? =

Twitter no ofrece RSS nativo. Usá un conversor:

1. Ve a [RSS.app](https://rss.app) o [Feedex](https://feedex.net)
2. Conecta tu cuenta de X/Twitter
3. Crea un feed con las cuentas que quieras seguir
4. Copia la URL RSS del feed
5. Pégala en el campo "RSS X/Twitter" del plugin

= ¿Cómo configuro RSS de Instagram? =

Instagram tampoco ofrece RSS nativo. Opciones:

1. **RSS.app** (igual que Twitter): Conecta Instagram y genera un feed
2. Busca "Instagram to RSS converter" en Google
3. Pega la URL en "RSS Instagram"

= ¿Cómo configuro WhatsApp Business? =

**Paso 1: Crear cuenta de Meta Business**
1. Ve a [business.facebook.com](https://business.facebook.com)
2. Crea una cuenta de negocio

**Paso 2: Crear app en Meta Developers**
1. Ve a [developers.facebook.com](https://developers.facebook.com)
2. Crea una nueva app tipo "Otro"
3. Agrega el producto "WhatsApp"

**Paso 3: Configurar WhatsApp**
1. En la sección WhatsApp del app, buscá "Configuración"
2. Anota el **Phone Number ID** (ID del número de teléfono)
3. En "Acceso a la API", generá un **Token temporal** o configurá un token permanente

**Paso 4: Obtener número destino**
1. El número que recibirá los mensajes debe estar verificado en Meta
2. Agregalo en formato internacional sin +: `5492991234567`

**Paso 5: Completar en WordPress**
1. Ve a Ajustes → Simple Gemini
2. Pega el **Permanent Access Token**
3. Pega el **Phone Number ID**
4. Pega el número destino
5. Activá WhatsApp

= ¿Cómo creo un bot de Telegram? =

1. Abre Telegram y buscá **@BotFather**
2. Escribí `/newbot`
3. Seguilo las instrucciones:
   - Elegí un nombre para el bot (ej: "Mi Bot de Noticias")
   - Elegí un username (debe terminar en `bot`, ej: `miportalnoticias_bot`)
4. **¡Guardá el Bot Token!** Lo necesitás para el plugin
5. (Opcional) Personalizá el bot con `/setname`, `/setpicture`, etc.

= ¿Cómo obtengo mi Chat ID de Telegram? =

1. Buscá **@myidbot** en Telegram
2. Iniciá el chat y escribí `/getid`
3. Te responderá con tu ID de usuario (ej: `123456789`)
4. Copialo y pegalo en los ajustes del plugin

**Para grupos:**
1. Agregá el bot al grupo
2. Escribí `/getgroupid` al bot
3. Te dará el ID del grupo (número negativo)

= ¿Cómo funciona el cron automático? =

1. **Activar:** Marcá "Activar cron diario" en los ajustes
2. **Horario:** Se ejecuta automáticamente a las 8:00 AM todos los días
3. **Qué hace:**
   - Lee las últimas noticias de tus RSS configurados
   - Envía el contenido a Gemini para generar una noticia original
   - Crea una imagen destacada con Gemini
   - Publica el post (como borrador o publicado, según configures)
   - Te envía notificación por WhatsApp/Telegram/Email

= ¿Puedo probar el cron sin esperar? =

**Sí!** En los ajustes hay un botón "🚀 Generar post ahora". Haz clic para ejecutar el cron inmediatamente.

= ¿El cron no funciona. ¿Qué hago? =

1. **Verificá que WP-Cron esté habilitado** en tu hosting
2. **Revisá los logs** en la sección inferior del panel de admin
3. **Verificá que la API Key** de Gemini sea correcta
4. **Asegurate de tener** al menos una fuente RSS O un prompt personalizado
5. **Probá el botón "Generar post ahora"** para ver errores específicos

= ¿Dónde veo los posts generados? =

Los posts se guardan en **Borradores** (a menos que actives "Publicar directamente").

Ve a: **WordPress → Posts → Borradores**

Cada post tiene metadata en "Campos personalizados":
- `simple_gemini_auto` = 1 (es auto-generado)
- `simple_gemini_source_url` = URL de origen
- `simple_gemini_source_type` = tipo de fuente (rss, x, ig, auto_cron)
- `simple_gemini_processed_at` = fecha de generación

= ¿Cómo funciona el control de calidad? =

El plugin automáticamente:
1. Verifica que el contenido tenga entre 200 y 10,000 caracteres
2. Detecta frases que indican "no puedo generar" o "como modelo de lenguaje"
3. Evita títulos muy cortos o vacíos
4. Detecta contenido duplicado con posts anteriores (>70% similaridad)
5. Guarda metadata de la fuente (URL, tipo, fecha)

Si falla el control de calidad, NO se publica el post y recibís un email de alerta.

= ¿Puedo usar el generador manual sin el auto-post? =

**Sí.** Podés usar el botón del sidebar de Gutenberg cuando quieras, sin activar el cron. Es ideal para generar contenido específico bajo demanda.

= ¿Funciona en cualquier hosting? =

**Sí.** El plugin usa solo funciones nativas de WordPress:
- `wp_remote_post()` para llamadas API
- `fetch_feed()` para RSS
- `wp_mail()` para emails
- `wp_schedule_event()` para el cron

No requiere Composer ni librerías externas. Funciona en:
- Hosting compartido (GoDaddy, Hostinger, DonWeb, etc.)
- VPS (DigitalOcean, Linode, etc.)
- WordPress.com
- Servidores dedicados

= ¿Qué modelos de Gemini puedo usar? =

El plugin permite elegir entre:
- **gemini-2.0-flash** ⭐ (recomendado): Rápido y económico, ideal para la mayoria de casos
- **gemini-2.0-flash-exp**: Experimental, puede dar mejores resultados pero más lento
- **gemini-1.5-flash**: Estable, buena opción alternativa

= ¿Puedo definir un prompt personalizado? =

**Sí.** En la sección "Prompt Personalizado del Cron" podés escribir tus propias instrucciones para Gemini.

**Ejemplos:**
- "Escribí una noticia sobre economía en General Roca, Río Negro"
- "Generá un resumen de las principales noticias deportivas de la región"
- "Creá una nota sobre turismo en Villa La Angostura"

Dejá vacío para usar el prompt por defecto (noticias locales de Río Negro/Patagonia).

= ¿Cómo evito que se publique spam? =

Tenés varias herramientas anti-spam:

1. **Límite diario**: Configurá "Límite posts/día" (recomendado: 1-3)
2. **Publicar como borrador**: Dejá desmarcado "Publicar directamente" para revisar antes
3. **Control de calidad**: Activado por defecto, bloquea contenido de baja calidad
4. **Anti-duplicados**: Evita publicar noticias muy similares a las anteriores

= ¿El plugin es compatible con Gutenberg? =

**Sí.** El plugin agrega un panel lateral "Gemini IA" en el editor de Gutenberg donde podés:
- Escribir un prompt
- Elegir el modo (texto o texto + imagen)
- Generar contenido instantáneamente
- Insertarlo directamente en el post

También podés usar el botón "Publicar directo" para publicar inmediatamente.

= ¿Qué debo agregar en wp-config.php? =

El plugin funciona sin modificar wp-config.php, pero podés agregar estas líneas opcionales:

**1. Definir API Key como constante (más seguro):**

```
php
// En wp-config.php, antes de "/* That's all, stop editing! */"
define('SIMPLE_GEMINI_API_KEY', 'TU_API_KEY_AQUI');
```

Luego podés dejar el campo de API Key vacío en los ajustes del plugin.

**2. Habilitar logs de depuración (para troubleshooting):**

```
php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Los logs se guardarán en `wp-content/debug.log`.

**3. Desactivar WP-Cron nativo (si tenés problemas):**

```
php
define('DISABLE_WP_CRON', true);
```

Y configurá en el crontab del servidor:
```
*/5 * * * * curl -s https://tu-sitio.com/wp-cron.php?doing_wp_cron
```

**4. Aumentar límite de memoria (si hay errores):**

```
php
define('WP_MEMORY_LIMIT', '256M');
```

= Requisitos del servidor =

- WordPress 5.0+
- PHP 7.4+
- Extensión cURL habilitada
- allow_url_fopen = 1 (para RSS)
- Conexión a Internet (para API de Gemini)

= Fuentes RSS Recomendadas para Río Negro/Patagonia =

| Fuente | URL RSS | Categoría |
|--------|---------|-----------|
| Diario Río Negro | `https://www.rionegro.com.ar/feed/` | Actualidad |
| Río Negro Rural | `https://www.rionegro.com.ar/rural/feed/` | Economía |
| LM Neuquén | `https://www.lmneuquen.com.ar/rss/` | Actualidad Neuquén |
| SMN Alertas | `https://alertas.smn.gob.ar/feed/` | Clima |

= Prompts por Categoría =

**Economía:** Periodista agro de Alto Valle. Nota de 500 palabras sobre fruticultura, precios, exportaciones. Título SEO, tono neutral.

**Turismo:** Redactor turístico patagónico. Nota de 500 palabras sobre turismo Bariloche/Villa La Angostura. Tips para visitantes.

**Clima:** Meteorólogo patagónico. Nota de 400 palabras sobre clima Alto Valle. Consejos para productores/vecinos.

**Política:** Periodista político rionegrino. Nota equilibrada de 500 palabras sobre anuncios provinciales.

= Ejemplo JSON Config =

{
  "feeds": [
    {"name": "Diario Río Negro", "url": "https://www.rionegro.com.ar/feed/", "category": "Actualidad", "active": true},
    {"name": "SMN Alertas", "url": "https://alertas.smn.gob.ar/feed/", "category": "Clima", "active": true}
  ],
  "settings": {
    "model": "gemini-2.0-flash",
    "posts_per_day": 3,
    "publish_directly": false
  }
}

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
