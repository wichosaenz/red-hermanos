<?php
/**
 * Classic widget — drop into any sidebar/footer widget area (also works in the
 * block widget editor via the Legacy Widget block).
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Widget.
 */
class RH_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'rh_widget',
			__( 'Red Hermanos', 'red-hermanos' ),
			array(
				'description' => __( 'Cross-site related posts from your sibling network.', 'red-hermanos' ),
			)
		);
	}

	/**
	 * Default instance values.
	 *
	 * @return array
	 */
	private function defaults() {
		$g = RH_Settings::globals();
		return array(
			'title'             => __( 'From Our Network', 'red-hermanos' ),
			'format'            => $g['active_format'],
			'count'             => $g['cards_count'],
			'show_thumbnails'   => $g['show_thumbnails'],
			'broken_image_mode' => $g['broken_image_mode'],
			'vertical_filter'   => $g['vertical_filter'],
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Saved instance values.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults() );

		$render_args = array(
			'format'            => RH_Settings::clean_format( $instance['format'] ),
			'count'             => RH_Settings::clamp_count( $instance['count'] ),
			'heading_text'      => sanitize_text_field( $instance['title'] ),
			'show_thumbnails'   => empty( $instance['show_thumbnails'] ) ? 0 : 1,
			'broken_image_mode' => RH_Settings::clean_broken_mode( $instance['broken_image_mode'] ),
			'vertical_filter'   => RH_Settings::clean_vertical( $instance['vertical_filter'] ),
			'zone'              => 'sidebar',
		);

		$html = RH_Renderer::render( $render_args );

		if ( '' === $html ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		// Sidebar variant forces single column via CSS class hook.
		echo '<div class="rh--sidebar-wrap">';
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Settings form.
	 *
	 * @param array $instance Saved instance.
	 * @return void
	 */
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, $this->defaults() );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'red-hermanos' ); ?></label>
			<input class="widefat" type="text"
				id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				value="<?php echo esc_attr( $instance['title'] ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"><?php esc_html_e( 'Format:', 'red-hermanos' ); ?></label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'format' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'format' ) ); ?>">
				<?php foreach ( RH_Settings::formats() as $fmt ) : ?>
					<option value="<?php echo esc_attr( $fmt ); ?>" <?php selected( $instance['format'], $fmt ); ?>><?php echo esc_html( $fmt ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'How many (1-9):', 'red-hermanos' ); ?></label>
			<input class="tiny-text" type="number" min="1" max="9" step="1"
				id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>"
				value="<?php echo esc_attr( (string) $instance['count'] ); ?>" />
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( ! empty( $instance['show_thumbnails'] ) ); ?>
				id="<?php echo esc_attr( $this->get_field_id( 'show_thumbnails' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'show_thumbnails' ) ); ?>" value="1" />
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_thumbnails' ) ); ?>"><?php esc_html_e( 'Show thumbnails', 'red-hermanos' ); ?></label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'broken_image_mode' ) ); ?>"><?php esc_html_e( 'Broken image behavior:', 'red-hermanos' ); ?></label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'broken_image_mode' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'broken_image_mode' ) ); ?>">
				<?php foreach ( RH_Settings::broken_image_modes() as $mode ) : ?>
					<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $instance['broken_image_mode'], $mode ); ?>><?php echo esc_html( $mode ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'vertical_filter' ) ); ?>"><?php esc_html_e( 'Vertical filter:', 'red-hermanos' ); ?></label>
			<select class="widefat"
				id="<?php echo esc_attr( $this->get_field_id( 'vertical_filter' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'vertical_filter' ) ); ?>">
				<option value="" <?php selected( $instance['vertical_filter'], '' ); ?>><?php esc_html_e( 'All verticals', 'red-hermanos' ); ?></option>
				<?php foreach ( RH_Settings::verticals() as $v ) : ?>
					<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $instance['vertical_filter'], $v ); ?>><?php echo esc_html( $v ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Persist sanitized instance.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$out = array();
		$out['title']             = sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : '' );
		$out['format']            = RH_Settings::clean_format( isset( $new_instance['format'] ) ? $new_instance['format'] : '' );
		$out['count']             = RH_Settings::clamp_count( isset( $new_instance['count'] ) ? $new_instance['count'] : 3 );
		$out['show_thumbnails']   = empty( $new_instance['show_thumbnails'] ) ? 0 : 1;
		$out['broken_image_mode'] = RH_Settings::clean_broken_mode( isset( $new_instance['broken_image_mode'] ) ? $new_instance['broken_image_mode'] : '' );
		$out['vertical_filter']   = RH_Settings::clean_vertical( isset( $new_instance['vertical_filter'] ) ? $new_instance['vertical_filter'] : '' );
		return $out;
	}
}
