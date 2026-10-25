<?php
/**
 * Run with: wp --skip-plugins --skip-themes eval-file tests/marketpress-source.php
 */

defined( 'ABSPATH' ) || exit;
require_once dirname( __DIR__ ) . '/ps-stats.php';

use PSStats\Integration\MarketPress;
use PSStats\Plugin;

if ( ! function_exists( 'mp_st_get_sales_data' ) ) {
	function mp_st_get_sales_data() {
		throw new RuntimeException( 'The AJAX handler must not be invoked by PS Stats.' );
	}
}

global $wpdb;
$check = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
$temporary = array();
$downloads = $wpdb->get_blog_prefix() . 'mp_download_events';
$authorized = false;
$enabled = true;
$failures = array();
$error_handler = array( Plugin::class, 'database_error' );
$had_error_handler = remove_action( 'ps_stats_database_error', $error_handler );
$record_error = static function ( string $operation ) use ( &$failures ): void {
	$failures[] = $operation;
};
add_action( 'ps_stats_database_error', $record_error );
$allow = static function ( array $caps ) use ( &$authorized ): array {
	$caps['manage_options'] = $authorized;
	return $caps;
};
add_filter( 'user_has_cap', $allow );
add_filter( 'pre_option_' . Plugin::OPTION_SETTINGS, static function () use ( &$enabled ): array { return array( 'marketpress_metrics' => $enabled ); } );

try {
	foreach ( array( $wpdb->posts, $wpdb->postmeta ) as $table ) {
		$schema = $wpdb->get_row( "SHOW CREATE TABLE {$table}", ARRAY_N );
		$check( is_array( $schema ) && false !== $wpdb->query( preg_replace( '/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $schema[1], 1 ) ), 'Cannot isolate source table.' );
		$temporary[] = $table;
	}
	$check( false !== $wpdb->query( "CREATE TEMPORARY TABLE {$downloads} (order_id bigint, user_id bigint, downloaded_at datetime, is_free tinyint, blog_id bigint) ENGINE=InnoDB" ), 'Cannot isolate download events.' );
	$temporary[] = $downloads;
	foreach ( array( array( 990001, 'order_received', '2026-01-15 12:00:00', 20 ), array( 990002, 'order_paid', '2026-01-31 23:59:59', 30 ), array( 990003, 'order_received', '2026-02-01 00:00:00', 40 ) ) as $row ) {
		$check( false !== $wpdb->insert( $wpdb->posts, array( 'ID' => $row[0], 'post_type' => 'mp_order', 'post_status' => $row[1], 'post_date' => $row[2] ) ), 'Cannot insert fixture order.' );
		$check( false !== $wpdb->insert( $wpdb->postmeta, array( 'post_id' => $row[0], 'meta_key' => 'mp_order_total', 'meta_value' => $row[3] ) ), 'Cannot insert fixture order value.' );
	}
	foreach ( array( array( '2026-01-31 23:59:59', 1, get_current_blog_id() ), array( '2026-01-15 12:00:00', 0, get_current_blog_id() ), array( '2026-01-15 12:00:00', 1, get_current_blog_id() + 1 ), array( '2026-02-01 00:00:00', 1, get_current_blog_id() ) ) as $row ) {
		$wpdb->insert( $downloads, array( 'order_id' => 999, 'user_id' => 888, 'downloaded_at' => $row[0], 'is_free' => $row[1], 'blog_id' => $row[2] ) );
	}
	try {
		MarketPress::summary( '2026-01-01', '2026-01-31' );
		throw new LogicException( 'Shop aggregates were readable without permission.' );
	} catch ( RuntimeException $expected ) {
		$check( str_contains( $expected->getMessage(), 'Berechtigung' ), 'Unexpected permission error.' );
	}
	$authorized = true;
	$result = MarketPress::summary( '2026-01-01', '2026-01-31' );
	$check( $result === array( array( 'month' => '2026-01', 'order_value' => 50.0, 'free_downloads' => 1 ) ), 'Source summary does not match source semantics, date boundaries or site isolation.' );
	$check( count( MarketPress::summary( '2026-01-01', '2026-02-28' ) ) === 2, 'Multi-month summary mismatch.' );
	$check( MarketPress::summary( '2025-01-01', '2025-12-31' ) === array(), 'Empty source interval returned artificial rows.' );
	$enabled = false;
	try {
		MarketPress::summary( '2026-01-01', '2026-01-31' );
		throw new LogicException( 'Disabled source returned statistics.' );
	} catch ( RuntimeException $expected ) {
		$check( str_contains( $expected->getMessage(), 'nicht aktiviert' ), 'Disabled source must report why it is unavailable.' );
	}
	$enabled = true;
	try {
		MarketPress::summary( '2026-02-30', '2026-03-01' );
		throw new LogicException( 'Invalid date accepted.' );
	} catch ( InvalidArgumentException $expected ) {
		$check( true, 'Date rejected.' );
	}
	try {
		MarketPress::summary( '2026-02-01', '2026-01-01' );
		throw new LogicException( 'Reversed date interval accepted.' );
	} catch ( InvalidArgumentException $expected ) {
		$check( true, 'Reversed interval rejected.' );
	}
	$break_download_query = static function ( string $sql ) use ( $downloads ): string {
		return str_contains( $sql, "FROM {$downloads}" ) ? "SELECT ps_stats_missing_test_column FROM {$downloads}" : $sql;
	};
	$previous_suppression = $wpdb->suppress_errors();
	add_filter( 'query', $break_download_query );
	try {
		MarketPress::summary( '2026-01-01', '2026-01-31' );
		throw new LogicException( 'Broken download source returned successful statistics.' );
	} catch ( RuntimeException $expected ) {
		$check( str_contains( $expected->getMessage(), 'Download-Quelle' ), 'Source failure must explain the affected source.' );
		$check( $failures === array( 'marketpress_source' ), 'Source failure was not reported through the database error hook.' );
	} finally {
		remove_filter( 'query', $break_download_query );
		$wpdb->suppress_errors( $previous_suppression );
	}
	$check( MarketPress::summary( '2026-01-01', '2026-01-31' ) === $result, 'Source did not recover after a query failure.' );
	echo "PASS: MarketPress read-only monthly source, permission and opt-in gates, source semantics, inclusive dates, site isolation, aggregate-only output and explicit source failures.\n";
} finally {
	foreach ( array_reverse( $temporary ) as $table ) {
		$wpdb->query( "DROP TEMPORARY TABLE {$table}" );
	}
	remove_filter( 'user_has_cap', $allow );
	remove_action( 'ps_stats_database_error', $record_error );
	if ( $had_error_handler ) {
		add_action( 'ps_stats_database_error', $error_handler );
	}
}
