<?php
/**
 * Placements — auto-insertion by hooks: before/after content, header
 * (wp_body_open) and footer (wp_footer). Only enabled zones are hooked.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Placements.
 */
class RH_Placements {

	/**
	 * Hook registration — only enabled zones get wired up.
	 *
	 * @return void
	 */
	public static function init() {
		$placements = RH_Settings::placements();

		if ( ! empty( $placements['after_content']['enabled'] ) || ! empty( $placements['before_content']['enabled'] ) ) {
			add_filter( 'the_content', array( __CLASS__, 'filter_content' ), 99 );
		}

		if ( ! empty( $placements['header']['enabled'] ) ) {
			add_action( 'wp_body_open', array( __CLASS__, 'render_header' ) );
		}

		if ( ! empty( $placements['footer']['enabled'] ) ) {
			add_action( 'wp_footer', array( __CLASS__, 'render_footer' ) );
		}
	}

	/**
	 * Should a zone render on the current request, per its display_scope?
	 * Never in admin, feeds or REST.
	 *
	 * @param string $scope Display scope (single|all|front).
	 * @return bool
	 */
	private static function scope_matches( $scope ) {
		if ( is_admin() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}

		switch ( $scope ) {
			case 'single':
				return is_singular( 'post' );
			case 'front':
				return is_front_page() || is_home();
			case 'all':
			default:
				return ! is_admin();
		}
	}

	/**
	 * Wrap a zone's rendered markup in its zone container.
	 *
	 * @param string $zone Zone key.
	 * @param string $html Rendered markup (already escaped).
	 * @return string
	 */
	private static function wrap( $zone, $html ) {
		if ( '' === $html ) {
			return '';
		}
		return '<div class="rh-zone rh-zone--' . esc_attr( $zone ) . '">' . $html . '</div>';
	}

	/**
	 * the_content filter — prepend before_content and append after_content.
	 * Never touches the article HTML itself (external JSON-LD stays intact).
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_content( $content ) {
		// Only on the main singular post query in the loop.
		if ( ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$placements = RH_Settings::placements();
		$before     = '';
		$after      = '';

		if ( ! empty( $placements['before_content']['enabled'] ) && self::scope_matches( $placements['before_content']['display_scope'] ) ) {
			$before = self::wrap( 'before_content', RH_Renderer::render( RH_Settings::zone_args( 'before_content' ) ) );
		}

		if ( ! empty( $placements['after_content']['enabled'] ) && self::scope_matches( $placements['after_content']['display_scope'] ) ) {
			$after = self::wrap( 'after_content', RH_Renderer::render( RH_Settings::zone_args( 'after_content' ) ) );
		}

		return $before . $content . $after;
	}

	/**
	 * wp_body_open — render the header zone at the top of <body>.
	 *
	 * @return void
	 */
	public static function render_header() {
		$placements = RH_Settings::placements();
		if ( empty( $placements['header']['enabled'] ) || ! self::scope_matches( $placements['header']['display_scope'] ) ) {
			return;
		}
		echo self::wrap( 'header', RH_Renderer::render( RH_Settings::zone_args( 'header' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * wp_footer — render the footer zone.
	 *
	 * @return void
	 */
	public static function render_footer() {
		$placements = RH_Settings::placements();
		if ( empty( $placements['footer']['enabled'] ) || ! self::scope_matches( $placements['footer']['display_scope'] ) ) {
			return;
		}
		echo self::wrap( 'footer', RH_Renderer::render( RH_Settings::zone_args( 'footer' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
