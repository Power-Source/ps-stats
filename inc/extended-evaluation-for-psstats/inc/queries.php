<?php
/**
 * Data queries.
 *
 * @package extended-evaluation-for-psstats
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

function eefpsstats_check_query() {
	global $wpdb;
	if ( '' !== $wpdb->last_error ) {
		do_action( 'ps_stats_database_error', 'evaluation', $wpdb->last_error );
		wp_die( esc_html__( 'Die Statistikdaten konnten nicht geladen werden. Bitte die Datenbankverbindung prüfen.', 'ps-stats' ), 500 );
	}
}

function eefpsstats_get_period( $start = '', $end = '' ) {
	if ( '' === $start && '' === $end ) {
		$bounds = \PSStats\Api\Provider::instance()->date_bounds();
		eefpsstats_check_query();
		$today = current_time( 'Y-m-d' );
		return array( $bounds['oldest'] ?: $today, $bounds['newest'] ?: $today );
	}
	if ( ! eefpsstats_is_valid_date_string( $start ) || ! eefpsstats_is_valid_date_string( $end ) || $start > $end ) {
		wp_die( esc_html__( 'Bitte einen gültigen Zeitraum angeben.', 'ps-stats' ), 400 );
	}
	return array( $start, $end );
}

/**
 * Returns the numeric values for all days in a month.
 *
 * @param int $month the month as value between 1 and 12 (default: 1).
 * @param int $year a year (default: 0).
 *
 * @return array an array of integers (1, 2, ..., 31).
 */
function eefpsstats_get_days( $month = 1, $year = 0 ) {
	if ( 2 === $month ) {
		if ( checkdate( 2, 29, $year ) ) {
			return range( 1, 29 );
		}

		return range( 1, 28 );
	}

	if ( in_array( $month, array( 4, 6, 9, 11 ), true ) ) {
		return range( 1, 30 );
	}

	return range( 1, 31 );
}

/**
 * Returns the numeric values of all months.
 *
 * @return array an array of integers (1, 2, ..., 12)
 */
function eefpsstats_get_months() {
	return range( 1, 12 );
}

/**
 * Returns the years PSStats has collected data for in descending order.
 *
 * @return array an array of integers (e. g. 2016, 2015)
 */
function eefpsstats_get_years() {
	$years = array_map( 'intval', array_keys( eefpsstats_get_views_for_all_years() ) );
	rsort( $years );
	return $years;
}

/**
 * Returns the views for all days.
 * If the given URL is not the empty string, the result is restricted to the given post.
 *
 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
 *
 * @return array an array with date as key and views as value
 */
function eefpsstats_get_views_for_all_days( $post_url = '' ) {
	static $cache = array();
	$key = get_current_blog_id() . ':' . $post_url;
	if ( ! isset( $cache[ $key ] ) ) {
		list( $from, $to ) = eefpsstats_get_period();
		$rows = \PSStats\Api\Provider::instance()->daily_visits( $from, $to, '' === $post_url ? array() : array( $post_url ) );
		eefpsstats_check_query();
		$cache[ $key ] = array_column( $rows, 'views', 'date' );
	}
	return $cache[ $key ];
}

/**
 * Returns the views for one day. If the date does not exists (e. g. 30th February),
 * this method returns -1.
 *
 * @param array $views_for_all_days an array with the daily views.
 * @param int   $year the year.
 * @param int   $month the month.
 * @param int   $day the day.
 *
 * @return int number the number of views (or -1 if the date is invalid).
 */
function eefpsstats_get_daily_views( $views_for_all_days, $year, $month, $day ) {
	if ( checkdate( $month, $day, $year ) ) {
		$date = sprintf(
			'%s-%s-%s',
			str_pad( $year, 4, '0', STR_PAD_LEFT ),
			str_pad( $month, 2, '0', STR_PAD_LEFT ),
			str_pad( $day, 2, '0', STR_PAD_LEFT )
		);
		if ( array_key_exists( $date, $views_for_all_days ) ) {
			return $views_for_all_days[ $date ];
		}

		return 0;
	}

	// No valid date.
	return -1;
}

/**
 * Returns the views for all months.
 * If the given URL is not the empty string, the result is restricted to the given post.
 *
 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
 *
 * @return array an array with month as key and views as value.
 */
function eefpsstats_get_views_for_all_months( $post_url = '' ) {
	$views_for_all_months = [];
	foreach ( eefpsstats_get_views_for_all_days( $post_url ) as $date => $views ) {
		$month = substr( $date, 0, 7 );
		$views_for_all_months[ $month ] = ( $views_for_all_months[ $month ] ?? 0 ) + $views;
	}

	return $views_for_all_months;
}

/**
 * Returns the views for one month.
 *
 * @param array $views_for_all_months an array with the monthly views.
 * @param int   $year the year.
 * @param int   $month the month.
 *
 * @return int the view for the given month.
 */
function eefpsstats_get_monthly_views( $views_for_all_months, $year, $month ) {
	$date = sprintf(
		'%s-%s',
		str_pad( $year, 4, '0', STR_PAD_LEFT ),
		str_pad( $month, 2, '0', STR_PAD_LEFT )
	);
	if ( array_key_exists( $date, $views_for_all_months ) ) {
		return $views_for_all_months[ $date ];
	}

	return 0;
}

/**
 * Returns the average daily views in the given month.
 *
 * @param array $views_for_all_months an array with the monthly views.
 * @param int   $year the year.
 * @param int   $month the month.
 *
 * @return float the average daily views in the given month.
 */
function eefpsstats_get_average_daily_views_of_month( $views_for_all_months, $year, $month ) {
	$views_in_month = eefpsstats_get_monthly_views( $views_for_all_months, $year, $month );
	$days_in_month  = eefpsstats_is_current_month( $year, $month )
		? (int) current_time( 'd' )
		: count( eefpsstats_get_days( $month, $year ) );

	return round( $views_in_month / $days_in_month );
}

/**
 * Returns an array with the daily views for all days in the given month.
 * If the given month is the current one, just the views for past days and the current day is included.
 *
 * @param array $views_for_all_days an array with the daily views.
 * @param int   $year the year.
 * @param int   $month the month.
 *
 * @return array array with the daily views for all days in the given day.
 */
function eefpsstats_get_daily_views_of_month( $views_for_all_days, $year, $month ) {
	if ( eefpsstats_is_current_month( $year, $month ) ) {
		$days = range( 1, (int) current_time( 'd' ) );
	} else {
		$days = eefpsstats_get_days( $month, $year );
	}

	$views = [];
	foreach ( $days as $day ) {
		$views[] = (int) eefpsstats_get_daily_views( $views_for_all_days, $year, $month, $day );
	}

	return $views;
}

/**
 * Returns whether the given month is the current one.
 *
 * @param int $year a year.
 * @param int $month a month.
 *
 * @return bool true if and only if the given month is the current one.
 */
function eefpsstats_is_current_month( $year, $month ) {
	$current_year  = (int) current_time( 'Y' );
	$current_month = (int) current_time( 'm' );

	return ( $current_year === $year && $current_month === $month );
}

/**
 * Returns the views for all years.
 *
 * If the given URL is not the empty string, the result is restricted to the given post.
 *
 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
 *
 * @return array an array with the year as key and views as value.
 */
function eefpsstats_get_views_for_all_years( $post_url = '' ) {
	$views_for_all_years = [];
	foreach ( eefpsstats_get_views_for_all_months( $post_url ) as $date => $views ) {
		$year = substr( $date, 0, 4 );
		$views_for_all_years[ $year ] = ( $views_for_all_years[ $year ] ?? 0 ) + $views;
	}

	return $views_for_all_years;
}

/**
 * Returns the views for one year. If the date does not exists (e. g. 30th February),
 * this method returns -1.
 *
 * @param array $views_for_all_years an array with the yearly views.
 * @param int   $year the year.
 *
 * @return int the views of the given year.
 */
function eefpsstats_get_yearly_views( $views_for_all_years, $year ) {
	$year_key = str_pad( $year, 4, '0', STR_PAD_LEFT );
	if ( array_key_exists( $year_key, $views_for_all_years ) ) {
		return $views_for_all_years[ $year_key ];
	}

	return 0;
}

/**
 * Returns the most popular posts with their views count (in the date period if set).
 *
 * @param string $start the start date of the period.
 * @param string $end the end date of the period.
 *
 * @return array an array with the most popular posts, ordered by view count.
 */
function eefpsstats_get_views_of_most_popular_posts( $start = '', $end = '' ) {
	list( $from, $to ) = eefpsstats_get_period( $start, $end );
	$rows = \PSStats\Api\Provider::instance()->landing_page_stats( $from, $to );
	eefpsstats_check_query();
	return array_map(
		static fn ( array $row ): array => array( 'url' => $row['path'], 'count' => $row['views'] ),
		$rows
	);
}

/**
 * Returns the number of views for the post with the given URL (in the date period if set).
 *
 * @param string $url the post URL.
 * @param string $start the start date of the period.
 * @param string $end the end date of the period.
 *
 * @return int the number of views for the post.
 */
function eefpsstats_get_views_of_post( $url, $start = '', $end = '' ) {
	static $cache = array();
	$key = get_current_blog_id() . ':' . $start . ':' . $end;
	if ( ! isset( $cache[ $key ] ) ) {
		$cache[ $key ] = array_column( eefpsstats_get_views_of_most_popular_posts( $start, $end ), 'count', 'url' );
	}
	return $cache[ $key ][ \PSStats\Normalizer::target_path( $url ) ] ?? 0;
}

/**
 * Returns the most popular referrers with their views count.
 * If the given URL is not the empty string, the result is restricted to the given post.
 *
 * @param string $post_url the URL of the post to select for (or the empty string for all posts).
 * @param string $start the start date of the period.
 * @param string $end the end date of the period.
 *
 * @return array an array with the most referrers, ordered by view count
 */
function eefpsstats_get_views_for_all_referrers( $post_url = '', $start = '', $end = '' ) {
	list( $from, $to ) = eefpsstats_get_period( $start, $end );
	$rows = \PSStats\Api\Provider::instance()->referrer_stats( $from, $to, '' === $post_url ? array() : array( $post_url ) );
	eefpsstats_check_query();
	return array_map(
		static fn ( array $row ): array => array( 'host' => $row['domain'], 'url' => 'https://' . $row['domain'], 'count' => $row['views'] ),
		$rows
	);
}

/**
 * Returns a list of all target URLs.
 *
 * @return array an array of urls
 */
function eefpsstats_get_post_urls() {
	$paths = array_column( eefpsstats_get_views_of_most_popular_posts(), 'url' );
	sort( $paths );
	return array_map( static fn ( string $path ): array => array( 'target' => $path ), $paths );
}
