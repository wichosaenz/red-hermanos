<?php
/**
 * Renderer — the single args-driven render engine used by every entry point
 * (placements, widget, shortcode, endpoint, template tag). Also owns the
 * front-end asset enqueue.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Renderer.
 */
class RH_Renderer {

	/**
	 * Formats whose JS/CSS were requested on the current page. Populated as
	 * render() runs so enqueue can stay lean.
	 *
	 * @var array
	 */
	private static $used_formats = array();

	/**
	 * Whether any rendered zone shows images (controls rh-images.js enqueue).
	 *
	 * @var bool
	 */
	private static $needs_images_js = false;

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		// Enqueue late so anything rendered during the page (placements, widgets,
		// shortcodes) has registered its needs first.
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 99 );
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
	 * Merge caller args over the global defaults.
	 *
	 * @param array $args Caller args.
	 * @return array
	 */
	private static function parse_args( array $args ) {
		$g = RH_Settings::globals();

		$defaults = array(
			'format'            => $g['active_format'],
			'count'             => $g['cards_count'],
			'heading_text'      => $g['heading_text'],
			'accent_color'      => $g['accent_color'],
			'show_thumbnails'   => $g['show_thumbnails'],
			'fallback_image'    => $g['fallback_image'],
			'broken_image_mode' => $g['broken_image_mode'],
			'vertical_filter'   => $g['vertical_filter'],
			'open_new_tab'      => $g['open_new_tab'],
			'zone'              => 'default',
		);

		$args = wp_parse_args( $args, $defaults );

		// Normalize / re-validate everything (callers may pass raw values).
		$args['format']            = RH_Settings::clean_format( $args['format'] );
		$args['count']             = RH_Settings::clamp_count( $args['count'] );
		$args['heading_text']      = sanitize_text_field( $args['heading_text'] );
		$args['accent_color']      = RH_Settings::clean_color( $args['accent_color'] );
		$args['show_thumbnails']   = empty( $args['show_thumbnails'] ) ? 0 : 1;
		$args['fallback_image']    = '' !== $args['fallback_image'] ? esc_url_raw( $args['fallback_image'] ) : '';
		$args['broken_image_mode'] = RH_Settings::clean_broken_mode( $args['broken_image_mode'] );
		$args['vertical_filter']   = RH_Settings::clean_vertical( $args['vertical_filter'] );
		$args['open_new_tab']      = empty( $args['open_new_tab'] ) ? 0 : 1;
		$args['zone']              = sanitize_key( $args['zone'] );

		return $args;
	}

	/**
	 * Query active articles for the given args.
	 *
	 * @param array $args Parsed args.
	 * @return array Array of row objects.
	 */
	private static function query( array $args ) {
		global $wpdb;
		$table = self::table();

		if ( '' !== $args['vertical_filter'] ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE activo = 1 AND vertical = %s ORDER BY posicion ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$args['vertical_filter'],
				$args['count']
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE activo = 1 ORDER BY posicion ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$args['count']
			);
		}

		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Resolve the thumbnail decision for a single article given the args.
	 *
	 * Cascade:
	 *   1. show_thumbnails false -> text mode (no <img>, no image slot).
	 *   2. thumbnail_url present -> use it.
	 *   3. else fallback_image present -> use fallback.
	 *   4. else broken_image_mode 'hide_card' -> omit the card.
	 *
	 * @param object $article Row object.
	 * @param array  $args    Parsed args.
	 * @return array {
	 *     @type string $mode  'image' | 'text' | 'skip'
	 *     @type string $src   Image URL when mode is 'image'.
	 * }
	 */
	public static function resolve_thumb( $article, array $args ) {
		if ( empty( $args['show_thumbnails'] ) ) {
			return array(
				'mode' => 'text',
				'src'  => '',
			);
		}

		$thumb = isset( $article->thumbnail_url ) ? trim( (string) $article->thumbnail_url ) : '';
		if ( '' !== $thumb ) {
			return array(
				'mode' => 'image',
				'src'  => $thumb,
			);
		}

		if ( '' !== $args['fallback_image'] ) {
			return array(
				'mode' => 'image',
				'src'  => $args['fallback_image'],
			);
		}

		if ( 'hide_card' === $args['broken_image_mode'] ) {
			return array(
				'mode' => 'skip',
				'src'  => '',
			);
		}

		// No image available but card should still render as text.
		return array(
			'mode' => 'text',
			'src'  => '',
		);
	}

	/**
	 * Map a format to its template file (validated — never build from raw).
	 *
	 * @param string $format Validated format.
	 * @return string Absolute template path.
	 */
	private static function template_path( $format ) {
		$map = array(
			'cards_grid' => 'cards-grid.php',
			'ticker'     => 'ticker.php',
			'carousel'   => 'carousel.php',
			'marquee'    => 'marquee.php',
			'in_post'    => 'in-post-card.php',
		);

		$file = isset( $map[ $format ] ) ? $map[ $format ] : 'cards-grid.php';
		$path = RH_PLUGIN_DIR . 'templates/' . $file;

		if ( ! file_exists( $path ) ) {
			$path = RH_PLUGIN_DIR . 'templates/cards-grid.php';
		}

		return $path;
	}

	/**
	 * Main render entry point. Returns a string of already-escaped markup.
	 *
	 * @param array $args Caller args.
	 * @return string
	 */
	public static function render( array $args ) {
		$args     = self::parse_args( $args );
		$articles = self::query( $args );

		// Never render an empty section.
		if ( empty( $articles ) ) {
			return '';
		}

		// Track needs for enqueue.
		self::$used_formats[ $args['format'] ] = true;
		if ( ! empty( $args['show_thumbnails'] ) ) {
			self::$needs_images_js = true;
		}

		// Expose to template.
		$heading = $args['heading_text'];
		$accent  = $args['accent_color'];

		$template = self::template_path( $args['format'] );

		ob_start();
		// The template escapes ALL output. $articles / $args / $heading / $accent
		// are in scope for the include.
		include $template;
		return trim( (string) ob_get_clean() );
	}

	/**
	 * Helper for templates: build the container opening tag with the right
	 * classes and inline accent variable.
	 *
	 * @param array  $args   Parsed args.
	 * @param string $format Format slug for the modifier class.
	 * @return string
	 */
	public static function container_open( array $args, $format ) {
		$classes = array(
			'rh-related-posts',
			'rh-format--' . $format,
			'rh-zone--' . $args['zone'],
		);

		$style = '';
		if ( ! empty( $args['accent_color'] ) ) {
			$style = ' style="--rh-accent:' . esc_attr( $args['accent_color'] ) . '"';
		}

		return '<section class="' . esc_attr( implode( ' ', $classes ) ) . '"' . $style . '>';
	}

	/* ---------------------------------------------------------------------
	 * Assets.
	 * ------------------------------------------------------------------- */

	/**
	 * Register (but do not enqueue) every stylesheet/script so anything can be
	 * pulled in on demand.
	 *
	 * @return void
	 */
	public static function register_assets() {
		$css = RH_PLUGIN_URL . 'assets/css/';
		$js  = RH_PLUGIN_URL . 'assets/js/';

		wp_register_style( 'rh-theme', $css . 'rh-theme.css', array(), RH_VERSION );
		wp_register_style( 'rh-cards-grid', $css . 'rh-cards-grid.css', array( 'rh-theme' ), RH_VERSION );
		wp_register_style( 'rh-ticker', $css . 'rh-ticker.css', array( 'rh-theme' ), RH_VERSION );
		wp_register_style( 'rh-carousel', $css . 'rh-carousel.css', array( 'rh-theme' ), RH_VERSION );
		wp_register_style( 'rh-marquee', $css . 'rh-marquee.css', array( 'rh-theme' ), RH_VERSION );

		wp_register_script( 'rh-images', $js . 'rh-images.js', array(), RH_VERSION, true );
		wp_register_script( 'rh-embed', $js . 'rh-embed.js', array(), RH_VERSION, true );
		wp_register_script( 'rh-carousel', $js . 'rh-carousel.js', array(), RH_VERSION, true );
		wp_register_script( 'rh-ticker', $js . 'rh-ticker.js', array(), RH_VERSION, true );
	}

	/**
	 * Enqueue only what the current page actually needs.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		// Determine which formats may appear on this page. Server-rendered zones
		// populate self::$used_formats during render(); but enqueue can run
		// before some renders (widgets in footer). So also consult the settings
		// for enabled placements + the global active format as a safe superset.
		$formats = self::$used_formats;

		$g = RH_Settings::globals();
		$formats[ $g['active_format'] ] = true;

		foreach ( RH_Settings::placements() as $zone => $conf ) {
			if ( ! empty( $conf['enabled'] ) ) {
				$formats[ RH_Settings::clean_format( $conf['format'] ) ] = true;
			}
		}

		// Theme tokens are always needed if anything renders.
		wp_enqueue_style( 'rh-theme' );

		$style_map = array(
			'cards_grid' => 'rh-cards-grid',
			'in_post'    => 'rh-cards-grid', // in-post reuses the card styling.
			'ticker'     => 'rh-ticker',
			'carousel'   => 'rh-carousel',
			'marquee'    => 'rh-marquee',
		);

		foreach ( array_keys( $formats ) as $format ) {
			if ( isset( $style_map[ $format ] ) ) {
				wp_enqueue_style( $style_map[ $format ] );
			}
		}

		// Format-specific JS only when that format is in use.
		if ( isset( $formats['carousel'] ) ) {
			wp_enqueue_script( 'rh-carousel' );
		}
		if ( isset( $formats['ticker'] ) ) {
			wp_enqueue_script( 'rh-ticker' );
		}

		// Image runtime handler only when images may be shown.
		if ( self::$needs_images_js || ! empty( $g['show_thumbnails'] ) ) {
			wp_enqueue_script( 'rh-images' );
		}

		// Embed loader — lightweight, enqueue on singular/front where an embed
		// div or shortcode may appear.
		if ( is_singular() || is_front_page() || is_home() ) {
			wp_enqueue_script( 'rh-embed' );
			wp_localize_script(
				'rh-embed',
				'RH_EMBED',
				array(
					'endpoint' => esc_url_raw( rest_url( RH_Rest_API::NAMESPACE . '/render' ) ),
				)
			);
		}

		// Inline accent from the global setting (zone overrides use inline style
		// on their own container via container_open()).
		if ( ! empty( $g['accent_color'] ) ) {
			wp_add_inline_style(
				'rh-theme',
				'.rh-related-posts{--rh-accent:' . esc_attr( $g['accent_color'] ) . ';}'
			);
		}
	}

	/**
	 * Force the images JS to load (used by embed contexts / admin preview docs).
	 *
	 * @return void
	 */
	public static function flag_images_needed() {
		self::$needs_images_js = true;
	}
}
