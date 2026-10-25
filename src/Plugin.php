<?php

namespace PSStats;

use PSStats\Admin\AdminPage;
use PSStats\Api\Provider;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public const OPTION_SETTINGS = 'ps_stats_settings';
	public const OPTION_SCHEMA   = 'ps_stats_schema_version';
	public const CRON_CLEANUP    = 'ps_stats_daily_cleanup';
	public const CRON_MIGRATION  = 'ps_stats_migration_batch';

	public static function boot(): void {
		load_plugin_textdomain( 'ps-stats', false, dirname( plugin_basename( PS_STATS_FILE ) ) . '/languages' );

		add_action( 'init', array( self::class, 'maybe_upgrade' ), 1 );
		add_action( 'template_redirect', array( Tracker::class, 'track' ), 999 );
		add_action( self::CRON_CLEANUP, array( Database::class, 'cleanup' ) );
		add_action( self::CRON_MIGRATION, array( Migration::class, 'run_batch' ) );
		add_action( 'wp_initialize_site', array( Lifecycle::class, 'initialize_site' ), 20, 1 );
		add_action( 'wpmu_new_blog', array( Lifecycle::class, 'initialize_legacy_site' ), 20, 1 );
		add_action( 'wp_uninitialize_site', array( Lifecycle::class, 'uninitialize_site' ), 10, 1 );
		add_action( 'delete_blog', array( Lifecycle::class, 'uninitialize_legacy_site' ), 10, 1 );

		if ( is_admin() ) {
			AdminPage::register();
		}

		Provider::instance();
		do_action( 'ps_stats_loaded', PS_STATS_VERSION );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( self::OPTION_SCHEMA ) !== PS_STATS_SCHEMA_VERSION ) {
			Lifecycle::install_current_site();
		}
	}

	/**
	 * @return array{retention:int,skip_logged_in:bool,excluded_paths:array<int,string>}
	 */
	public static function settings(): array {
		$settings = get_option( self::OPTION_SETTINGS, array() );
		$settings = is_array( $settings ) ? $settings : array();

		return array(
			'retention'      => isset( $settings['retention'] ) ? (int) $settings['retention'] : 30,
			'skip_logged_in' => ! isset( $settings['skip_logged_in'] ) || (bool) $settings['skip_logged_in'],
			'excluded_paths' => isset( $settings['excluded_paths'] ) && is_array( $settings['excluded_paths'] )
				? $settings['excluded_paths']
				: array(),
		);
	}
}
