<?php
/**
 * Shortcodes — [red_hermanos ...]. Server-rendered by default; embed="1"
 * emits a container that rh-embed.js fills via the /render endpoint.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Shortcodes.
 */
class RH_Shortcodes {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_shortcode( 'red_hermanos', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * [red_hermanos] handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'format'   => '',
				'count'    => '',
				'heading'  => '',
				'thumbs'   => '',
				'vertical' => '',
				'accent'   => '',
				'embed'    => '0',
			),
			$atts,
			'red_hermanos'
		);

		$format   = RH_Settings::clean_format( $atts['format'] );
		$count    = '' !== $atts['count'] ? RH_Settings::clamp_count( $atts['count'] ) : RH_Settings::globals()['cards_count'];
		$vertical = RH_Settings::clean_vertical( $atts['vertical'] );

		// Embed variant: emit a placeholder for rh-embed.js (skips server cache).
		if ( '1' === (string) $atts['embed'] || 1 === $atts['embed'] ) {
			$thumbs_attr = '';
			if ( '' !== $atts['thumbs'] ) {
				$thumbs_attr = ' data-thumbs="' . esc_attr( ( '1' === (string) $atts['thumbs'] ) ? '1' : '0' ) . '"';
			}
			$heading_attr = '' !== $atts['heading'] ? ' data-heading="' . esc_attr( sanitize_text_field( $atts['heading'] ) ) . '"' : '';

			return '<div class="rh-embed" data-rh-embed data-format="' . esc_attr( $format ) . '"'
				. ' data-count="' . esc_attr( (string) $count ) . '"'
				. ' data-vertical="' . esc_attr( $vertical ) . '"'
				. $thumbs_attr . $heading_attr . '></div>';
		}

		$args = array(
			'format'          => $format,
			'count'           => $count,
			'vertical_filter' => $vertical,
			'zone'            => 'shortcode',
		);

		if ( '' !== $atts['heading'] ) {
			$args['heading_text'] = sanitize_text_field( $atts['heading'] );
		}
		if ( '' !== $atts['thumbs'] ) {
			$args['show_thumbnails'] = ( '1' === (string) $atts['thumbs'] ) ? 1 : 0;
		}
		if ( '' !== $atts['accent'] ) {
			$args['accent_color'] = RH_Settings::clean_color( $atts['accent'] );
		}

		return RH_Renderer::render( $args );
	}
}
