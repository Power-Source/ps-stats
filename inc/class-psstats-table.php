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
		$wpdb->tables[] = $table;

		// With prefix.
		$wpdb->$table = $wpdb->get_blog_prefix() . $table;
	}


	/**
	 * Create the table.
	 *
	 * @since   0.6.0
	 * @version 1.2.4
	 */
	public static function create() {

		global $wpdb;

		// If existent.
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$wpdb->psstats'" ) === $wpdb->psstats ) {
			return;
		}

		// Include.
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Create.
		dbDelta(
			"CREATE TABLE `$wpdb->psstats` (
			`id` bigint(20) unsigned NOT NULL auto_increment,
			  `created` date NOT NULL default '0000-00-00',
			  `referrer` varchar(255) NOT NULL default '',
			  `target` varchar(255) NOT NULL default '',
			  PRIMARY KEY  (`id`),
			  KEY `referrer` (`referrer`),
			  KEY `target` (`target`),
			  KEY `created` (`created`)
			);"
		);
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
