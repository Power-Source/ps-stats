<?php
/**
 * Run with: wp --skip-plugins --skip-themes eval-file tests/storage-privacy.php
 */

defined( 'ABSPATH' ) || exit;
require_once dirname( __DIR__ ) . '/ps-stats.php';
require_once PS_STATS_DIR . '/inc/psstats-widget/psstats-widget.php';

use PSStats\Database;
use PSStats\Migration;
use PSStats\Plugin;
use PSStats\Privacy;
use PSStats\Tracker;
use PSStats\Integration\MarketPress;

global $wpdb;
$check = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
};
// All writes stay on this connection, including option updates and post fixtures.
wp_cache_add_non_persistent_groups( array( 'options', 'posts', 'post_meta' ) );
$tables = array( Database::table(), Database::metrics_table(), $wpdb->options, $wpdb->posts, $wpdb->postmeta );
$temporary = array();
$original = $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() );
$clear = static function (): void {
	foreach ( array( 'alloptions', 'notoptions', Plugin::OPTION_SETTINGS, Migration::OPTION, 'ps_stats_cleanup_error', 'ps_stats_cleanup_last_success' ) as $key ) {
		wp_cache_delete( $key, 'options' );
	}
};
$save_settings = static function ( array $changes ) use ( $check ): void {
	$value = array_merge( array( 'retention' => 365, 'skip_logged_in' => true, 'excluded_paths' => array(), 'collect_referrers' => false, 'referrer_domains' => array(), 'marketpress_metrics' => false ), $changes );
	update_option( Plugin::OPTION_SETTINGS, $value, false );
	$check( get_option( Plugin::OPTION_SETTINGS ) === $value, 'Fixture settings did not persist.' );
};

try {
	foreach ( $tables as $table ) {
		$schema = $wpdb->get_row( "SHOW CREATE TABLE {$table}", ARRAY_N );
		$check( is_array( $schema ), 'Missing fixture schema: ' . $table );
		$check( false !== $wpdb->query( preg_replace( '/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $schema[1], 1 ) ), 'Cannot isolate ' . $table );
		$temporary[] = $table;
	}
	$legacy = Database::legacy_table();
	$check( false !== $wpdb->query( "CREATE TEMPORARY TABLE {$legacy} (id bigint PRIMARY KEY, created varchar(10), target varchar(255), referrer varchar(255)) ENGINE=InnoDB" ), 'Cannot create isolated legacy fixture.' );
	$temporary[] = $legacy;
	$clear();
	$save_settings( array() );
	update_option( Migration::OPTION, array( 'status' => 'complete' ), false );
	add_filter( 'pre_option_permalink_structure', static fn () => '/%postname%/' );
	add_filter( 'pre_option_home', static fn () => 'http://example.test' );
	add_filter( 'pre_option_siteurl', static fn () => 'http://example.test' );
	$post_id = 990001;
	$check( false !== $wpdb->insert( $wpdb->posts, array( 'ID' => $post_id, 'post_name' => 'public-fixture', 'post_title' => 'Public fixture', 'post_type' => 'post', 'post_status' => 'publish', 'post_password' => '' ) ), 'Cannot create public fixture.' );
	add_filter( 'url_to_postid', static fn ( $url ) => $url === 'http://example.test/public-fixture/' ? 'http://example.test/?p=990001' : $url );
	// Core resolves the isolated post through its normal URL query.
	$check( Privacy::route( '/public-fixture/?ignore=yes' ) === '/public-fixture/', 'Canonical public route was not accepted.' );
	foreach ( array( '/members/alice/', '/shop/checkout/', '/orders/token/', '/unknown/token/', '/favicon.ico', '/author/alice/', '/download/token/', 'https://other.test/public-fixture/', '/?p=123', '/?%70=123', '/?p[]=123', '/?token=private', '/?to%6ben=private', 'http://[', 'file:///' ) as $path ) {
		$check( null === Privacy::route( $path ), 'Sensitive or unknown route was accepted: ' . $path );
	}
	$check( '/' === Privacy::route( '/' ), 'Homepage should be accepted.' );
	$subsite = static fn () => 'http://example.test/subsite';
	add_filter( 'pre_option_home', $subsite, 20 );
	$check( \PSStats\Normalizer::local_url( '/subsite/public-fixture/' ) === 'http://example.test/subsite/public-fixture/', 'Subdirectory URLs duplicated the site path.' );
	$check( Privacy::route( '/subsite/' ) === '/subsite/', 'Subdirectory homepage was not accepted.' );
	Database::increment( current_time( 'Y-m-d' ), '/subsite/', '', 3 );
	$check( PSStats_Posts::get_post_ids( 'page', 0, 1, 0 ) === array( 0 => 3 ), 'Subdirectory homepage disappeared from public rankings.' );
	$check( Database::delete_all(), 'Cannot clear subdirectory fixture.' );
	remove_filter( 'pre_option_home', $subsite, 20 );
	$save_settings( array( 'excluded_paths' => array( '/public-fixture' ) ) );
	$check( null === Privacy::route( '/public-fixture/' ), 'Explicit subtree exclusion failed.' );
	$save_settings( array( 'collect_referrers' => true, 'referrer_domains' => array( 'example.org' ) ) );
	$check( Privacy::referrer( 'https://example.org/private?token=x' ) === 'example.org', 'Approved referrer should only store its domain.' );
	$check( Privacy::referrer( 'https://alice.example.org/' ) === '', 'Unapproved personal subdomain was retained.' );
	$save_settings( array() );
	$check( Privacy::referrer( 'https://example.org/' ) === '', 'Referrers must default off.' );
	$check( str_contains( Privacy::text(), 'keine rechtliche Freigabe' ), 'Privacy text must not claim legal certification.' );
	$wpdb->update( $wpdb->posts, array( 'post_password' => 'fixture-password' ), array( 'ID' => $post_id ) );
	clean_post_cache( $post_id );
	$check( null === Privacy::route( '/public-fixture/' ), 'Password-protected content must be excluded.' );
	$wpdb->update( $wpdb->posts, array( 'post_password' => '' ), array( 'ID' => $post_id ) );
	clean_post_cache( $post_id );

	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 test browser';
	$_SERVER['REQUEST_URI'] = '/';
	$_SERVER['HTTP_REFERER'] = 'https://unapproved.example/private?token=not-stored';
	Tracker::track();
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === 1, 'Eligible GET was not counted.' );
	$_SERVER['REQUEST_METHOD'] = 'POST';
	Tracker::track();
	$_SERVER['REQUEST_METHOD'] = 'GET';
	$_SERVER['HTTP_USER_AGENT'] = 'crawler bot';
	Tracker::track();
	$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 test browser';
	$_SERVER['REQUEST_URI'] = '/members/alice/';
	Tracker::track();
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === 1, 'POST, bots or private routes were counted.' );
	$check( Database::delete_all(), 'Cannot clear tracking fixture.' );

	Database::increment( '2024-01-01', '/', '', 3 );
	Database::increment( '2024-01-01', '/', 'example.org', 7 );
	Database::increment( '2026-10-25', '/', '', 5 );
	Database::increment_metric( '2024-01-01', 'product', 1, 'views', 4 );
	$check( Database::delete_referrers(), 'Referrer removal failed.' );
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === 15, 'Referrer removal lost views.' );
	$check( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Database::table() . " WHERE referrer_domain<>''" ) === 0, 'Referrer domains remain.' );
	$check( ! Database::transaction( static function (): bool { Database::increment( '2026-10-25', '/', '', 100 ); return false; } ), 'Failing transaction must return false.' );
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === 15, 'Transaction rollback failed.' );
	$check( Database::delete_before( '2025-01-01' ), 'Date deletion failed.' );
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === 5, 'Date deletion boundary mismatch.' );
	$check( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Database::metrics_table() ) === 0, 'Date deletion left metrics behind.' );

	$state = array( 'status' => 'pending', 'cursor' => 0, 'processed' => 0, 'source_count' => 3, 'max_id' => 3 );
	update_option( Migration::OPTION, $state, false );
	$check( ! Database::delete_all(), 'Destructive cleanup must pause during migration.' );
	$check( false !== $wpdb->insert( $legacy, array( 'id' => 1, 'created' => '2026-10-01', 'target' => '/', 'referrer' => '' ) ), 'Cannot create legacy row.' );
	$wpdb->insert( $legacy, array( 'id' => 2, 'created' => '2026-10-01', 'target' => '/members/alice/', 'referrer' => '' ) );
	$wpdb->insert( $legacy, array( 'id' => 3, 'created' => '2026-10-02', 'target' => '/', 'referrer' => 'https://example.org/private' ) );
	$force_failure = static function ( string $sql ) use ( $wpdb ): string {
		return str_starts_with( $sql, "UPDATE {$wpdb->options} SET option_value=" ) && str_contains( $sql, Migration::OPTION ) ? 'PS_STATS_TEST_FORCED_FAILURE' : $sql;
	};
	add_filter( 'query', $force_failure );
	$errors_before = $wpdb->suppress_errors( true );
	Migration::run_batch();
	$wpdb->suppress_errors( $errors_before );
	remove_filter( 'query', $force_failure );
	$check( Migration::state()['status'] === 'failed' && Migration::state()['cursor'] === 0, 'Failed cursor update must not advance migration.' );
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === 5, 'Failed cursor update must roll back aggregate writes.' );
	$wpdb->update( $legacy, array( 'created' => '2026-02-30' ), array( 'id' => 3 ) );
	Migration::run_batch();
	$check( Migration::state()['status'] === 'failed' && Migration::state()['cursor'] === 0, 'Invalid historical dates must fail without advancing the cursor.' );
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === 5, 'Invalid date batch must not leave partial aggregates.' );
	$wpdb->update( $legacy, array( 'created' => '2026-10-02' ), array( 'id' => 3 ) );
	Migration::run_batch();
	$check( Migration::state()['processed'] === 3 && Migration::state()['skipped'] === 1, 'Migration cursor or privacy exclusions mismatch.' );
	$after = (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() );
	Migration::run_batch();
	Migration::run_batch();
	$check( Migration::state()['status'] === 'complete', 'Migration did not finish.' );
	$check( (int) $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === $after, 'Repeated migration double-counted views.' );
	$check( (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$legacy}" ) === 3, 'Source table must remain for explicit review.' );
	Database::increment( '2001-01-01', '/', '', 8 );
	Database::cleanup();
	$check( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Database::table() . " WHERE stat_date='2001-01-01'" ) === 0, 'Automatic retention did not delete expired rows.' );
	$check( (int) get_option( 'ps_stats_cleanup_last_success' ) > 0, 'Cleanup did not record success.' );
	$check( Database::delete_all(), 'Completed migration should permit deletion.' );
	$check( (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Database::table() ) === 0, 'Delete all failed.' );

	$check( shortcode_exists( 'psstats-count' ) && shortcode_exists( 'psstats-count-sum' ), 'Compatibility shortcodes are missing.' );
	Database::increment( current_time( 'Y-m-d' ), '/public-fixture/', '', 9 );
	$check( get_psstats_count_sum() === 9, 'Compatibility sum has an off-by-one error.' );
	$check( get_psstats_count( $post_id ) === 9, 'Compatibility post count mismatch.' );
	$check( count( PSStats_Posts::get_posts( 'post', 0, 5, 0 ) ) === 1, 'Popular widget fixture missing.' );
	$check( str_contains( do_shortcode( '[psstats-count-sum]' ), '9' ), 'Sum shortcode did not render.' );
	$check( has_action( 'mp_checkout/product_sale', array( MarketPress::class, 'record_product_sale' ) ) === false, 'PS Stats must not duplicate sales tracking.' );
	$check( has_action( 'mp_serve_download', array( MarketPress::class, 'record_download' ) ) === false, 'PS Stats must not duplicate download tracking.' );
	try {
		(new \PSStats\Api\Provider())->daily_visits( '2026-02-30', '2026-03-01' );
		throw new RuntimeException( 'Invalid dates were silently accepted.' );
	} catch ( InvalidArgumentException $expected ) {
		$check( true, 'Invalid dates rejected.' );
	}
	$check( ! MarketPress::skip_download_tracking( false ) && MarketPress::skip_download_tracking( true ), 'Skip filter must preserve prior decisions.' );
	$_GET['orderid'] = 'not-stored';
	$check( MarketPress::skip_download_tracking( false ), 'Download endpoints must not create pageviews.' );
	unset( $_GET['orderid'] );

	$check( Database::lock(), 'Cannot acquire maintenance lock.' );
	try {
		$other = new wpdb( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
		$name = 'ps_stats_' . md5( DB_NAME . ':' . Database::table() );
		$check( '0' === (string) $other->get_var( $other->prepare( 'SELECT GET_LOCK(%s,0)', $name ) ), 'Another connection acquired an active maintenance lock.' );
		$other->close();
	} finally {
		Database::unlock();
	}
	echo "PASS: privacy/GET/bot exclusions, referrer opt-in, atomic deletion, cursor rollback, invalid-date recovery, migration replay, source preservation, cleanup, compatibility, API validation and cross-connection locking.\n";
} finally {
	foreach ( array_reverse( $temporary ) as $table ) {
		$wpdb->query( "DROP TEMPORARY TABLE {$table}" );
	}
	$clear();
	wp_cache_delete( $post_id ?? 990001, 'posts' );
	$check( $wpdb->get_var( 'SELECT SUM(view_count) FROM ' . Database::table() ) === $original, 'Persistent statistics changed during testing.' );
}
