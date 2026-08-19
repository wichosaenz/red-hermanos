# Changelog

All notable changes to Red Hermanos are documented here. This project adheres to
[Semantic Versioning](https://semver.org/).

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
