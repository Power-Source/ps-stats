<?php
/**
 * PSStats: PSStats_Cron class
 *
 * This file contains the derived class for the plugin's cron features.
 *
 * @package   PSStats
 * @since     1.4.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * PSStats_Cron
 *
 * @since 1.4.0
 */
class PSStats_Cron extends PSStats {

	/**
	 * Cleanup obsolete DB values
	 *
	 * @since    0.3.0
	 * @version  1.4.0
	 */
	public static function cleanup_data() {

		// Global.
		global $wpdb;

		// Remove items.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM `$wpdb->psstats` WHERE created <= SUBDATE(%s, %d)",
				current_time( 'Y-m-d' ),
				(int) self::$_options['days']
			)
		);

		// Optimize DB.
		$wpdb->query(
			"OPTIMIZE TABLE `$wpdb->psstats`"
		);
	}
}
