<?php
/**
 * PSStats: PSStats_Install class
 *
 * This file contains the derived class for the plugin's installation features.
 *
 * @package   PSStats
 * @since     0.1
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * PSStats_Install
 *
 * @since   0.1
 * @version 2016-12-20
 */
class PSStats_Install {

	/**
	 * Plugin activation handler.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $network_wide Whether the plugin was activated network-wide or not.
	 */
	public static function init( $network_wide = false ) {

		if ( $network_wide && is_multisite() ) {
			$sites = get_sites();

			// Create tables for each site in a network.
			foreach ( $sites as $site ) {
				// Convert object to array.
				$site = (array) $site;

				switch_to_blog( $site['blog_id'] );
				self::_apply();
			}

			restore_current_blog();
		} else {
			self::_apply();
		}
	}

	/**
	 * Sets up the plugin for a newly created site on Multisite.
	 *
	 * @since 1.4.4
	 *
	 * @param int $site_id Site ID.
	 */
	public static function init_site( $site_id ) {

		switch_to_blog( (int) $site_id );

		self::_apply();

		restore_current_blog();
	}

	/**
	 * Creates the database tables needed for the plugin.
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 */
	private static function _apply() {

		// Cleanup any leftover transients.
		delete_transient( 'psstats_data' );

		// Set up the cron event.
		if ( ! wp_next_scheduled( 'psstats_cleanup' ) ) {
			wp_schedule_event(
				time(),
				'daily',
				'psstats_cleanup'
			);
		}

		// Create the actual tables.
		PSStats_Table::init();
		PSStats_Table::create();
	}
}
