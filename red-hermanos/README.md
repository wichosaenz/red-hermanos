# Red Hermanos — Artículos Relacionados entre Sitios

Capa de visualización para una red editorial de 20 sitios WordPress. Red Hermanos
**recibe** cada semana, vía REST API, el conjunto de artículos hermanos
recomendados (que le envía el flujo de n8n `WF4 — Red Hermanos Distributor`), los
**almacena** en su propia tabla y los **muestra** en el frontend con un bloque
que se adapta al tema activo.

No consulta Pinecone, no genera recomendaciones y **no imprime ningún JSON-LD /
schema** — de eso se encarga el orquestador externo. Es únicamente la capa de
recepción + display, equivalente funcional a *Jetpack Related Posts* pero entre
sitios afiliados del mismo grupo.

- **PHP:** 7.4 – 8.3
- **WordPress:** 5.8+ (probado en 6.x y 7.x)
- **Sin dependencias:** sin Composer, sin jQuery (en el front), sin CSS/JS
  externos, sin CDNs.

## Arquitectura

```
n8n (WF4 — Red Hermanos Distributor)  --POST /sync-->  [ tabla red_hermanos ]
                                                              |
   frontend  <--  RH_Renderer::render($args)  <--------------+
   (placements / widget / shortcode / template tag / endpoint /render)
```

Todos los puntos de entrada pasan por un único motor de render
(`RH_Renderer::render()`), de modo que las cinco vías de colocación comparten la
misma lógica de renderizado e imágenes:

1. **Widget** — para cualquier área de sidebar o footer.
2. **Placements** — auto-inserción por hook: antes/después del contenido,
   cabecera (`wp_body_open`) y pie (`wp_footer`), con opciones por zona.
3. **Shortcode** — `[red_hermanos]`.
4. **Template tag** — `red_hermanos( $args )` para archivos del tema.
5. **Endpoint de render** — `/render` público de solo lectura + `rh-embed.js`.

## Instalación

1. Sube `red-hermanos.zip` en **Plugins → Añadir nuevo → Subir plugin** →
   **Instalar ahora** → **Activar**. (No uses el botón «Download ZIP» de GitHub:
   anida las carpetas y WordPress no encuentra el plugin.)
2. O clona el repo dentro de `wp-content/plugins/`.

Al activar se crea la tabla `{prefix}red_hermanos` y se siembran las opciones por
defecto.

## Configuración

1. Crea una **Contraseña de aplicación** para un usuario con `edit_posts`
   (*Usuarios → Perfil → Contraseñas de aplicación*). n8n la usa como Basic Auth.
2. Abre el menú **Red Hermanos** y configura las pestañas General, Placements,
   Widgets & Shortcode, Status y Tools.

## Contrato de datos (`POST /wp-json/red-hermanos/v1/sync`)

Basic Auth (Contraseña de aplicación). Cuerpo JSON (campos en español):

```json
{
  "semana_iso": "2026-W33",
  "articulos": [
    {
      "post_url": "https://hermano.com/articulo/",
      "post_title": "Título exacto del artículo hermano",
      "post_excerpt": "Primeros ~160-200 caracteres…",
      "site_url": "https://hermano.com",
      "site_name": "Nombre legible del sitio hermano",
      "vertical": "automotive",
      "researcher": "Wilhelm Hartmann",
      "thumbnail_url": "https://hermano.com/wp-content/uploads/img.jpg",
      "similarity_score": 0.87,
      "topic_tag": "EV manufacturing trends",
      "formato_asignado": "cards_grid",
      "posicion": 1
    }
  ]
}
```

Respuesta: `{ success, semana, inserted, total_sent, timestamp }`.
`thumbnail_url` puede venir vacío — el render degrada sin imagen rota.

### Ejemplo con `curl`

```bash
curl -X POST "https://sitio.com/wp-json/red-hermanos/v1/sync" \
  -u "usuario:CONTRASENA_DE_APLICACION" \
  -H "Content-Type: application/json" \
  -d '{"semana_iso":"2026-W33","articulos":[{"post_url":"https://hermano.com/a/","post_title":"…","site_url":"https://hermano.com","site_name":"Hermano","posicion":1}]}'
```

### Otros endpoints

- `GET /wp-json/red-hermanos/v1/status` (con auth) — `{ plugin_version,
  active_articles, latest_sync, current_week, site_url }`.
- `GET /wp-json/red-hermanos/v1/render?format=ticker&count=5&thumbs=0` (público,
  solo lectura) — devuelve `{ format, count, html }` con marcado escapado.

## Formatos

- `cards_grid` — principal, pulido (grid responsive 3→2→1).
- `ticker`, `carousel`, `marquee`, `in_post` — funcionales pero mínimos en v1.0
  (`// TODO v1.1`); renderizan sin romper.

## Control de imágenes

Una miniatura solo se muestra si, en cascada, `show_thumbnails` (zona o global)
está activo **y** hay `thumbnail_url` o `fallback_image`; si no, la card sale en
**modo texto** sin hueco. En runtime, `rh-images.js` reacciona al `onerror` y
aplica el modo (*usar reserva* / *ocultar imagen* / *ocultar card*), de forma que
**nunca** se ve una imagen rota.

## Modelo de seguridad

- Sincronización recibe **solo texto estructurado**, saneado al entrar; nunca
  almacena ni ejecuta HTML/marcado/código.
- Toda salida se **escapa al renderizar** (`esc_html` / `esc_attr` / `esc_url`).
- Sin `eval`, sin código remoto, sin peticiones salientes (el único `fetch` del
  front es al propio `/render`).
- Escritura con **Contraseñas de aplicación** + `current_user_can('edit_posts')`.
  `/render` valida `format` contra whitelist y solo lee datos ya públicos.

## Compatibilidad

- Editor clásico (sin depender de Gutenberg).
- No emite JSON-LD/schema propio.
- Purga caché de Breeze tras cada sync (si existe).
- Carga solo `rh-theme.css` + el CSS/JS del formato en uso.

## Licencia

GPL-2.0-or-later (declarado en el encabezado del plugin).
