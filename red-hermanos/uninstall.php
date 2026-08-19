<?php
/**
 * Uninstall — drop the table and delete every rh_* option. Multisite-aware.
 *
 * @package Red_Hermanos
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Remove all plugin data for the current site.
 *
 * @return void
 */
function rh_uninstall_cleanup() {
	global $wpdb;

	$table = $wpdb->prefix . 'red_hermanos';
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange

	delete_option( 'rh_globals' );
	delete_option( 'rh_placements' );
	delete_option( 'rh_db_version' );
}

if ( is_multisite() ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $sites as $blog_id ) {
		switch_to_blog( $blog_id );
		rh_uninstall_cleanup();
		restore_current_blog();
	}
} else {
	rh_uninstall_cleanup();
}
