<?php

namespace PSStats;

use PSStats\Admin\AdminPage;
use PSStats\Admin\DashboardWidget;
use PSStats\Api\Provider;
use PSStats\Integration\MarketPress;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	public const OPTION_SETTINGS = 'ps_stats_settings';
	public const OPTION_SCHEMA   = 'ps_stats_schema_version';
	public const CRON_CLEANUP    = 'ps_stats_daily_cleanup';
	public const CRON_MIGRATION  = 'ps_stats_migration_batch';

	public static function boot(): void {
		load_plugin_textdomain( 'ps-stats', false, dirname( plugin_basename( PS_STATS_FILE ) ) . '/languages' );
		load_plugin_textdomain( 'psstats', false, dirname( plugin_basename( PS_STATS_FILE ) ) . '/languages' );
		require_once PS_STATS_DIR . '/inc/psstats-widget/psstats-widget.php';

		add_action( 'init', array( self::class, 'maybe_upgrade' ), 1 );
		add_action( 'template_redirect', array( Tracker::class, 'track' ), 999 );
		add_action( self::CRON_CLEANUP, array( Database::class, 'cleanup' ) );
		add_action( self::CRON_MIGRATION, array( Migration::class, 'run_batch' ) );
		add_action( 'wp_initialize_site', array( Lifecycle::class, 'initialize_site' ), 20, 1 );
		add_action( 'wpmu_new_blog', array( Lifecycle::class, 'initialize_legacy_site' ), 20, 1 );
		add_action( 'wp_uninitialize_site', array( Lifecycle::class, 'uninitialize_site' ), 10, 1 );
		add_action( 'delete_blog', array( Lifecycle::class, 'uninitialize_legacy_site' ), 10, 1 );
		MarketPress::register();
		add_action( 'ps_stats_database_error', array( self::class, 'database_error' ), 10, 1 );

		if ( is_admin() ) {
			AdminPage::register();
			add_action( 'wp_dashboard_setup', array( DashboardWidget::class, 'register' ) );
			add_action( 'admin_init', array( Privacy::class, 'register_text' ) );
		}

		Provider::instance();
		do_action( 'ps_stats_loaded', PS_STATS_VERSION );
	}

	public static function database_error( string $operation ): void {
		update_option( 'ps_stats_database_error', sanitize_key( $operation ), false );
	}

	public static function maybe_upgrade(): void {
		if ( get_option( self::OPTION_SCHEMA ) !== PS_STATS_SCHEMA_VERSION ) {
			Lifecycle::install_current_site();
		}
	}

	/**
	 * @return array{retention:int,skip_logged_in:bool,excluded_paths:array<int,string>,collect_referrers:bool,referrer_domains:array<int,string>,marketpress_metrics:bool}
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
			'collect_referrers' => ! empty( $settings['collect_referrers'] ),
			'referrer_domains' => isset( $settings['referrer_domains'] ) && is_array( $settings['referrer_domains'] ) ? $settings['referrer_domains'] : array(),
			'marketpress_metrics' => ! empty( $settings['marketpress_metrics'] ),
		);
	}
}
