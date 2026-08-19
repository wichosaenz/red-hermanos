# Red Hermanos — Cross-Site Related Posts

Display layer for a legitimate editorial network of 20 WordPress sites. Red
Hermanos **receives** a weekly set of recommended sibling articles (pushed by an
n8n workflow via REST API), **stores** them in its own table, and **renders**
them on the front end in a theme-adaptive block.

It does **not** query Pinecone, generate recommendations, or emit any JSON-LD /
schema — structured data is injected elsewhere by the orchestrator. This plugin
is purely the display + receive layer, functionally equivalent to Jetpack
Related Posts but across affiliated sites of the same group.

- **PHP:** 7.4 – 8.3
- **WordPress:** 5.8+ (tested with 6.x and 7.x)
- **No dependencies:** no Composer, no jQuery, no external CSS/JS, no CDNs.

## Architecture

```
n8n (WF4 — Red Hermanos Distributor)  --POST /sync-->  [ red-hermanos table ]
                                                              |
   front end  <--  RH_Renderer::render($args)  <-------------+
   (placements / widget / shortcode / template tag / /render endpoint)
```

Every entry point funnels through a single args-driven engine
(`RH_Renderer::render()`), so the five placement methods share the exact same
rendering and image-handling logic:

1. **Widget** — `Red Hermanos` widget for any sidebar/footer area.
2. **Placements** — auto-insert by hook: before/after content, header
   (`wp_body_open`), footer (`wp_footer`), each with per-zone options.
3. **Shortcode** — `[red_hermanos]`.
4. **Template tag** — `red_hermanos( $args )` for theme files.
5. **Render endpoint** — public read-only `/render` + `rh-embed.js` for on-demand
   loading (e.g. to bypass page cache in "fresh" zones).

## Installation

**A) Upload the ZIP (recommended)**

Get `red-hermanos.zip` in any of these ways:

- **From a GitHub Release** — the `v*` tags publish a Release with the installable
  `red-hermanos.zip` attached (permanent download link).
- **From GitHub Actions** — every push runs the *Build plugin ZIP* workflow; open
  the run and download the `red-hermanos-plugin` artifact.
- **Locally** — run `./build.sh` → produces `red-hermanos.zip`.

> Do **not** use GitHub's green **Code → Download ZIP** button: it nests the repo
> in an extra folder, so WordPress can't find the plugin header. Use one of the
> options above.

Then in wp-admin: **Plugins → Add New → Upload Plugin** → choose
`red-hermanos.zip` → **Install Now** → **Activate**.

**B) Git clone**

```bash
cd wp-content/plugins/
git clone https://github.com/wichosaenz/red-hermanos.git
# The plugin lives in red-hermanos/red-hermanos/ — move it up one level:
mv red-hermanos/red-hermanos ./red-hermanos-plugin && rm -rf red-hermanos && mv red-hermanos-plugin red-hermanos
```

On activation the plugin creates the `{prefix}red_hermanos` table and seeds
default options.

## Configuration

1. **Create an Application Password** for a user with `edit_posts` capability:
   *Users → Profile → Application Passwords*. n8n uses it as Basic Auth.
2. Open **Red Hermanos** in the admin menu:
   - **General** — default format, count, heading, accent color (empty =
     inherit theme), and the **Images** section (show thumbnails, fallback
     image, broken-image behavior).
   - **Placements** — enable/configure each zone (after/before content,
     header, footer) with per-zone overrides + live preview.
   - **Widgets & Shortcode** — copyable snippets.
   - **Status** — health info + endpoint URLs, `/status` test, cache purge.
   - **Tools** — preview each format.

## Data contract (`POST /wp-json/red-hermanos/v1/sync`)

Basic Auth (Application Password). Body (Spanish field names, exactly as n8n
sends them):

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

Response: `{ success, semana, inserted, total_sent, timestamp }`.

`thumbnail_url` may be empty — the render degrades gracefully (no broken image).

### Example `curl`

```bash
curl -X POST "https://sitio.com/wp-json/red-hermanos/v1/sync" \
  -u "usuario:APPLICATION_PASSWORD" \
  -H "Content-Type: application/json" \
  -d '{"semana_iso":"2026-W33","articulos":[{"post_url":"https://hermano.com/a/","post_title":"…","site_url":"https://hermano.com","site_name":"Hermano","posicion":1}]}'
```

### Other endpoints

- `GET /wp-json/red-hermanos/v1/status` (auth) — `{ plugin_version,
  active_articles, latest_sync, current_week, site_url }`.
- `GET /wp-json/red-hermanos/v1/render?format=ticker&count=5&thumbs=0` (public,
  read-only) — returns `{ format, count, html }` with pre-escaped markup.

## Formats

- `cards_grid` — primary, fully polished (3→2→1 responsive grid).
- `ticker`, `carousel`, `marquee`, `in_post` — functional-but-minimal in v1.0
  (marked `// TODO v1.1`); they render without breaking.

## Security model

- Sync receives **structured text only** — sanitized on the way in
  (`sanitize_text_field` / `sanitize_textarea_field` / `esc_url_raw`). Never
  stores or executes HTML/markup/code.
- All front-end output is built from those fields and **escaped** on render
  (`esc_html` / `esc_attr` / `esc_url`).
- No `eval`, no remote code, no outbound requests (the only front-end `fetch` is
  to this site's own `/render`).
- Write endpoints use native **Application Passwords** +
  `current_user_can('edit_posts')`. The public `/render` endpoint validates
  `format` against a whitelist and exposes only already-public data.

## Compatibility notes

- Works with the **classic editor** (no Gutenberg/block.json dependency).
- Emits **no JSON-LD / schema** (avoids duplicating externally-injected data).
- Purges **Breeze** cache after each sync when present.
- Loads only `rh-theme.css` + the CSS/JS of the format actually in use.
