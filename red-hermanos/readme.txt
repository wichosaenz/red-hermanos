=== Red Hermanos — Cross-Site Related Posts ===
Contributors: everest-ecosystem
Tags: related posts, cross-site, backlinks, SEO, structured data
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Displays related articles from 19 sibling sites in a WordPress editorial network. Generates dofollow backlinks and enriches JSON-LD structured data for SEO and GEO optimization.

== Description ==

Red Hermanos is the display layer of a cross-site content distribution system. It receives weekly recommendations from an n8n workflow (powered by Pinecone vector search) and renders them as related article widgets with full SEO backlink value.

Features:

* Cross-site article recommendations from 19 sibling sites
* Dofollow editorial backlinks for Domain Authority growth
* JSON-LD structured data (ItemList + WebPage.relatedLink)
* 5 display formats: cards grid, ticker, carousel, marquee, in-post
* Weekly rotation aligned with Mon-Fri publishing cycle
* REST API endpoint for n8n orchestration
* WordPress native auto-updates from GitHub

== Installation ==

1. Upload the plugin ZIP via Plugins > Add New > Upload Plugin, or clone into wp-content/plugins/.
2. Activate the plugin. The storage table and default options are created automatically.
3. Create a WordPress Application Password for a user with the edit_posts capability; the n8n workflow uses it as Basic Auth for the /sync endpoint.
4. Configure display options under the Red Hermanos admin menu.

== Changelog ==

= 1.2.0 =
* Fixed: "full" text appearing in cards (WordPress image size leaking)
* Added: Site name with favicon displayed below article title
* Added: GitHub-based auto-updates via Plugin Update Checker
* Added: Filter that skips sibling articles without a featured image
* Improved: Excerpt validation (filters invalid image-size strings)
* Security: Repository moved to private

= 1.1.0 =
* Added: JSON-LD injection (ItemList + WebPage.relatedLink)
* Fixed: Links now dofollow without target="_blank" or rel="noopener"
* Improved: Semantic HTML structure (nav > article > h4 > a)
* Added: Card click handler via JavaScript (SEO-safe)

= 1.0.0 =
* Initial release
* REST API sync endpoint
* Cards grid display format
* Admin settings page

== Upgrade Notice ==

= 1.2.0 =
Bug fixes and auto-update support. Update recommended for all sites.
