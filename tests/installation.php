<?php
/**
 * Run with: wp --skip-plugins --skip-themes eval-file tests/installation.php
 */

defined( 'ABSPATH' ) || exit;
require_once dirname( __DIR__ ) . '/ps-stats.php';

global $wpdb;
$check = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
$prefix = $wpdb->base_prefix;
$had_table = isset( $wpdb->psstats );
$original_table = $had_table ? $wpdb->psstats : null;
$registered_tables = $wpdb->tables;
$fixture_prefix = $prefix . 'psstats_test_' . bin2hex( random_bytes( 4 ) ) . '_';
$fixture = $wpdb->get_blog_prefix() . 'psstats';
$temporary_options = false;
$sql_mode = $wpdb->get_var( 'SELECT @@SESSION.sql_mode' );
$clear_cache = static function (): void {
	foreach ( array( 'alloptions', 'notoptions', 'cron', 'psstats', '_transient_psstats_data', '_transient_timeout_psstats_data' ) as $key ) {
		wp_cache_delete( $key, 'options' );
	}
	wp_cache_delete( 'psstats_data', 'transient' );
};
wp_cache_add_non_persistent_groups( array( 'options', 'transient' ) );
$force_failure = static function ( string $sql ) use ( $wpdb ): string {
	return str_starts_with( $sql, "CREATE TABLE `$wpdb->psstats`" ) ? 'PSSTATS_TEST_FORCED_FAILURE' : $sql;
};

try {
	// Option writes stay connection-local; statistics use a disposable table prefix.
	$schema = $wpdb->get_row( "SHOW CREATE TABLE `$wpdb->options`", ARRAY_N );
	$check( is_array( $schema ), 'Cannot read options schema.' );
	$check( false !== $wpdb->query( preg_replace( '/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $schema[1], 1 ) ), 'Cannot isolate options.' );
	$temporary_options = true;
	$clear_cache();
	$wpdb->base_prefix = $fixture_prefix;
	PSStats_Table::init();
	$fixture = $wpdb->psstats;
	$check( str_starts_with( $fixture, $fixture_prefix ), 'Fixture table must use the isolated prefix.' );
	$check( false !== $wpdb->query( "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,NO_ENGINE_SUBSTITUTION'" ), 'Cannot enable strict date validation.' );
	set_transient( 'psstats_data', array( 'stale' => true ), 900 );

	PSStats::init();
	$exists = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $fixture ) );
	$check( $wpdb->get_var( $exists ) === $fixture, 'Loading an already-active plugin must create missing storage.' );
	$check( false === get_transient( 'psstats_data' ), 'Recreated storage must invalidate cached statistics.' );
	$check( false !== wp_next_scheduled( 'psstats_cleanup' ), 'Automatic installation must schedule cleanup.' );
	$check( null === PSStats_Dashboard::get_stats( true ) && '' === $wpdb->last_error, 'Empty dashboard queries must succeed.' );
	$check(
		false !== $wpdb->insert( $fixture, array( 'created' => current_time( 'Y-m-d' ), 'target' => '/fixture/', 'referrer' => 'https://example.org/source' ) ),
		'The original per-view schema must accept tracking rows under strict SQL modes.'
	);
	update_option( 'psstats', array( 'days' => 42 ), false );
	PSStats::init();
	PSStats_Install::init();
	$check( 1 === (int) $wpdb->get_var( "SELECT COUNT(*) FROM `$fixture`" ), 'Repeated initialization or activation must preserve statistics.' );
	$check( array( 'days' => 42 ) === get_option( 'psstats' ), 'Initialization must preserve existing settings.' );
	$events = 0;
	foreach ( _get_cron_array() as $hooks ) {
		$events += isset( $hooks['psstats_cleanup'] ) ? count( $hooks['psstats_cleanup'] ) : 0;
	}
	$check( 1 === $events, 'Repeated initialization must not duplicate cleanup events.' );
	$stats = PSStats_Dashboard::get_stats( true );
	$check(
		is_array( $stats ) && 1 === (int) $stats['visits'][0]['count']
		&& '/fixture/' === $stats['target'][0]['url'] && 'example.org' === $stats['referrer'][0]['host']
		&& '' === $wpdb->last_error,
		'Dashboard queries must read the original schema after repair.'
	);

	$check( false !== $wpdb->query( "DROP TABLE `$fixture`" ), 'Cannot remove isolated fixture.' );
	wp_clear_scheduled_hook( 'psstats_cleanup' );
	add_filter( 'query', $force_failure );
	$errors_before = $wpdb->suppress_errors( true );
	try {
		$installed = PSStats_Install::maybe_install();
	} finally {
		$wpdb->suppress_errors( $errors_before );
		remove_filter( 'query', $force_failure );
	}
	$check( false === $installed, 'Failed creation must not report success.' );
	$check( false !== has_action( 'admin_notices', array( 'PSStats_Install', 'print_install_error' ) ), 'Failed creation must register a visible admin error.' );
	$check( false === wp_next_scheduled( 'psstats_cleanup' ), 'Failed creation must not schedule cleanup against missing storage.' );
	PSStats::init();
	$check( $wpdb->get_var( $exists ) === $fixture, 'A failed installation must be retried on the next initialization.' );
	$check( false === has_action( 'admin_notices', array( 'PSStats_Install', 'print_install_error' ) ), 'Successful recovery must clear the installation notice.' );
	echo "PASS: original schema repair, strict SQL dates, preserved rows/settings, dashboard queries, cleanup and failure recovery.\n";
} finally {
	remove_filter( 'query', $force_failure );
	if ( str_starts_with( $fixture, $fixture_prefix ) ) {
		$wpdb->query( "DROP TABLE IF EXISTS `$fixture`" );
	}
	$wpdb->query( $wpdb->prepare( 'SET SESSION sql_mode = %s', $sql_mode ) );
	$wpdb->base_prefix = $prefix;
	$wpdb->tables = $registered_tables;
	if ( $had_table ) {
		$wpdb->psstats = $original_table;
	} else {
		unset( $wpdb->psstats );
	}
	if ( $temporary_options ) {
		$wpdb->query( "DROP TEMPORARY TABLE `$wpdb->options`" );
	}
	$clear_cache();
}
