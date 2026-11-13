<?php
/**
 * PSStats: PSStats_Table class
 *
 * This file contains class for the plugin's DB table handling.
 *
 * @package   PSStats
 * @since     0.6
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * PSStats Table
 *
 * @since 0.6
 */
class PSStats_Table {

	/**
	 * Definition of the custom table.
	 *
	 * @since   0.6.0
	 * @version 1.2.4
	 */
	public static function init() {

		// Global.
		global $wpdb;

		// Name.
		$table = 'psstats';

		// As array.
		if ( ! in_array( $table, $wpdb->tables, true ) ) {
			$wpdb->tables[] = $table;
		}

		// With prefix.
		$wpdb->$table = $wpdb->get_blog_prefix() . $table;
	}


	/**
	 * Create the table.
	 *
	 * @since   0.6.0
	 * @version 1.2.4
	 *
	 * @return bool Whether the table exists after installation.
	 */
	public static function create() {

		global $wpdb;

		// If existent.
		$exists_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->psstats ) );
		if ( $wpdb->get_var( $exists_query ) === $wpdb->psstats ) {
			return true;
		}
		if ( '' !== $wpdb->last_error ) {
			return false;
		}

		// Include.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create.
		$charset = $wpdb->get_charset_collate();
		dbDelta(
			"CREATE TABLE `$wpdb->psstats` (
			`id` bigint(20) unsigned NOT NULL auto_increment,
			  `created` date NOT NULL,
			  `referrer` varchar(255) NOT NULL default '',
			  `target` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `referrer` (`referrer`),
			  KEY `target` (`target`),
			  KEY `created` (`created`)
			) $charset;"
		);

		if ( $wpdb->get_var( $exists_query ) !== $wpdb->psstats ) {
			return false;
		}

		delete_transient( 'psstats_data' );
		return true;
	}


	/**
	 * Remove the custom table.
	 *
	 * @since   0.6.0
	 * @version 1.2.4
	 */
	public static function drop() {

		global $wpdb;

		// Remove.
		$wpdb->query( "DROP TABLE IF EXISTS `$wpdb->psstats`" );
	}
}
