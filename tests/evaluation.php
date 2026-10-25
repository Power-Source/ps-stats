<?php
/**
 * Run with: wp --skip-plugins --skip-themes eval-file tests/evaluation.php
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __DIR__ ) . '/ps-stats.php';
require_once PS_STATS_DIR . '/inc/extended-evaluation-for-psstats/inc/queries.php';
require_once PS_STATS_DIR . '/inc/extended-evaluation-for-psstats/inc/formatting.php';

global $wpdb;
$table = \PSStats\Database::table();
$check = static function ( bool $condition, string $message ): void {
	if ( ! $condition ) {
		throw new \RuntimeException( $message );
	}
};
$original_total = $wpdb->get_var( "SELECT SUM(view_count) FROM {$table}" );
$schema = $wpdb->get_row( "SHOW CREATE TABLE {$table}", ARRAY_N );
$check( is_array( $schema ) && isset( $schema[1] ), 'Cannot read aggregate schema.' );
$check( false !== $wpdb->query( preg_replace( '/^CREATE TABLE /', 'CREATE TEMPORARY TABLE ', $schema[1], 1 ) ), 'Cannot create isolated fixture table: ' . $wpdb->last_error );

try {
	foreach (
		array(
			array( '2024-02-28', '/fixture-a/', '', 7 ),
			array( '2024-02-29', '/fixture-a/', 'example.org', 5 ),
			array( '2024-02-29', '/fixture-b/', 'example.org', 3 ),
			array( '2024-03-31', '/fixture-a/', 'example.net', 11 ),
			array( '2025-01-01', '/fixture-b/', '', 13 ),
		) as $fixture
	) {
		$check( \PSStats\Database::increment( ...$fixture ), 'Fixture insert failed.' );
	}

	$check( eefpsstats_get_years() === array( 2025, 2024 ), 'Year tabs must include all retained years.' );
	$check( eefpsstats_get_views_for_all_years() === array( 2024 => 26, 2025 => 13 ), 'Year totals must sum view_count, not count rows.' );
	$check( eefpsstats_get_views_for_all_months() === array( '2024-02' => 15, '2024-03' => 11, '2025-01' => 13 ), 'Monthly totals mismatch.' );
	$check( eefpsstats_get_views_for_all_days( '/fixture-a/' ) === array( '2024-02-28' => 7, '2024-02-29' => 5, '2024-03-31' => 11 ), 'Daily path filter mismatch.' );
	$check( eefpsstats_get_views_of_post( '/fixture-a/?ignored=yes', '2024-02-01', '2024-02-29' ) === 12, 'Content date and normalized path filter mismatch.' );
	$check( eefpsstats_get_views_of_post( '/missing/' ) === 0, 'Unvisited content must report zero.' );
	$popular = eefpsstats_get_views_of_most_popular_posts();
	$check( $popular === array( array( 'url' => '/fixture-a/', 'count' => 23 ), array( 'url' => '/fixture-b/', 'count' => 16 ) ), 'Content ranking must include all retained data.' );
	$referrers = eefpsstats_get_views_for_all_referrers( '/fixture-a/', '2024-02-01', '2024-02-29' );
	$check( $referrers === array( array( 'host' => 'example.org', 'url' => 'https://example.org', 'count' => 5 ) ), 'Referrer date/path filter mismatch.' );
	$check( array_sum( array_column( eefpsstats_get_views_for_all_referrers(), 'count' ) ) === 19, 'Direct views must not be counted as external referrers.' );
	$check( count( eefpsstats_get_days( 2, 2024 ) ) === 29 && count( eefpsstats_get_days( 2, 2025 ) ) === 28, 'Leap-year calendar mismatch.' );
	$check( eefpsstats_is_valid_date_string( '2024-02-29' ) && ! eefpsstats_is_valid_date_string( '2025-02-29' ) && ! eefpsstats_is_valid_date_string( '2024/02/29' ), 'Date validation mismatch.' );

	$render = static function ( string $view, array $get = array(), array $post = array() ): string {
		$_GET = $get;
		$_POST = $post;
		$_REQUEST = array_merge( $get, $post );
		ob_start();
		try {
			require PS_STATS_DIR . '/inc/extended-evaluation-for-psstats/views/' . $view . '.php';
			return ob_get_contents();
		} finally {
			ob_end_clean();
		}
	};
	$overview = $render( 'dashboard' );
	$check( str_contains( $overview, 'chart-monthly' ) && str_contains( $overview, 'chart-yearly' ) && str_contains( $overview, 'csv-export' ), 'Overview charts or CSV export missing.' );
	$year = $render( 'dashboard', array( 'year' => '2024', 'post' => '/fixture-a/' ) );
	$check( str_contains( $year, 'chart-daily' ) && substr_count( $year, '<th scope="row">' ) === 31, 'Year detail must show all 31 possible days.' );
	$check( str_contains( $year, 'post=%2Ffixture-a%2F' ), 'Year navigation must retain the selected path.' );
	$content = $render( 'content' );
	$check( str_contains( $content, 'chart-popular-content' ) && str_contains( $content, '/fixture-a/' ) && str_contains( $content, '/fixture-b/' ), 'Content chart or complete ranking missing.' );
	$referrer = $render( 'referrers' );
	$check( str_contains( $referrer, 'chart-referrers' ) && str_contains( $referrer, 'example.org' ) && str_contains( $referrer, 'example.net' ), 'Referrer chart or domains missing.' );
	$invalid = $render( 'content', array(), array( 'start' => '2025-01-02', 'end' => '2024-01-01', '_wpnonce' => wp_create_nonce( 'content' ) ) );
	$check( str_contains( $invalid, 'notice-error' ) && ! str_contains( $invalid, 'chart-popular-content' ), 'Invalid ranges must show an error, not unfiltered results.' );

	$check( false !== $wpdb->query( "TRUNCATE TABLE {$table}" ), 'Cannot clear isolated fixture table.' );
	$check( eefpsstats_get_views_of_most_popular_posts( '2020-01-01', '2020-12-31' ) === array(), 'Empty date ranges must return no rows.' );
	$check( eefpsstats_get_views_for_all_referrers() === array(), 'Empty referrer data must not produce artificial entries.' );
	echo "PASS: aggregate totals, complete history, calendar, path/date filters, rankings, charts, CSV controls, invalid ranges and empty data.\n";
} finally {
	$wpdb->query( "DROP TEMPORARY TABLE {$table}" );
	$check( $wpdb->get_var( "SELECT SUM(view_count) FROM {$table}" ) === $original_total, 'Persistent statistics changed during tests.' );
}
