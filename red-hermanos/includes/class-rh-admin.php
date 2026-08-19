<?php
/**
 * Admin GUI — top-level "Red Hermanos" menu with tabbed settings pages,
 * Settings API storage, live preview, snippet copying and cache/status tools.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Admin.
 */
class RH_Admin {

	const PAGE_SLUG = 'red-hermanos';

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_ajax_rh_purge_cache', array( __CLASS__, 'ajax_purge_cache' ) );
	}

	/**
	 * Register the top-level menu.
	 *
	 * @return void
	 */
	public static function add_menu() {
		add_menu_page(
			__( 'Red Hermanos', 'red-hermanos' ),
			__( 'Red Hermanos', 'red-hermanos' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-networking',
			58
		);
	}

	/**
	 * Register settings + sanitize callbacks.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'rh_settings_globals',
			RH_Settings::OPT_GLOBALS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'RH_Settings', 'sanitize_globals' ),
			)
		);

		register_setting(
			'rh_settings_placements',
			RH_Settings::OPT_PLACEMENTS,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'RH_Settings', 'sanitize_placements' ),
			)
		);
	}

	/**
	 * Enqueue admin assets only on our page.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_media();
		wp_enqueue_style( 'rh-admin', RH_PLUGIN_URL . 'assets/css/rh-admin.css', array(), RH_VERSION );

		wp_enqueue_script(
			'rh-admin',
			RH_PLUGIN_URL . 'assets/js/rh-admin.js',
			array( 'wp-color-picker' ),
			RH_VERSION,
			true
		);

		wp_localize_script(
			'rh-admin',
			'RH_ADMIN',
			array(
				'renderEndpoint' => esc_url_raw( rest_url( RH_Rest_API::NAMESPACE . '/render' ) ),
				'statusEndpoint' => esc_url_raw( rest_url( RH_Rest_API::NAMESPACE . '/status' ) ),
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'purgeNonce'     => wp_create_nonce( 'rh_purge_cache' ),
				'restNonce'      => wp_create_nonce( 'wp_rest' ),
				'i18n'           => array(
					'copied'    => __( 'Copied!', 'red-hermanos' ),
					'copy'      => __( 'Copy', 'red-hermanos' ),
					'loading'   => __( 'Loading preview…', 'red-hermanos' ),
					'noData'    => __( 'No data to preview yet.', 'red-hermanos' ),
					'purged'    => __( 'Cache purged.', 'red-hermanos' ),
					'purgeFail' => __( 'No Breeze cache found (or purge failed).', 'red-hermanos' ),
				),
			)
		);
	}

	/**
	 * AJAX: purge Breeze cache now.
	 *
	 * @return void
	 */
	public static function ajax_purge_cache() {
		check_ajax_referer( 'rh_purge_cache', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'red-hermanos' ) ), 403 );
		}
		if ( function_exists( 'breeze_clear_all_cache' ) ) {
			breeze_clear_all_cache();
			wp_send_json_success( array( 'message' => __( 'Cache purged.', 'red-hermanos' ) ) );
		}
		wp_send_json_error( array( 'message' => __( 'No Breeze cache found.', 'red-hermanos' ) ) );
	}

	/**
	 * Current tab from the query string.
	 *
	 * @return string
	 */
	private static function current_tab() {
		$tab   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$valid = array( 'general', 'placements', 'widgets', 'status', 'tools' );
		return in_array( $tab, $valid, true ) ? $tab : 'general';
	}

	/**
	 * Render the settings page shell + tab body.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab  = self::current_tab();
		$tabs = array(
			'general'    => __( 'General', 'red-hermanos' ),
			'placements' => __( 'Placements', 'red-hermanos' ),
			'widgets'    => __( 'Widgets & Shortcode', 'red-hermanos' ),
			'status'     => __( 'Status', 'red-hermanos' ),
			'tools'      => __( 'Tools', 'red-hermanos' ),
		);
		?>
		<div class="wrap rh-admin">
			<h1><span class="dashicons dashicons-networking"></span> <?php esc_html_e( 'Red Hermanos', 'red-hermanos' ); ?></h1>
			<p class="rh-tagline"><?php esc_html_e( 'Cross-site related posts. Data is delivered weekly by n8n via REST API; this plugin stores and displays it.', 'red-hermanos' ); ?></p>

			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $slug => $label ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => self::PAGE_SLUG, 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>"
						class="nav-tab <?php echo $tab === $slug ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $label ); ?>
					</a>
				<?php endforeach; ?>
			</h2>

			<div class="rh-tab-body">
				<?php
				switch ( $tab ) {
					case 'placements':
						self::tab_placements();
						break;
					case 'widgets':
						self::tab_widgets();
						break;
					case 'status':
						self::tab_status();
						break;
					case 'tools':
						self::tab_tools();
						break;
					case 'general':
					default:
						self::tab_general();
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tab: General.
	 * ------------------------------------------------------------------- */

	/**
	 * General settings tab.
	 *
	 * @return void
	 */
	private static function tab_general() {
		$g = RH_Settings::globals();
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'rh_settings_globals' ); ?>
			<?php $name = RH_Settings::OPT_GLOBALS; ?>

			<h2><?php esc_html_e( 'Defaults', 'red-hermanos' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Default format', 'red-hermanos' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( $name ); ?>[active_format]">
							<?php foreach ( RH_Settings::formats() as $fmt ) : ?>
								<option value="<?php echo esc_attr( $fmt ); ?>" <?php selected( $g['active_format'], $fmt ); ?>><?php echo esc_html( $fmt ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'How many cards (1-9)', 'red-hermanos' ); ?></th>
					<td><input type="number" min="1" max="9" step="1" name="<?php echo esc_attr( $name ); ?>[cards_count]" value="<?php echo esc_attr( (string) $g['cards_count'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Heading text', 'red-hermanos' ); ?></th>
					<td><input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[heading_text]" value="<?php echo esc_attr( $g['heading_text'] ); ?>" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Accent color', 'red-hermanos' ); ?></th>
					<td>
						<input type="text" class="rh-color-picker" name="<?php echo esc_attr( $name ); ?>[accent_color]" value="<?php echo esc_attr( $g['accent_color'] ); ?>" data-default-color="" />
						<p class="description"><?php esc_html_e( 'Leave empty to inherit the theme accent (YITH Proteo).', 'red-hermanos' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Open links in new tab', 'red-hermanos' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[open_new_tab]" value="1" <?php checked( ! empty( $g['open_new_tab'] ) ); ?> /> <?php esc_html_e( 'Yes', 'red-hermanos' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Vertical filter', 'red-hermanos' ); ?></th>
					<td>
						<select name="<?php echo esc_attr( $name ); ?>[vertical_filter]">
							<option value="" <?php selected( $g['vertical_filter'], '' ); ?>><?php esc_html_e( 'All verticals', 'red-hermanos' ); ?></option>
							<?php foreach ( RH_Settings::verticals() as $v ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $g['vertical_filter'], $v ); ?>><?php echo esc_html( $v ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<h2 class="rh-section-images"><?php esc_html_e( 'Images', 'red-hermanos' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Show thumbnails', 'red-hermanos' ); ?></th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[show_thumbnails]" value="1" <?php checked( ! empty( $g['show_thumbnails'] ) ); ?> /> <?php esc_html_e( 'Master toggle for images (text-only cards when off).', 'red-hermanos' ); ?></label></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Fallback image', 'red-hermanos' ); ?></th>
					<td>
						<input type="text" class="regular-text rh-media-url" name="<?php echo esc_attr( $name ); ?>[fallback_image]" value="<?php echo esc_attr( $g['fallback_image'] ); ?>" />
						<button type="button" class="button rh-media-pick"><?php esc_html_e( 'Choose image', 'red-hermanos' ); ?></button>
						<p class="description"><?php esc_html_e( 'Used when an article has no thumbnail (and broken-image mode is "Use fallback").', 'red-hermanos' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Broken image behavior', 'red-hermanos' ); ?></th>
					<td>
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[broken_image_mode]" value="fallback" <?php checked( $g['broken_image_mode'], 'fallback' ); ?> /> <?php esc_html_e( 'Use fallback', 'red-hermanos' ); ?></label><br />
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[broken_image_mode]" value="hide_image" <?php checked( $g['broken_image_mode'], 'hide_image' ); ?> /> <?php esc_html_e( 'Hide image (text-only card)', 'red-hermanos' ); ?></label><br />
						<label><input type="radio" name="<?php echo esc_attr( $name ); ?>[broken_image_mode]" value="hide_card" <?php checked( $g['broken_image_mode'], 'hide_card' ); ?> /> <?php esc_html_e( 'Hide the whole card', 'red-hermanos' ); ?></label>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Live preview', 'red-hermanos' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Preview uses the public /render endpoint with your current default format. Save first to preview saved values.', 'red-hermanos' ); ?></p>
		<p>
			<button type="button" class="button rh-preview-btn"
				data-format="<?php echo esc_attr( $g['active_format'] ); ?>"
				data-count="<?php echo esc_attr( (string) $g['cards_count'] ); ?>"
				data-thumbs="<?php echo esc_attr( $g['show_thumbnails'] ? '1' : '0' ); ?>"
				data-vertical="<?php echo esc_attr( $g['vertical_filter'] ); ?>"
				data-target="#rh-preview-general"><?php esc_html_e( 'Preview', 'red-hermanos' ); ?></button>
		</p>
		<div id="rh-preview-general" class="rh-preview-box"></div>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tab: Placements.
	 * ------------------------------------------------------------------- */

	/**
	 * Placements tab.
	 *
	 * @return void
	 */
	private static function tab_placements() {
		$placements = RH_Settings::placements();
		$name       = RH_Settings::OPT_PLACEMENTS;

		$zone_meta = array(
			'after_content'  => array( __( 'After content', 'red-hermanos' ), __( 'Inserted right after the post content (the_content, priority 99).', 'red-hermanos' ) ),
			'before_content' => array( __( 'Before content', 'red-hermanos' ), __( 'Inserted just before the post content.', 'red-hermanos' ) ),
			'header'         => array( __( 'Header (site top)', 'red-hermanos' ), __( 'Rendered at wp_body_open (top of the site). Requires theme support for wp_body_open.', 'red-hermanos' ) ),
			'footer'         => array( __( 'Footer', 'red-hermanos' ), __( 'Rendered at wp_footer.', 'red-hermanos' ) ),
		);
		?>
		<form method="post" action="options.php">
			<?php settings_fields( 'rh_settings_placements' ); ?>
			<div class="rh-cards">
			<?php foreach ( $zone_meta as $zone => $meta ) :
				$z = $placements[ $zone ];
				?>
				<div class="rh-card-zone">
					<h3><?php echo esc_html( $meta[0] ); ?></h3>
					<p class="description"><?php echo esc_html( $meta[1] ); ?></p>

					<p><label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][enabled]" value="1" <?php checked( ! empty( $z['enabled'] ) ); ?> /> <?php esc_html_e( 'Enabled', 'red-hermanos' ); ?></label></p>

					<p>
						<label><?php esc_html_e( 'Format', 'red-hermanos' ); ?></label><br />
						<select name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][format]">
							<?php foreach ( RH_Settings::formats() as $fmt ) : ?>
								<option value="<?php echo esc_attr( $fmt ); ?>" <?php selected( $z['format'], $fmt ); ?>><?php echo esc_html( $fmt ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label><?php esc_html_e( 'Count (1-9)', 'red-hermanos' ); ?></label><br />
						<input type="number" min="1" max="9" step="1" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][count]" value="<?php echo esc_attr( (string) $z['count'] ); ?>" />
					</p>

					<p>
						<label><?php esc_html_e( 'Heading (optional)', 'red-hermanos' ); ?></label><br />
						<input type="text" class="regular-text" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][heading_text]" value="<?php echo esc_attr( $z['heading_text'] ); ?>" />
					</p>

					<p>
						<label><?php esc_html_e( 'Display scope', 'red-hermanos' ); ?></label><br />
						<select name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][display_scope]">
							<option value="single" <?php selected( $z['display_scope'], 'single' ); ?>><?php esc_html_e( 'Single posts', 'red-hermanos' ); ?></option>
							<option value="front" <?php selected( $z['display_scope'], 'front' ); ?>><?php esc_html_e( 'Front page', 'red-hermanos' ); ?></option>
							<option value="all" <?php selected( $z['display_scope'], 'all' ); ?>><?php esc_html_e( 'Everywhere (front-end)', 'red-hermanos' ); ?></option>
						</select>
					</p>

					<p>
						<label><input type="checkbox" name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][show_thumbnails]" value="1" <?php checked( ! empty( $z['show_thumbnails'] ) ); ?> /> <?php esc_html_e( 'Show thumbnails', 'red-hermanos' ); ?></label>
					</p>

					<p>
						<label><?php esc_html_e( 'Broken image behavior', 'red-hermanos' ); ?></label><br />
						<select name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][broken_image_mode]">
							<option value="" <?php selected( $z['broken_image_mode'], '' ); ?>><?php esc_html_e( '(inherit global)', 'red-hermanos' ); ?></option>
							<?php foreach ( RH_Settings::broken_image_modes() as $mode ) : ?>
								<option value="<?php echo esc_attr( $mode ); ?>" <?php selected( $z['broken_image_mode'], $mode ); ?>><?php echo esc_html( $mode ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<label><?php esc_html_e( 'Vertical filter', 'red-hermanos' ); ?></label><br />
						<select name="<?php echo esc_attr( $name ); ?>[<?php echo esc_attr( $zone ); ?>][vertical_filter]">
							<option value="" <?php selected( $z['vertical_filter'], '' ); ?>><?php esc_html_e( 'All verticals', 'red-hermanos' ); ?></option>
							<?php foreach ( RH_Settings::verticals() as $v ) : ?>
								<option value="<?php echo esc_attr( $v ); ?>" <?php selected( $z['vertical_filter'], $v ); ?>><?php echo esc_html( $v ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>

					<p>
						<button type="button" class="button rh-preview-btn"
							data-format="<?php echo esc_attr( $z['format'] ); ?>"
							data-count="<?php echo esc_attr( (string) $z['count'] ); ?>"
							data-thumbs="<?php echo esc_attr( ! empty( $z['show_thumbnails'] ) ? '1' : '0' ); ?>"
							data-vertical="<?php echo esc_attr( $z['vertical_filter'] ); ?>"
							data-target="#rh-preview-<?php echo esc_attr( $zone ); ?>"><?php esc_html_e( 'Preview', 'red-hermanos' ); ?></button>
					</p>
					<div id="rh-preview-<?php echo esc_attr( $zone ); ?>" class="rh-preview-box"></div>
				</div>
			<?php endforeach; ?>
			</div>
			<?php submit_button(); ?>
		</form>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tab: Widgets & Shortcode.
	 * ------------------------------------------------------------------- */

	/**
	 * Widgets & shortcode tab.
	 *
	 * @return void
	 */
	private static function tab_widgets() {
		$render_url = esc_url_raw( rest_url( RH_Rest_API::NAMESPACE . '/render?format=ticker&count=5' ) );
		$snippets   = array(
			__( 'Shortcode', 'red-hermanos' )          => '[red_hermanos format="cards_grid" count="3"]',
			__( 'Shortcode (embed)', 'red-hermanos' )  => '[red_hermanos format="ticker" embed="1"]',
			__( 'Template tag (PHP)', 'red-hermanos' ) => "<?php if ( function_exists( 'red_hermanos' ) ) red_hermanos( array( 'format' => 'cards_grid' ) ); ?>",
			__( 'Render endpoint URL', 'red-hermanos' ) => $render_url,
		);
		?>
		<h2><?php esc_html_e( 'Widget', 'red-hermanos' ); ?></h2>
		<p><?php
			printf(
				/* translators: %s: Appearance > Widgets link. */
				esc_html__( 'A %s widget is available to drag into any sidebar or footer widget area, each with its own format, count and image options.', 'red-hermanos' ),
				'<strong>' . esc_html__( 'Red Hermanos', 'red-hermanos' ) . '</strong>'
			);
		?> <a href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Open the Widgets screen', 'red-hermanos' ); ?></a>.</p>

		<h2><?php esc_html_e( 'Snippets', 'red-hermanos' ); ?></h2>
		<table class="widefat rh-snippets">
			<tbody>
			<?php foreach ( $snippets as $label => $code ) : ?>
				<tr>
					<th scope="row"><?php echo esc_html( $label ); ?></th>
					<td><code class="rh-snippet"><?php echo esc_html( $code ); ?></code></td>
					<td><button type="button" class="button rh-copy-btn" data-copy="<?php echo esc_attr( $code ); ?>"><?php esc_html_e( 'Copy', 'red-hermanos' ); ?></button></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tab: Status.
	 * ------------------------------------------------------------------- */

	/**
	 * Status / health tab (read-only).
	 *
	 * @return void
	 */
	private static function tab_status() {
		global $wpdb;
		$table  = $wpdb->prefix . RH_TABLE_NAME;
		$g      = RH_Settings::globals();
		$active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE activo = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$week   = $wpdb->get_var( "SELECT semana_iso FROM {$table} WHERE activo = 1 ORDER BY fecha_sync DESC LIMIT 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$sync   = $wpdb->get_var( "SELECT MAX(fecha_sync) FROM {$table} WHERE activo = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		$sync_url   = esc_url_raw( rest_url( RH_Rest_API::NAMESPACE . '/sync' ) );
		$status_url = esc_url_raw( rest_url( RH_Rest_API::NAMESPACE . '/status' ) );
		$render_url = esc_url_raw( rest_url( RH_Rest_API::NAMESPACE . '/render' ) );
		?>
		<table class="widefat striped">
			<tbody>
				<tr><th><?php esc_html_e( 'Plugin version', 'red-hermanos' ); ?></th><td><?php echo esc_html( RH_VERSION ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Active format', 'red-hermanos' ); ?></th><td><?php echo esc_html( $g['active_format'] ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Active articles', 'red-hermanos' ); ?></th><td><?php echo esc_html( (string) $active ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Current week', 'red-hermanos' ); ?></th><td><?php echo esc_html( $week ? $week : '—' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Last sync', 'red-hermanos' ); ?></th><td><?php echo esc_html( $sync ? $sync : '—' ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Sync endpoint (POST)', 'red-hermanos' ); ?></th><td><code><?php echo esc_html( $sync_url ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Status endpoint (GET)', 'red-hermanos' ); ?></th><td><code><?php echo esc_html( $status_url ); ?></code></td></tr>
				<tr><th><?php esc_html_e( 'Render endpoint (GET)', 'red-hermanos' ); ?></th><td><code><?php echo esc_html( $render_url ); ?></code></td></tr>
			</tbody>
		</table>
		<p>
			<button type="button" class="button rh-status-btn"><?php esc_html_e( 'Send test to /status', 'red-hermanos' ); ?></button>
			<button type="button" class="button rh-purge-btn"><?php esc_html_e( 'Purge cache now', 'red-hermanos' ); ?></button>
		</p>
		<pre id="rh-status-out" class="rh-status-out"></pre>
		<?php
	}

	/* ---------------------------------------------------------------------
	 * Tab: Tools.
	 * ------------------------------------------------------------------- */

	/**
	 * Tools tab — preview each format.
	 *
	 * @return void
	 */
	private static function tab_tools() {
		global $wpdb;
		$table  = $wpdb->prefix . RH_TABLE_NAME;
		$active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE activo = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		?>
		<?php if ( 0 === $active ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'No active articles yet. Run the weekly sync from n8n (WF4 — Red Hermanos Distributor) to populate the table.', 'red-hermanos' ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Preview each format', 'red-hermanos' ); ?></h2>
		<p>
			<button type="button" class="button button-primary rh-preview-all"><?php esc_html_e( 'Render all formats', 'red-hermanos' ); ?></button>
		</p>
		<div class="rh-format-gallery">
			<?php foreach ( RH_Settings::formats() as $fmt ) : ?>
				<div class="rh-format-tile">
					<h3><?php echo esc_html( $fmt ); ?></h3>
					<div class="rh-preview-box" data-format="<?php echo esc_attr( $fmt ); ?>" data-count="6"></div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}
