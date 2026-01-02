<?php
/**
 * PSStats: PSStats_Deactivate class
 *
 * This file contains the derived class for the plugin's deactivation actions.
 *
 * @package   PSStats
 * @since     1.4.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * PSStats_Deactivate
 *
 * @since 1.4.0
 */
class PSStats_Deactivate {

	/**
	 * Plugin deactivation actions
	 *
	 * @since    1.4.0
	 * @version  1.4.0
	 */
	public static function init() {

		// Delete transients.
		delete_transient( 'psstats_data' );

		// Delete cron event.
		wp_clear_scheduled_hook( 'psstats_cleanup' );
	}
}
