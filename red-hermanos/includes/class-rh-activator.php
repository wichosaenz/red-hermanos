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
		self::maybe_migrate();
	}

	/**
	 * Boot-time migration hook. Runs on every load (cheap: one option read) so
	 * the 20 sites already on v1.1 get schema changes without re-activating.
	 *
	 * @return void
	 */
	public static function maybe_migrate() {
		if ( get_option( RH_Settings::OPT_DB_VERSION ) === RH_VERSION ) {
			return;
		}

		global $wpdb;
		$table = self::table_name();

		// v1.2.0: ensure the site_icon_url column exists. We check
		// information_schema and ALTER without "IF NOT EXISTS", because MySQL
		// (unlike MariaDB) does not support IF NOT EXISTS for ADD COLUMN — using
		// it there raises a syntax error and the column never gets added.
		if ( ! self::column_exists( $table, 'site_icon_url' ) ) {
			$wpdb->query( "ALTER TABLE {$table} ADD COLUMN site_icon_url VARCHAR(500) DEFAULT NULL AFTER thumbnail_url" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange
		}

		// Only record the new db version once the column is really present, so a
		// failed ALTER (e.g. permissions) is retried on the next load instead of
		// being silently marked done.
		if ( self::column_exists( $table, 'site_icon_url' ) ) {
			update_option( RH_Settings::OPT_DB_VERSION, RH_VERSION );
		}
	}

	/**
	 * Public helper: does the storage table have the site_icon_url column?
	 * Used by the REST sync to stay resilient if a migration has not run yet.
	 *
	 * @return bool
	 */
	public static function has_site_icon_column() {
		return self::column_exists( self::table_name(), 'site_icon_url' );
	}

	/**
	 * Whether a column exists on a table (cross-DB, via information_schema).
	 *
	 * @param string $table  Fully-qualified table name.
	 * @param string $column Column name.
	 * @return bool
	 */
	private static function column_exists( $table, $column ) {
		global $wpdb;
		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
				DB_NAME,
				$table,
				$column
			)
		); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return null !== $found;
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
  site_icon_url VARCHAR(500) DEFAULT NULL,
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
