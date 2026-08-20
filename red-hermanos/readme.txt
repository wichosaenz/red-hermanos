=== Red Hermanos — Cross-Site Related Posts ===
Contributors: everest-ecosystem
Tags: related posts, cross-site, backlinks, SEO, structured data
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays related articles from 19 sibling sites in a WordPress editorial network with dofollow editorial backlinks.

== Description ==

Red Hermanos is the display layer of a cross-site content distribution system. It receives weekly recommendations from an n8n workflow (powered by Pinecone vector search) and renders them as related article widgets.

Features:

* Cross-site article recommendations from 19 sibling sites
* Dofollow editorial backlinks for Domain Authority growth
* Site name + favicon shown under each article title
* 5 display formats: cards grid, ticker, carousel, marquee, in-post
* Weekly rotation aligned with Mon-Fri publishing cycle
* REST API endpoint for n8n orchestration
* WordPress native auto-updates from GitHub
* Emits no JSON-LD of its own — the source content already carries its metadata

== Installation ==

1. Upload the plugin ZIP via Plugins > Add New > Upload Plugin, or clone into wp-content/plugins/.
2. Activate the plugin. The storage table and default options are created automatically.
3. Create a WordPress Application Password for a user with the edit_posts capability; the n8n workflow uses it as Basic Auth for the /sync endpoint.
4. Configure display options under the Red Hermanos admin menu.

== Changelog ==

= 1.2.1 =
* Fixed (critical): widgets rendered empty when active articles had no featured image; the renderer now prefers image cards but never returns an empty widget (falls back to text-only cards)
* Fixed: sync no longer discards valid extension-less / CDN thumbnail URLs
* Fixed: "full" and other image-size tokens filtered on both input and output
* Fixed: cross-DB safe migration for the site_icon_url column
* Removed: JSON-LD output (source content already carries its own metadata)

= 1.2.0 =
* Added: Site name with favicon displayed below article title
* Added: GitHub-based auto-updates via Plugin Update Checker
* Fixed: "full" text appearing in cards (WordPress image size leaking)
* Changed: dofollow links (no target="_blank" / rel); semantic nav > article > h4 > a; JS card click

= 1.0.0 =
* Initial release
* REST API sync endpoint
* Cards grid display format
* Admin settings page

== Upgrade Notice ==

= 1.2.1 =
Critical fix: restores related-article rendering when articles lack featured images. Update strongly recommended.
