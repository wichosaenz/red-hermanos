<?php
/**
 * Activation — creates the storage table with dbDelta() and seeds default
 * options from RH_Settings.
 *
 * @package Red_Hermanos
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class RH_Activator.
 */
class RH_Activator {

	/**
	 * Run on plugin activation.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_table();
		self::seed_options();
	}

	/**
	 * Fully-qualified table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . RH_TABLE_NAME;
	}

	/**
	 * Create/upgrade the table via dbDelta().
	 *
	 * @return void
	 */
	private static function create_table() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		// dbDelta is picky about formatting: two spaces after PRIMARY KEY, one
		// space between type and other keywords, lowercase keys aligned.
		$sql = "CREATE TABLE {$table} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  post_url VARCHAR(500) NOT NULL,
  post_title VARCHAR(500) NOT NULL,
  post_excerpt TEXT DEFAULT NULL,
  site_url VARCHAR(255) NOT NULL,
  site_name VARCHAR(100) NOT NULL,
  vertical VARCHAR(50) DEFAULT NULL,
  researcher VARCHAR(100) DEFAULT NULL,
  thumbnail_url VARCHAR(500) DEFAULT NULL,
  similarity_score FLOAT DEFAULT 0,
  topic_tag VARCHAR(255) DEFAULT NULL,
  semana_iso VARCHAR(10) NOT NULL,
  fecha_sync DATETIME NOT NULL,
  formato_asignado VARCHAR(50) DEFAULT 'cards_grid',
  posicion TINYINT(3) DEFAULT 0,
  activo TINYINT(1) DEFAULT 1,
  PRIMARY KEY  (id),
  KEY idx_semana (semana_iso),
  KEY idx_activo (activo),
  KEY idx_formato (formato_asignado),
  KEY idx_vertical (vertical)
) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Seed default options only if they do not exist yet (add_option is a no-op
	 * when the option already exists, so re-activation never clobbers config).
	 *
	 * @return void
	 */
	private static function seed_options() {
		add_option( RH_Settings::OPT_GLOBALS, RH_Settings::default_globals() );
		add_option( RH_Settings::OPT_PLACEMENTS, RH_Settings::default_placements() );
		add_option( RH_Settings::OPT_DB_VERSION, RH_VERSION );

		// Keep db version current for future migrations.
		update_option( RH_Settings::OPT_DB_VERSION, RH_VERSION );
	}
}
