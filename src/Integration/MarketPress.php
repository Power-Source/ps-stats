<?php

namespace PSStats\Integration;

use PSStats\Plugin;

defined( 'ABSPATH' ) || exit;

final class MarketPress {
	public static function register(): void {
		add_filter( 'ps_stats_skip_tracking', array( self::class, 'skip_download_tracking' ) );
	}

	public static function skip_download_tracking( bool $skip ): bool {
		return $skip || isset( $_GET['orderid'] );
	}

	public static function available(): bool {
		return function_exists( 'mp_st_get_sales_data' );
	}

	/**
	 * Read-only, site-wide aggregates matching MarketPress's own statistics.
	 * No order/customer identifiers or individual event timestamps leave SQL.
	 *
	 * @return array<int,array{month:string,order_value:float,free_downloads:int}>
	 */
	public static function summary( string $from, string $to ): array {
		if ( ! current_user_can( 'manage_options' ) ) {
			throw new \RuntimeException( 'PS Stats: Keine Berechtigung für Shop-Statistiken.' );
		}
		if ( ! Plugin::settings()['marketpress_metrics'] || ! self::available() ) {
			throw new \RuntimeException( 'PS Stats: MarketPress-Quelle ist nicht aktiviert oder nicht verfügbar.' );
		}
		foreach ( array( $from, $to ) as $date ) {
			$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
			if ( false === $parsed || $parsed->format( 'Y-m-d' ) !== $date ) {
				throw new \InvalidArgumentException( 'PS Stats: Ungültiger Shop-Zeitraum.' );
			}
		}
		if ( $from > $to ) {
			throw new \InvalidArgumentException( 'PS Stats: Ungültige Shop-Zeitraumreihenfolge.' );
		}
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE_FORMAT(p.post_date,'%%Y-%%m') AS month, SUM(m.meta_value) AS order_value
				FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON p.ID=m.post_id
				WHERE p.post_type=%s AND m.meta_key=%s AND p.post_date >= %s AND p.post_date < %s
				GROUP BY month ORDER BY month",
				'mp_order', 'mp_order_total', $from . ' 00:00:00', gmdate( 'Y-m-d', strtotime( $to . ' +1 day' ) ) . ' 00:00:00'
			),
			ARRAY_A
		);
		self::check_query();
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['month'] ] = array( 'month' => $row['month'], 'order_value' => (float) $row['order_value'], 'free_downloads' => 0 );
		}
		$table = $wpdb->get_blog_prefix() . 'mp_download_events';
		$downloads = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE_FORMAT(downloaded_at,'%%Y-%%m') AS month, COUNT(*) AS downloads FROM {$table}
				WHERE is_free=1 AND blog_id=%d AND downloaded_at >= %s AND downloaded_at < %s
				GROUP BY month ORDER BY month",
				get_current_blog_id(), $from . ' 00:00:00', gmdate( 'Y-m-d', strtotime( $to . ' +1 day' ) ) . ' 00:00:00'
			),
			ARRAY_A
		);
		self::check_query( 'PS Stats: MarketPress-Download-Quelle konnte nicht gelesen werden. Bitte prüfen, ob das MarketPress-Statistikmodul seine Downloadtabelle vollständig initialisiert hat.' );
		foreach ( $downloads as $row ) {
			$result[ $row['month'] ] = $result[ $row['month'] ] ?? array( 'month' => $row['month'], 'order_value' => 0.0, 'free_downloads' => 0 );
			$result[ $row['month'] ]['free_downloads'] = (int) $row['downloads'];
		}
		ksort( $result );
		return array_values( $result );
	}

	private static function check_query( string $message = 'PS Stats: MarketPress-Statistik konnte nicht gelesen werden.' ): void {
		global $wpdb;
		if ( '' !== $wpdb->last_error ) {
			do_action( 'ps_stats_database_error', 'marketpress_source', $wpdb->last_error );
			throw new \RuntimeException( $message );
		}
	}
}
