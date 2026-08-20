# Red Hermanos — Artículos Relacionados entre Sitios

Plugin de WordPress que muestra **artículos relacionados de los sitios "hermanos"**
de una red editorial de 20 sitios. Red Hermanos es la **capa de visualización**
de un sistema mayor: **recibe** cada semana, vía REST API, el conjunto de
artículos hermanos recomendados (que le envía un flujo de n8n), los **almacena**
en su propia tabla y los **muestra** en el frontend con un bloque que se adapta
al tema activo.

> No consulta Pinecone ni genera recomendaciones — de eso se encarga el
> orquestador externo. Es únicamente la capa de *recepción + display*,
> equivalente funcional a *Jetpack Related Posts* pero entre sitios afiliados del
> mismo grupo. Desde la v1.2 el plugin **sí emite su propio bloque JSON-LD
> separado** (`ItemList` + `WebPage.relatedLink`) que declara los artículos
> hermanos como contenido relacionado, sin tocar el structured data que inyecta
> el orquestador.

- **PHP:** 7.4 – 8.3
- **WordPress:** 5.8+ (probado en 6.x y 7.x)
- **Sin dependencias:** sin Composer, sin jQuery (en el front), sin CSS/JS
  externos, sin CDNs.

---

## Descarga rápida

Ya no necesitas compilar nada: el archivo **[`red-hermanos.zip`](./red-hermanos.zip)**
está en la raíz de este repositorio, listo para instalar.

1. Descarga **`red-hermanos.zip`** (botón *Download raw file* sobre el archivo, o
   clona el repo).
2. En WordPress: **Plugins → Añadir nuevo → Subir plugin** → selecciona el
   `.zip` → **Instalar ahora** → **Activar**.

> ⚠️ **No uses** el botón verde **«Code → Download ZIP»** de GitHub: ese envuelve
> el repositorio en una carpeta extra y WordPress no encuentra el plugin. Usa el
> `red-hermanos.zip` de la raíz.

---

## Arquitectura

```
n8n (WF4 — Red Hermanos Distributor)  --POST /sync-->  [ tabla red_hermanos ]
                                                              |
   frontend  <--  RH_Renderer::render($args)  <--------------+
   (placements / widget / shortcode / template tag / endpoint /render)
```

Todos los puntos de entrada pasan por un **único motor de render**
(`RH_Renderer::render()`), de modo que las cinco vías de colocación comparten
exactamente la misma lógica de renderizado y de control de imágenes:

1. **Widget** — widget *Red Hermanos* para cualquier área de sidebar o footer.
2. **Placements** — auto-inserción por hook: antes/después del contenido,
   cabecera (`wp_body_open`) y pie (`wp_footer`), cada zona con sus propias
   opciones.
3. **Shortcode** — `[red_hermanos]`.
4. **Template tag** — `red_hermanos( $args )` para archivos del tema.
5. **Endpoint de render** — `/render` público de solo lectura + `rh-embed.js`
   para carga bajo demanda (por ejemplo, para saltarse el caché de página en
   zonas que quieras "frescas").

---

## Instalación

### A) Subir el ZIP (recomendado)

Usa el **`red-hermanos.zip`** de la raíz del repositorio (ver *Descarga rápida*).
En wp-admin: **Plugins → Añadir nuevo → Subir plugin** → elige el archivo →
**Instalar ahora** → **Activar**.

### B) Clonar con Git

```bash
cd wp-content/plugins/
git clone https://github.com/wichosaenz/red-hermanos.git
# El plugin vive en red-hermanos/red-hermanos/ — súbelo un nivel:
mv red-hermanos/red-hermanos ./rh-tmp && rm -rf red-hermanos && mv rh-tmp red-hermanos
```

Al activar, el plugin crea la tabla `{prefix}red_hermanos` y siembra las opciones
por defecto.

### C) Reconstruir el ZIP tú mismo

```bash
./build.sh   # genera red-hermanos.zip con la estructura correcta
```

---

## Configuración

1. **Crea una Contraseña de aplicación** para un usuario con capacidad
   `edit_posts`: *Usuarios → Perfil → Contraseñas de aplicación*. n8n la usa como
   Basic Auth.
2. Abre **Red Hermanos** en el menú del admin:
   - **General** — formato por defecto, número de cards, encabezado, color de
     acento (vacío = heredar del tema) y la sección **Imágenes** (mostrar
     miniaturas, imagen de reserva, comportamiento ante imágenes rotas).
   - **Placements** — activa/configura cada zona (antes/después del contenido,
     cabecera, pie) con overrides por zona y previsualización en vivo.
   - **Widgets & Shortcode** — fragmentos copiables.
   - **Status** — información de salud, URLs de endpoints, prueba de `/status` y
     purga de caché.
   - **Tools** — previsualiza cada formato.

---

## Contrato de datos (`POST /wp-json/red-hermanos/v1/sync`)

Autenticación Basic Auth (Contraseña de aplicación). Cuerpo JSON (nombres de
campo en español, tal como los envía n8n):

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

`thumbnail_url` puede venir vacío — el render degrada con elegancia (sin imagen
rota).

### Ejemplo con `curl`

```bash
curl -X POST "https://sitio.com/wp-json/red-hermanos/v1/sync" \
  -u "usuario:CONTRASENA_DE_APLICACION" \
  -H "Content-Type: application/json" \
  -d '{"semana_iso":"2026-W33","articulos":[{"post_url":"https://hermano.com/a/","post_title":"…","site_url":"https://hermano.com","site_name":"Hermano","posicion":1}]}'
```

### Otros endpoints

- `GET /wp-json/red-hermanos/v1/status` (con auth) — devuelve
  `{ plugin_version, active_articles, latest_sync, current_week, site_url }`.
- `GET /wp-json/red-hermanos/v1/render?format=ticker&count=5&thumbs=0` (público,
  solo lectura) — devuelve `{ format, count, html }` con marcado ya escapado.

---

## Formatos disponibles

- `cards_grid` — principal, completamente pulido (grid responsive 3→2→1).
- `ticker`, `carousel`, `marquee`, `in_post` — funcionales pero mínimos en la
  v1.0 (marcados con `// TODO v1.1`); renderizan sin romper.

---

## Control de imágenes (transversal)

Una miniatura solo se muestra si, **en cascada**, `show_thumbnails` (de la zona o
el global) está activo **y** hay `thumbnail_url` o `fallback_image`. Si no, la
card se renderiza en **modo texto** sin hueco de imagen.

- **Toggle maestro** «Mostrar miniaturas» (estilo Jetpack).
- **Imagen de reserva** configurable.
- **Comportamiento ante imagen rota:** *usar reserva* / *ocultar imagen (card de
  solo texto)* / *ocultar la card completa*.

En tiempo de ejecución, `rh-images.js` reacciona al `onerror` de cada `<img>` y
aplica el modo elegido, de modo que **nunca** se ve una imagen rota, aunque el
dato venga mal.

---

## Modelo de seguridad

- El endpoint de sincronización recibe **solo campos de texto estructurados** —
  se sanean **al entrar** (`sanitize_text_field` / `sanitize_textarea_field` /
  `esc_url_raw`). Nunca almacena ni ejecuta HTML/marcado/código.
- Toda la salida al frontend se construye desde esos campos y se **escapa al
  renderizar** (`esc_html` / `esc_attr` / `esc_url`).
- Sin `eval`, sin código remoto, sin peticiones salientes (el único `fetch` del
  front es al propio endpoint `/render` del mismo sitio).
- Los endpoints de escritura usan **Contraseñas de aplicación** nativas +
  `current_user_can('edit_posts')`. El endpoint público `/render` valida
  `format` contra una whitelist y expone solo datos ya públicos.
- `$wpdb->prepare` en toda consulta con variables; `$wpdb->insert/update` con
  arrays de formato.

---

## Notas de compatibilidad

- Funciona con el **editor clásico** (no depende de Gutenberg ni `block.json`).
- **Emite su propio JSON-LD** en `wp_footer` (prioridad 5), solo en entradas
  individuales, como bloque separado del structured data externo (v1.2+).
- **Enlaces dofollow**: sin `target="_blank"` y sin `rel` (backlinks editoriales).
- **Auto-actualizaciones desde GitHub** (Plugin Update Checker vendorizado).
- Purga el caché de **Breeze** tras cada sincronización, si está presente.
- Carga **solo** `rh-theme.css` + el CSS/JS del formato realmente en uso.

---

## Desarrollo

| Archivo | Rol |
|---------|-----|
| `red-hermanos.php` | Punto de entrada: header, constantes, hooks, template tag |
| `includes/class-rh-settings.php` | Fuente única de verdad: defaults, cascada, sanitización |
| `includes/class-rh-activator.php` | Crea la tabla (`dbDelta`) y siembra opciones |
| `includes/class-rh-rest-api.php` | Endpoints `/sync`, `/status`, `/render` |
| `includes/class-rh-renderer.php` | Motor de render + resolución de imagen + enqueue |
| `includes/class-rh-placements.php` | Auto-inserción por hooks |
| `includes/class-rh-widget.php` | Widget clásico para sidebar/footer |
| `includes/class-rh-admin.php` | GUI con pestañas (Settings API) |
| `includes/class-rh-shortcodes.php` | Shortcode `[red_hermanos]` |
| `templates/` | Plantillas de cada formato + partial de card |
| `assets/` | CSS (tokens + por formato) y JS (imágenes, embed, carousel, ticker, admin) |

Compilar el ZIP instalable:

```bash
./build.sh   # produce red-hermanos.zip (primer nivel: red-hermanos/red-hermanos.php)
```

## Licencia

GPL-2.0-or-later (declarado en el encabezado del plugin).
