# Changelog

All notable changes to Red Hermanos are documented here. This project adheres to
[Semantic Versioning](https://semver.org/).

## [1.2.0] — 2026-08-20

### Fixed
- The literal word **"full"** (and other WordPress image-size names leaking from
  `?_embed` responses) no longer appears under card titles. The REST endpoint
  discards known junk excerpt values, and templates only render an excerpt when
  it is non-empty and longer than 10 characters.

### Added
- **Site name + favicon** below each article title. New `site_icon_url` column
  (with an in-place `ALTER TABLE` migration for sites already on 1.1) accepted by
  the sync endpoint and rendered as a 16×16 favicon beside the sibling site name.
- **GitHub auto-updates** via the vendored Plugin Update Checker (v5.7, MIT). New
  sites detect GitHub releases and offer the native WordPress "update now" flow.
- **GitHub Access Token** field in the admin (General → Updates) for private-repo
  updates; current plugin version shown in the admin header and Updates section.
- **No-image safety net**: templates skip sibling articles without a valid
  featured image; the query over-fetches to still fill the configured count.

### Security / hardening
- Sync validates `thumbnail_url` looks like a real image URL before storing.
- JSON-LD encoded with `JSON_HEX_TAG` to prevent any `</script>` breakout.

## [1.1.0] — 2026-08-20

### Added
- **JSON-LD** structured data injected on `wp_footer` (priority 5), single posts
  only: an `ItemList` of `Article` items plus a `WebPage.relatedLink` list,
  emitted as its own block separate from any externally-injected structured data.
- Card **click handler** in vanilla JS (SEO-safe: crawlers still see only the
  single title anchor).

### Changed
- Links are **dofollow**: no `target="_blank"`, no `rel="nofollow|noopener|
  sponsored|ugc"`; absolute URLs.
- Semantic HTML: `<nav>` container → `<article>` per card → `<h4><a>` title,
  where the anchor wraps only the title.

## [1.0.0] — 2026-08-19

### Initial release

- Storage table `{prefix}red_hermanos` created via `dbDelta()` on activation,
  with indexes on `semana_iso`, `activo`, `formato_asignado`, `vertical`.
- REST API:
  - `POST /red-hermanos/v1/sync` (Application Passwords) — deactivates previous
    weeks, inserts sanitized rows, purges history older than 4 weeks, clears
    Breeze cache.
  - `GET /red-hermanos/v1/status` (auth) — health check.
  - `GET /red-hermanos/v1/render` (public, read-only) — pre-escaped markup for
    any format, with soft cache headers.
- Single args-driven render engine (`RH_Renderer`) shared by all five placement
  methods: widget, hook placements, shortcode, template tag, `/render` embed.
- Cascading image control: master "show thumbnails" toggle, fallback image, and
  broken-image behavior (`fallback` / `hide_image` / `hide_card`) enforced
  server-side and at runtime (`rh-images.js`).
- Tabbed admin GUI (General / Placements / Widgets & Shortcode / Status / Tools)
  with color picker, media picker, live preview, copyable snippets, `/status`
  test and cache purge.
- Theme-adaptive styling (YITH Proteo child): inherited typography, token-based
  color cascade with fallbacks, per-zone and sidebar variants.
- `cards_grid` fully polished; `ticker`, `carousel`, `marquee`, `in_post`
  functional-but-minimal (`// TODO v1.1`).
- i18n-ready (`red-hermanos` text domain) with a `.pot` template.
- Clean uninstall (drops table + options, multisite-aware).
