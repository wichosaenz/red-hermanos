<?php
/**
 * Plugin Name:       Red Hermanos — Cross-Site Related Posts
 * Plugin URI:        https://github.com/wichosaenz/red-hermanos
 * Description:        Muestra artículos relacionados de los sitios hermanos de la red, alimentado semanalmente por n8n vía REST API.
 * Version:           1.2.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Everest Ecosystem
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       red-hermanos
 * Domain Path:       /languages
 *
 * @package Red_Hermanos
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/*
 * ---------------------------------------------------------------------------
 * SECURITY MODEL (see 0-BIS in the project brief)
 * ---------------------------------------------------------------------------
 * Red Hermanos is a plain "related posts" display layer for a legitimate
 * editorial network. It NEVER receives, stores or executes HTML, <script>,
 * markup or code. Every field is sanitized on the way IN (sanitize_text_field
 * / sanitize_textarea_field / esc_url_raw) and every value is escaped on the
 * way OUT (esc_html / esc_attr / esc_url). No eval, no create_function, no
 * remote code, no outbound requests. Auth uses native WordPress Application
 * Passwords with capability checks. The public /render endpoint exposes only
 * already-public data and returns pre-escaped markup.
 * ---------------------------------------------------------------------------
 */

/* -------------------------------------------------------------------------
 * Constants
 * ---------------------------------------------------------------------------
 */
define( 'RH_VERSION', '1.2.1' );
define( 'RH_PLUGIN_FILE', __FILE__ );
define( 'RH_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RH_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RH_TABLE_NAME', 'red_hermanos' );

/* -------------------------------------------------------------------------
 * Class autoloading (explicit requires — no Composer, no external deps).
 * ---------------------------------------------------------------------------
 */
require_once RH_PLUGIN_DIR . 'includes/class-rh-settings.php';
require_once RH_PLUGIN_DIR . 'includes/class-rh-activator.php';
require_once RH_PLUGIN_DIR . 'includes/class-rh-rest-api.php';
require_once RH_PLUGIN_DIR . 'includes/class-rh-renderer.php';
require_once RH_PLUGIN_DIR . 'includes/class-rh-placements.php';
require_once RH_PLUGIN_DIR . 'includes/class-rh-widget.php';
require_once RH_PLUGIN_DIR . 'includes/class-rh-admin.php';
require_once RH_PLUGIN_DIR . 'includes/class-rh-shortcodes.php';

/* -------------------------------------------------------------------------
 * GitHub auto-updates.
 *
 * Uses YahnisElsts/plugin-update-checker (MIT license, v5.7). Checks GitHub
 * releases for new versions and surfaces the native WordPress "update now"
 * notification. No Composer — the library is vendored in-tree. For a private
 * repository, a Personal Access Token (repo scope) saved in the admin panel is
 * applied below.
 * ---------------------------------------------------------------------------
 */
require_once RH_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';

add_action(
	'init',
	function () {
		if ( ! class_exists( '\\YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
			return;
		}

		$update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			'https://github.com/wichosaenz/red-hermanos/',
			RH_PLUGIN_FILE,
			'red-hermanos'
		);

		// Use GitHub releases (not tags/branches) for version detection. The
		// release tag should match the version (e.g. "v1.2.0").
		$vcs_api = $update_checker->getVcsApi();
		if ( $vcs_api && method_exists( $vcs_api, 'enableReleaseAssets' ) ) {
			$vcs_api->enableReleaseAssets();
		}

		// Private repository: apply the token saved in the admin panel, if any.
		$token = get_option( 'rh_github_token', '' );
		if ( ! empty( $token ) ) {
			$update_checker->setAuthentication( $token );
		}
	},
	1
);

/* -------------------------------------------------------------------------
 * Activation — create table + seed options.
 * ---------------------------------------------------------------------------
 */
register_activation_hook( __FILE__, array( 'RH_Activator', 'activate' ) );

/* -------------------------------------------------------------------------
 * Bootstrap.
 * ---------------------------------------------------------------------------
 */
add_action(
	'plugins_loaded',
	function () {
		// i18n.
		load_plugin_textdomain(
			'red-hermanos',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		// Run pending schema migrations for sites upgraded in place (WordPress
		// does not fire the activation hook on plugin updates).
		RH_Activator::maybe_migrate();

		// Core runtime pieces (front + REST).
		RH_Rest_API::init();
		RH_Renderer::init();
		RH_Placements::init();
		RH_Shortcodes::init();

		// Admin GUI only in wp-admin.
		if ( is_admin() ) {
			RH_Admin::init();
		}
	}
);

/* -------------------------------------------------------------------------
 * Register the classic widget (sidebar/footer areas).
 * ---------------------------------------------------------------------------
 */
add_action(
	'widgets_init',
	function () {
		register_widget( 'RH_Widget' );
	}
);

/* -------------------------------------------------------------------------
 * Public template tag.
 *
 * Usage in a theme file (header.php / footer.php / sidebar.php):
 *   <?php if ( function_exists( 'red_hermanos' ) ) red_hermanos( array( 'format' => 'cards_grid' ) ); ?>
 *
 * Prints the rendered (already-escaped) markup for the given args.
 * ---------------------------------------------------------------------------
 */
if ( ! function_exists( 'red_hermanos' ) ) {
	/**
	 * Print the Red Hermanos block for a set of args.
	 *
	 * @param array $args Renderer args (merged over globals). Optional.
	 * @return void
	 */
	function red_hermanos( array $args = array() ) {
		// RH_Renderer::render() returns markup already escaped inside the template.
		echo RH_Renderer::render( $args ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
