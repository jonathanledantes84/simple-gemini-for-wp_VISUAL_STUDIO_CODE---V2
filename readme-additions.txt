= Fuentes RSS Recomendadas para Río Negro/Patagonia =

El plugin soporta múltiples fuentes RSS para auto-blogging. Aquí tenés las más relevantes para tu zona:

| Fuente | URL RSS | Categoría Sugerida |
|--------|---------|-------------------|
| Diario Río Negro (principal) | `https://www.rionegro.com.ar/feed/` | Actualidad |
| Río Negro Rural (Agro) | `https://www.rionegro.com.ar/rural/feed/` | Economía / Agro |
| Río Negro Sociedad | `https://www.rionegro.com.ar/sociedad/feed/` | Sociedad / Comunidad |
| Río Negro Deportes | `https://www.rionegro.com.ar/deportes/feed/` | Deportes Regionales |
| LM Neuquén | `https://www.lmneuquen.com.ar/rss/` | Actualidad Neuquén |
| LM Cipolletti | `https://www.lmcipolletti.com/rss` | Local Cipolletti |
| LM Cipolletti Local | `https://www.lmcipolletti.com/rss/local/` | Local Cipolletti |
| Noticias NQN | `https://www.noticiasnqn.com.ar/rss/noticias/` | Actualidad Neuquén |
| Noticias NQN Río Negro | `https://www.noticiasnqn.com.ar/rss/rio-negro/` | Río Negro Provincial |
| ADN Río Negro | `https://www.adnrionegro.com.ar/feed/` | Política / Provincia |
| Bariloche Opina (Turismo) | `https://www.barilocheopina.com/rss` | Turismo / Andina |
| ANROCA (Roca) | `https://www.anroca.com.ar/rss` | Local General Roca |
| Azminforma (Policiales) | `https://www.azminforma.com/feed/` | Sociedad / Policía |
| Cipo360 | `https://www.cipo360.com.ar/rss/noticias/` | Actualidad Cipolletti |
| 7 en Punto (Valle Medio) | `https://www.7enpunto.com/rss/actualidad/` | Actualidad Valle Medio |
| Línea Sur Noticias | `https://lineasurnoticias.com.ar/feed/` | Región Sur Río Negro |
| SMN Alertas (Clima) | `https://alertas.smn.gob.ar/feed/` | Clima / Alertas |
| INTA (Agro) | `https://inta.gob.ar/rss` | Agro / INTA |
| Canal 10 Río Negro | `https://www.canal10.ar/feed/` | Medios Públicos |
| Noticias Net (Viedma/Costa) | `https://www.noticiasnet.com.ar/feed/` | Actualidad Viedma / Costa |

= Prompts Recomendados por Categoría =

**Economía / Agro:**
Eres periodista agro de Alto Valle. Basado en el feed RSS, redactá nota de 500 palabras sobre fruticultura, precios de peras/manzana, sequía o exportaciones. Título SEO (ej: "Precios de la fruta en Roca: impacto en productores"). Incluí datos locales (Cooperativa, ruta 22) y pronóstico. Tono neutral.

**Turismo:**
Redactor turístico patagónico. Creá nota positiva de 500 palabras sobre turismo Bariloche/Villa La Angostura o eventos. Título con gancho, tips para visitantes desde Roca/Neuquén, impacto económico.

**Clima / Alertas:**
Meteorólogo patagónico. Generá nota de 400 palabras sobre clima Alto Valle (viento zonda, nevadas, sequía). Título práctico, pronóstico detallado, consejos para productores/vecinos.

**Política Provincial:**
Periodista político rionegrino. Generá nota equilibrada de 500 palabras sobre anuncios provinciales, regalías, tarifas con impacto en Alto Valle. Título objetivo, cuerpo con balance.

**Deportes Regionales:**
Cronista deportivo patagónico. Creá artículo de 450 palabras sobre fútbol (Roca Juniors, Cipolletti), rugby o eventos deportivos Alto Valle. Título atractivo, resultados/claves, cuerpo con análisis y futuro.

**Sociedad / Comunidad:**
Periodista de sociedad Alto Valle. Generá nota de 500 palabras sobre temas comunitarios, salud, educación o eventos vecinales en Roca/Cipolletti/Regina. Título con gancho humano, emotivo, cuerpo con impacto local y voces de vecinos.

**Actualidad Local (General Roca):**
Cronista local de Roca. Feed RSS configurado. Generá artículo de 400 palabras sobre obras, intendente, eventos o problemas vecinales en General Roca. Título objetivo, hechos, cuerpo equilibrado + voz de vecinos.

= Ejemplo de Configuración JSON =

Podés guardar esta configuración para importar más tarde:

{
  "feeds": [
    {
      "name": "Diario Río Negro",
      "url": "https://www.rionegro.com.ar/feed/",
      "category": "Actualidad",
      "active": true
    },
    {
      "name": "Río Negro Rural",
      "url": "https://www.rionegro.com.ar/rural/feed/",
      "category": "Economía",
      "active": true
    },
    {
      "name": "LM Neuquén",
      "url": "https://www.lmneuquen.com.ar/rss/",
      "category": "Actualidad Neuquén",
      "active": true
    },
    {
      "name": "SMN Alertas",
      "url": "https://alertas.smn.gob.ar/feed/",
      "category": "Clima",
      "active": true
    }
  ],
  "settings": {
    "api_key": "",
    "model": "gemini-2.0-flash",
    "posts_per_day": 3,
    "publish_directly": false,
    "default_category": 1,
    "telegram_enabled": true,
    "whatsapp_enabled": true,
    "email_enabled": true
  }
}
