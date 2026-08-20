<?php
/**
 * JSON-LD injection.
 *
 * Emits structured data declaring the sibling articles as editorially related
 * content, to enrich SEO/GEO. This is the plugin's OWN, SEPARATE block — it does
 * not touch the article HTML or the externally-injected "Block 0" structured
 * data (R-LD-1).
 *
 * Rules (see R-LD in the brief):
 *   R-LD-1: separate <script> block, independent of Block 0.
 *   R-LD-2: ItemList > ListItem > Article per sibling article.
 *   R-LD-3: WebPage.relatedLink with the sibling URLs.
 *   R-LD-4: injected on wp_footer, priority 5.
 *   R-LD-5: wp_json_encode() with JSON_UNESCAPED_SLASHES.
 *   R-LD-6: only on is_singular('post').
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_JsonLd.
 */
class RH_JsonLd {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_footer', array( __CLASS__, 'output' ), 5 );
	}

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	private static function table() {
		global $wpdb;
		return $wpdb->prefix . RH_TABLE_NAME;
	}

	/**
	 * Fetch active sibling articles for the current page.
	 *
	 * @return array Row objects.
	 */
	private static function articles() {
		global $wpdb;
		$table = self::table();

		$g       = RH_Settings::globals();
		$limit   = max( 1, min( 10, (int) $g['cards_count'] ) );
		$verticl = $g['vertical_filter'];

		if ( '' !== $verticl ) {
			$sql = $wpdb->prepare(
				"SELECT post_url, post_title, thumbnail_url, site_name FROM {$table} WHERE activo = 1 AND vertical = %s ORDER BY posicion ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$verticl,
				$limit
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT post_url, post_title, thumbnail_url, site_name FROM {$table} WHERE activo = 1 ORDER BY posicion ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$limit
			);
		}

		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Build and print the JSON-LD block.
	 *
	 * @return void
	 */
	public static function output() {
		// R-LD-6: single posts only.
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$articles = self::articles();
		if ( empty( $articles ) ) {
			return;
		}

		$page_url = get_permalink();

		// R-LD-2: ItemList of ListItem > Article.
		$list_items    = array();
		$related_links = array();
		$position      = 1;

		foreach ( $articles as $article ) {
			$url = isset( $article->post_url ) ? $article->post_url : '';
			if ( '' === $url ) {
				continue;
			}

			$item_article = array(
				'@type'    => 'Article',
				'url'      => $url,
				'headline' => isset( $article->post_title ) ? $article->post_title : '',
			);

			if ( ! empty( $article->site_name ) ) {
				$item_article['publisher'] = array(
					'@type' => 'Organization',
					'name'  => $article->site_name,
				);
			}

			if ( ! empty( $article->thumbnail_url ) ) {
				$item_article['image'] = $article->thumbnail_url;
			}

			$list_items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'item'     => $item_article,
			);

			$related_links[] = $url;
			++$position;
		}

		if ( empty( $list_items ) ) {
			return;
		}

		// R-LD-1/3: our own @graph with a WebPage (relatedLink) + ItemList,
		// entirely separate from any other structured data on the page.
		$graph = array(
			'@context' => 'https://schema.org',
			'@graph'   => array(
				array(
					'@type'       => 'WebPage',
					'@id'         => $page_url . '#rh-related',
					'url'         => $page_url,
					'relatedLink' => $related_links,
				),
				array(
					'@type'           => 'ItemList',
					'name'            => 'Related articles from sibling sites',
					'itemListElement' => $list_items,
				),
			),
		);

		// R-LD-5: unescaped slashes (plus unescaped unicode for clean output).
		// JSON_HEX_TAG escapes < and > as </> so a stray "</script>"
		// in any field can never break out of the script element.
		$json = wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );
		if ( false === $json ) {
			return;
		}

		echo "\n<script type=\"application/ld+json\" data-rh-jsonld>" . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
