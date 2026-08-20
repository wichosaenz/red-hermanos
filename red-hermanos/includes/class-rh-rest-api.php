<?php
/**
 * REST API — /sync (POST, auth), /status (GET, auth), /render (GET, public).
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Rest_API.
 */
class RH_Rest_API {

	const NAMESPACE = 'red-hermanos/v1';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
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
	 * Register the three routes.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/sync',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'handle_sync' ),
				'permission_callback' => array( __CLASS__, 'verify_sync_auth' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'get_status' ),
				'permission_callback' => array( __CLASS__, 'verify_sync_auth' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/render',
			array(
				'methods'             => 'GET',
				'callback'            => array( __CLASS__, 'handle_render' ),
				// Public, read-only: exposes only already-public data and returns
				// pre-escaped markup built by the plugin (never remote HTML).
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Auth for write/status endpoints. WordPress has already authenticated the
	 * Basic Auth (Application Password) and set the current user before this
	 * callback runs, so we only verify capability. No embedded secrets.
	 *
	 * @param WP_REST_Request $request Request (unused).
	 * @return bool
	 */
	public static function verify_sync_auth( $request ) {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * POST /sync — receive the weekly set of sibling articles.
	 *
	 * SECURITY: the payload is STRUCTURED TEXT ONLY. Every field is sanitized
	 * here on the way IN (esc_url_raw / sanitize_text_field /
	 * sanitize_textarea_field / floatval / intval). Any tag that arrives in a
	 * field is neutralized. Nothing is ever executed or stored as markup.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function handle_sync( $request ) {
		global $wpdb;

		$params = $request->get_json_params();

		$semana    = isset( $params['semana_iso'] ) ? sanitize_text_field( $params['semana_iso'] ) : '';
		$articulos = isset( $params['articulos'] ) && is_array( $params['articulos'] ) ? $params['articulos'] : array();

		if ( '' === $semana || empty( $articulos ) ) {
			return new WP_Error(
				'rh_bad_request',
				__( 'Missing or invalid semana_iso / articulos.', 'red-hermanos' ),
				array( 'status' => 400 )
			);
		}

		$table = self::table();

		// Step 1: deactivate previous active rows (keep them for fallback if a
		// future sync fails; we do NOT delete here).
		$wpdb->query( "UPDATE {$table} SET activo = 0 WHERE activo = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Step 2: insert each article, fully sanitized.
		$fecha_sync = current_time( 'mysql' );
		$inserted   = 0;

		// Resilience: only write site_icon_url if the column exists (it may not
		// on a site whose migration has not completed). Without this guard a
		// missing column would fail EVERY insert and leave the widget empty.
		$has_icon_col = RH_Activator::has_site_icon_column();

		foreach ( $articulos as $index => $art ) {
			if ( ! is_array( $art ) ) {
				continue;
			}

			$post_url = isset( $art['post_url'] ) ? esc_url_raw( $art['post_url'] ) : '';
			$title    = isset( $art['post_title'] ) ? sanitize_text_field( $art['post_title'] ) : '';

			// A row without a URL or title is not renderable — skip it.
			if ( '' === $post_url || '' === $title ) {
				continue;
			}

			// Excerpt: filter known junk values. When n8n pulls posts from the
			// WP REST API with ?_embed, image-size names ("full", "thumbnail",
			// "medium"…) can leak into the excerpt field. clean_excerpt() also
			// drops values <= 10 chars, so the literal word "full" never renders
			// under a card title. (Same helper runs again at render time.)
			$excerpt = RH_Renderer::clean_excerpt(
				isset( $art['post_excerpt'] ) ? sanitize_textarea_field( $art['post_excerpt'] ) : ''
			);

			// Thumbnail: keep any real absolute http(s) URL. We deliberately do
			// NOT require a file extension — modern WordPress/CDN thumbnail URLs
			// are often extension-less or carry query strings, and an over-strict
			// check here previously wiped valid images and emptied the widget.
			$thumb = isset( $art['thumbnail_url'] ) && '' !== $art['thumbnail_url'] ? esc_url_raw( $art['thumbnail_url'] ) : '';
			if ( '' !== $thumb && ! preg_match( '#^https?://#i', $thumb ) ) {
				$thumb = ''; // Not an absolute URL (e.g. a stray "full") — discard.
			}

			$data = array(
				'post_url'         => $post_url,
				'post_title'       => $title,
				'post_excerpt'     => $excerpt,
				'site_url'         => isset( $art['site_url'] ) ? esc_url_raw( $art['site_url'] ) : '',
				'site_name'        => isset( $art['site_name'] ) ? sanitize_text_field( $art['site_name'] ) : '',
				'vertical'         => isset( $art['vertical'] ) ? sanitize_text_field( $art['vertical'] ) : '',
				'researcher'       => isset( $art['researcher'] ) ? sanitize_text_field( $art['researcher'] ) : '',
				'thumbnail_url'    => $thumb,
				'site_icon_url'    => isset( $art['site_icon_url'] ) ? esc_url_raw( $art['site_icon_url'] ) : '',
				'similarity_score' => isset( $art['similarity_score'] ) ? floatval( $art['similarity_score'] ) : 0,
				'topic_tag'        => isset( $art['topic_tag'] ) ? sanitize_text_field( $art['topic_tag'] ) : '',
				'semana_iso'       => $semana,
				'fecha_sync'       => $fecha_sync,
				'formato_asignado' => isset( $art['formato_asignado'] ) ? RH_Settings::clean_format( $art['formato_asignado'] ) : 'cards_grid',
				'posicion'         => isset( $art['posicion'] ) ? intval( $art['posicion'] ) : ( (int) $index + 1 ),
				'activo'           => 1,
			);

			// Drop the icon field when the column is absent (see above).
			if ( ! $has_icon_col ) {
				unset( $data['site_icon_url'] );
			}

			// Let $wpdb->insert infer formats (all %s; MySQL coerces numeric
			// columns). This keeps insertion robust even when a key is omitted.
			$ok = $wpdb->insert( $table, $data ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( false !== $ok ) {
				++$inserted;
			}
		}

		// Step 3: purge history older than 4 weeks.
		$cutoff = gmdate( 'o-\WW', strtotime( '-4 weeks' ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE semana_iso < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		// Step 4: purge Breeze cache if present.
		if ( function_exists( 'breeze_clear_all_cache' ) ) {
			breeze_clear_all_cache();
		}

		return rest_ensure_response(
			array(
				'success'    => true,
				'semana'     => $semana,
				'inserted'   => $inserted,
				'total_sent' => count( $articulos ),
				'timestamp'  => $fecha_sync,
			)
		);
	}

	/**
	 * GET /status — health-check for n8n.
	 *
	 * @param WP_REST_Request $request Request (unused).
	 * @return WP_REST_Response
	 */
	public static function get_status( $request ) {
		global $wpdb;
		$table = self::table();

		$active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE activo = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$latest = $wpdb->get_var( "SELECT MAX(fecha_sync) FROM {$table} WHERE activo = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$week   = $wpdb->get_var( "SELECT semana_iso FROM {$table} WHERE activo = 1 ORDER BY fecha_sync DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		return rest_ensure_response(
			array(
				'plugin_version'  => RH_VERSION,
				'active_articles' => $active,
				'latest_sync'     => $latest ? $latest : null,
				'current_week'    => $week ? $week : null,
				'site_url'        => home_url(),
			)
		);
	}

	/**
	 * GET /render — public, read-only. Returns pre-escaped markup for any
	 * format so a block can be rendered on demand (including via JS embed).
	 *
	 * SECURITY: params are strictly sanitized; format is validated against a
	 * whitelist so no arbitrary include path can be built. Output is escaped
	 * inside the template. Only already-public data is read.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function handle_render( $request ) {
		$format   = RH_Settings::clean_format( $request->get_param( 'format' ) );
		$count    = RH_Settings::clamp_count( $request->get_param( 'count' ) );
		$vertical = RH_Settings::clean_vertical( $request->get_param( 'vertical' ) );

		$globals = RH_Settings::globals();

		$args = array(
			'format'          => $format,
			'count'           => $count,
			'vertical_filter' => $vertical,
			'zone'            => 'embed',
		);

		// Optional thumbs override for this call.
		$thumbs = $request->get_param( 'thumbs' );
		if ( null !== $thumbs && '' !== $thumbs ) {
			$args['show_thumbnails'] = ( '1' === (string) $thumbs || 1 === $thumbs || true === $thumbs ) ? 1 : 0;
		}

		// Optional heading override.
		$heading = $request->get_param( 'heading' );
		if ( null !== $heading && '' !== $heading ) {
			$args['heading_text'] = sanitize_text_field( $heading );
		}

		$html = RH_Renderer::render( $args );

		// Soft cache headers — data rotates weekly, 5 min is safe and keeps the
		// DB cool. Compatible with Breeze / host cache.
		if ( ! headers_sent() ) {
			header( 'Cache-Control: public, max-age=300' );
		}

		return rest_ensure_response(
			array(
				'format' => $format,
				'count'  => $count,
				'html'   => $html,
			)
		);
	}
}
