<?php

namespace PSStats;

defined( 'ABSPATH' ) || exit;

final class Lifecycle {
	public static function activate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::install_current_site();
				restore_current_blog();
			}
			return;
		}
		self::install_current_site();
	}

	public static function install_current_site(): void {
		try {
			Database::create_table();
			if ( false === get_option( Plugin::OPTION_SETTINGS, false ) ) {
				add_option(
					Plugin::OPTION_SETTINGS,
					array( 'retention' => 30, 'skip_logged_in' => true, 'excluded_paths' => array() ),
					'',
					false
				);
			}
			update_option( Plugin::OPTION_SCHEMA, PS_STATS_SCHEMA_VERSION, false );
			if ( ! wp_next_scheduled( Plugin::CRON_CLEANUP ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', Plugin::CRON_CLEANUP );
			}
			Migration::prepare();
		} catch ( \Throwable $error ) {
			update_option( 'ps_stats_install_error', sanitize_text_field( $error->getMessage() ), false );
		}
	}

	public static function deactivate( bool $network_wide = false ): void {
		if ( is_multisite() && $network_wide ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
				switch_to_blog( (int) $site_id );
				self::clear_schedules();
				restore_current_blog();
			}
			return;
		}
		self::clear_schedules();
	}

	public static function initialize_site( \WP_Site $site ): void {
		if ( ! self::network_active() ) {
			return;
		}
		switch_to_blog( (int) $site->blog_id );
		self::install_current_site();
		restore_current_blog();
	}

	public static function initialize_legacy_site( int $site_id ): void {
		if ( ! self::network_active() ) {
			return;
		}
		switch_to_blog( $site_id );
		self::install_current_site();
		restore_current_blog();
	}

	public static function uninitialize_site( \WP_Site $site ): void {
		self::remove_site_data( (int) $site->blog_id );
	}

	public static function uninitialize_legacy_site( int $site_id ): void {
		self::remove_site_data( $site_id );
	}

	public static function uninstall(): void {
		if ( is_multisite() ) {
			foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $site_id ) {
				self::remove_site_data( (int) $site_id );
			}
			return;
		}
		self::remove_current_site_data();
	}

	private static function remove_site_data( int $site_id ): void {
		switch_to_blog( $site_id );
		self::remove_current_site_data();
		restore_current_blog();
	}

	private static function remove_current_site_data(): void {
		global $wpdb;
		self::clear_schedules();
		$wpdb->query( 'DROP TABLE IF EXISTS ' . Database::table() );
		delete_option( Plugin::OPTION_SETTINGS );
		delete_option( Plugin::OPTION_SCHEMA );
		delete_option( Migration::OPTION );
		delete_option( 'ps_stats_install_error' );
	}

	private static function clear_schedules(): void {
		wp_clear_scheduled_hook( Plugin::CRON_CLEANUP );
		wp_clear_scheduled_hook( Plugin::CRON_MIGRATION );
	}

	private static function network_active(): bool {
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active_for_network( plugin_basename( PS_STATS_FILE ) );
	}
}
