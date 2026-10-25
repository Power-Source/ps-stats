<?php

namespace PSStats;

defined( 'ABSPATH' ) || exit;

final class Tracker {
	private const BOT_MARKERS = array(
		'bot', 'crawler', 'spider', 'slurp', 'bingpreview', 'facebookexternalhit',
		'monitor', 'uptime', 'headless', 'curl/', 'wget/', 'python-requests',
	);

	public static function track(): void {
		if ( self::should_skip() ) {
			return;
		}

		$path     = Normalizer::target_path( $_SERVER['REQUEST_URI'] ?? '/' );
		$settings = Plugin::settings();
		foreach ( $settings['excluded_paths'] as $excluded ) {
			if ( '/' === $excluded || $path === $excluded || str_starts_with( $path, rtrim( $excluded, '/' ) . '/' ) ) {
				return;
			}
		}

		$domain = Normalizer::referrer_domain( $_SERVER['HTTP_REFERER'] ?? '' );
		$date   = current_time( 'Y-m-d' );
		if ( Database::increment( $date, $path, $domain ) ) {
			do_action( 'ps_stats_view_counted', $date, $path, $domain );
		} else {
			global $wpdb;
			do_action( 'ps_stats_database_error', 'track', $wpdb->last_error );
		}
	}

	private static function should_skip(): bool {
		$method = strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) );
		if ( 'GET' !== $method || is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}
		if ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) || is_feed() || is_robots() || is_trackback() || is_search() || is_404() || is_preview() ) {
			return true;
		}
		if ( Plugin::settings()['skip_logged_in'] && is_user_logged_in() ) {
			return true;
		}

		$user_agent = strtolower( (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) );
		if ( '' === $user_agent ) {
			return true;
		}
		foreach ( self::BOT_MARKERS as $marker ) {
			if ( str_contains( $user_agent, $marker ) ) {
				return true;
			}
		}

		return (bool) apply_filters( 'ps_stats_skip_tracking', false );
	}
}
