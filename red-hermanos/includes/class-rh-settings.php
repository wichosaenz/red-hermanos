<?php
/**
 * Settings — single source of truth for options, defaults, the option
 * cascade (global -> zone) and sanitization callbacks.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Settings.
 */
class RH_Settings {

	const OPT_GLOBALS    = 'rh_globals';
	const OPT_PLACEMENTS = 'rh_placements';
	const OPT_DB_VERSION = 'rh_db_version';

	/**
	 * Whitelist of valid render formats. Used everywhere a format is validated
	 * so a request param can never build an arbitrary include path.
	 *
	 * @return string[]
	 */
	public static function formats() {
		return array( 'cards_grid', 'ticker', 'carousel', 'marquee', 'in_post' );
	}

	/**
	 * Whitelist of network verticals.
	 *
	 * @return string[]
	 */
	public static function verticals() {
		return array( 'automotive', 'finance', 'health', 'technology' );
	}

	/**
	 * Whitelist of broken-image behaviors.
	 *
	 * @return string[]
	 */
	public static function broken_image_modes() {
		return array( 'fallback', 'hide_image', 'hide_card' );
	}

	/**
	 * Whitelist of display scopes for placements.
	 *
	 * @return string[]
	 */
	public static function display_scopes() {
		return array( 'single', 'all', 'front' );
	}

	/**
	 * Default global settings.
	 *
	 * @return array
	 */
	public static function default_globals() {
		return array(
			'active_format'     => 'cards_grid',
			'cards_count'       => 3,
			'heading_text'      => 'From Our Network',
			'accent_color'      => '',
			'show_thumbnails'   => 1,
			'fallback_image'    => '',
			'broken_image_mode' => 'fallback',
			'vertical_filter'   => '',
			'open_new_tab'      => 1,
		);
	}

	/**
	 * Default placement zones. Each zone inherits from globals and may override
	 * any field. Only after_content is enabled by default.
	 *
	 * @return array
	 */
	public static function default_placements() {
		return array(
			'after_content'  => array(
				'enabled'       => 1,
				'format'        => 'cards_grid',
				'count'         => 3,
				'heading_text'  => 'From Our Network',
				'display_scope' => 'single',
			),
			'before_content' => array(
				'enabled'       => 0,
				'format'        => 'cards_grid',
				'count'         => 3,
				'heading_text'  => 'From Our Network',
				'display_scope' => 'single',
			),
			'header'         => array(
				'enabled'       => 0,
				'format'        => 'ticker',
				'count'         => 6,
				'heading_text'  => 'From Our Network',
				'display_scope' => 'all',
			),
			'footer'         => array(
				'enabled'       => 0,
				'format'        => 'cards_grid',
				'count'         => 3,
				'heading_text'  => 'From Our Network',
				'display_scope' => 'all',
			),
		);
	}

	/**
	 * Convenience: both default arrays plus db version.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			self::OPT_GLOBALS    => self::default_globals(),
			self::OPT_PLACEMENTS => self::default_placements(),
			self::OPT_DB_VERSION => RH_VERSION,
		);
	}

	/**
	 * Read global options merged over defaults so no key is ever missing.
	 *
	 * @return array
	 */
	public static function globals() {
		$stored = get_option( self::OPT_GLOBALS, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::default_globals() );
	}

	/**
	 * Read placement options merged over defaults (recursively per zone).
	 *
	 * @return array
	 */
	public static function placements() {
		$stored   = get_option( self::OPT_PLACEMENTS, array() );
		$defaults = self::default_placements();
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		$out = array();
		foreach ( $defaults as $zone => $zone_defaults ) {
			$zone_stored = isset( $stored[ $zone ] ) && is_array( $stored[ $zone ] ) ? $stored[ $zone ] : array();
			$out[ $zone ] = wp_parse_args( $zone_stored, $zone_defaults );
		}
		return $out;
	}

	/**
	 * Resolve the EFFECTIVE renderer args for a placement zone. Starts from the
	 * globals, applies the zone overrides, and resolves the cascade for
	 * show_thumbnails / broken_image_mode / vertical_filter. This is the single
	 * method placements, widgets and admin previews use so the cascade lives in
	 * exactly one place.
	 *
	 * @param string $zone_key Zone key (after_content, header, ...).
	 * @return array Effective args for RH_Renderer::render().
	 */
	public static function zone_args( $zone_key ) {
		$globals    = self::globals();
		$placements = self::placements();
		$zone       = isset( $placements[ $zone_key ] ) ? $placements[ $zone_key ] : array();

		$args = array(
			'format'            => isset( $zone['format'] ) ? $zone['format'] : $globals['active_format'],
			'count'             => isset( $zone['count'] ) ? $zone['count'] : $globals['cards_count'],
			'heading_text'      => isset( $zone['heading_text'] ) && '' !== $zone['heading_text'] ? $zone['heading_text'] : $globals['heading_text'],
			'accent_color'      => isset( $zone['accent_color'] ) && '' !== $zone['accent_color'] ? $zone['accent_color'] : $globals['accent_color'],
			'show_thumbnails'   => isset( $zone['show_thumbnails'] ) ? (int) $zone['show_thumbnails'] : (int) $globals['show_thumbnails'],
			'fallback_image'    => isset( $zone['fallback_image'] ) && '' !== $zone['fallback_image'] ? $zone['fallback_image'] : $globals['fallback_image'],
			'broken_image_mode' => isset( $zone['broken_image_mode'] ) && '' !== $zone['broken_image_mode'] ? $zone['broken_image_mode'] : $globals['broken_image_mode'],
			'vertical_filter'   => isset( $zone['vertical_filter'] ) && '' !== $zone['vertical_filter'] ? $zone['vertical_filter'] : $globals['vertical_filter'],
			'open_new_tab'      => isset( $zone['open_new_tab'] ) ? (int) $zone['open_new_tab'] : (int) $globals['open_new_tab'],
			'display_scope'     => isset( $zone['display_scope'] ) ? $zone['display_scope'] : 'single',
			'zone'              => $zone_key,
		);

		return $args;
	}

	/* ---------------------------------------------------------------------
	 * Sanitization (used as register_setting callbacks).
	 * ------------------------------------------------------------------- */

	/**
	 * Sanitize the whole globals array.
	 *
	 * @param mixed $input Raw input from Settings API.
	 * @return array
	 */
	public static function sanitize_globals( $input ) {
		$d   = self::default_globals();
		$in  = is_array( $input ) ? $input : array();
		$out = array();

		$out['active_format']     = self::clean_format( isset( $in['active_format'] ) ? $in['active_format'] : $d['active_format'] );
		$out['cards_count']       = self::clamp_count( isset( $in['cards_count'] ) ? $in['cards_count'] : $d['cards_count'] );
		$out['heading_text']      = sanitize_text_field( isset( $in['heading_text'] ) ? $in['heading_text'] : $d['heading_text'] );
		$out['accent_color']      = self::clean_color( isset( $in['accent_color'] ) ? $in['accent_color'] : '' );
		$out['show_thumbnails']   = empty( $in['show_thumbnails'] ) ? 0 : 1;
		$out['fallback_image']    = isset( $in['fallback_image'] ) && '' !== $in['fallback_image'] ? esc_url_raw( $in['fallback_image'] ) : '';
		$out['broken_image_mode'] = self::clean_broken_mode( isset( $in['broken_image_mode'] ) ? $in['broken_image_mode'] : $d['broken_image_mode'] );
		$out['vertical_filter']   = self::clean_vertical( isset( $in['vertical_filter'] ) ? $in['vertical_filter'] : '' );
		$out['open_new_tab']      = empty( $in['open_new_tab'] ) ? 0 : 1;

		return $out;
	}

	/**
	 * Sanitize the whole placements array (each zone).
	 *
	 * @param mixed $input Raw input from Settings API.
	 * @return array
	 */
	public static function sanitize_placements( $input ) {
		$defaults = self::default_placements();
		$in       = is_array( $input ) ? $input : array();
		$out      = array();

		foreach ( $defaults as $zone => $zone_defaults ) {
			$z = isset( $in[ $zone ] ) && is_array( $in[ $zone ] ) ? $in[ $zone ] : array();

			$out[ $zone ] = array(
				'enabled'           => empty( $z['enabled'] ) ? 0 : 1,
				'format'            => self::clean_format( isset( $z['format'] ) ? $z['format'] : $zone_defaults['format'] ),
				'count'             => self::clamp_count( isset( $z['count'] ) ? $z['count'] : $zone_defaults['count'] ),
				'heading_text'      => sanitize_text_field( isset( $z['heading_text'] ) ? $z['heading_text'] : $zone_defaults['heading_text'] ),
				'display_scope'     => self::clean_scope( isset( $z['display_scope'] ) ? $z['display_scope'] : $zone_defaults['display_scope'] ),
				'accent_color'      => self::clean_color( isset( $z['accent_color'] ) ? $z['accent_color'] : '' ),
				'show_thumbnails'   => isset( $z['show_thumbnails'] ) && '' !== $z['show_thumbnails'] ? ( empty( $z['show_thumbnails'] ) ? 0 : 1 ) : 1,
				'broken_image_mode' => isset( $z['broken_image_mode'] ) && '' !== $z['broken_image_mode'] ? self::clean_broken_mode( $z['broken_image_mode'] ) : '',
				'vertical_filter'   => self::clean_vertical( isset( $z['vertical_filter'] ) ? $z['vertical_filter'] : '' ),
			);
		}

		return $out;
	}

	/* ---------------------------------------------------------------------
	 * Small validators (reused by REST render + widget too).
	 * ------------------------------------------------------------------- */

	/**
	 * Validate a format against the whitelist, falling back to cards_grid.
	 *
	 * @param string $format Candidate format.
	 * @return string
	 */
	public static function clean_format( $format ) {
		$format = is_string( $format ) ? sanitize_key( $format ) : '';
		return in_array( $format, self::formats(), true ) ? $format : 'cards_grid';
	}

	/**
	 * Clamp a count to 1..9.
	 *
	 * @param mixed $count Candidate count.
	 * @return int
	 */
	public static function clamp_count( $count ) {
		$count = (int) $count;
		if ( $count < 1 ) {
			$count = 1;
		}
		if ( $count > 9 ) {
			$count = 9;
		}
		return $count;
	}

	/**
	 * Validate a hex color; empty means "inherit from theme".
	 *
	 * @param string $color Candidate color.
	 * @return string
	 */
	public static function clean_color( $color ) {
		if ( '' === $color || null === $color ) {
			return '';
		}
		$clean = sanitize_hex_color( $color );
		return $clean ? $clean : '';
	}

	/**
	 * Validate a broken-image mode.
	 *
	 * @param string $mode Candidate mode.
	 * @return string
	 */
	public static function clean_broken_mode( $mode ) {
		$mode = is_string( $mode ) ? sanitize_key( $mode ) : '';
		return in_array( $mode, self::broken_image_modes(), true ) ? $mode : 'fallback';
	}

	/**
	 * Validate a vertical; empty means "all".
	 *
	 * @param string $vertical Candidate vertical.
	 * @return string
	 */
	public static function clean_vertical( $vertical ) {
		$vertical = is_string( $vertical ) ? sanitize_key( $vertical ) : '';
		if ( '' === $vertical ) {
			return '';
		}
		return in_array( $vertical, self::verticals(), true ) ? $vertical : '';
	}

	/**
	 * Validate a display scope.
	 *
	 * @param string $scope Candidate scope.
	 * @return string
	 */
	public static function clean_scope( $scope ) {
		$scope = is_string( $scope ) ? sanitize_key( $scope ) : '';
		return in_array( $scope, self::display_scopes(), true ) ? $scope : 'single';
	}
}
