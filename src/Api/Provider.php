<?php

namespace PSStats\Api;

use PSStats\Database;
use PSStats\Normalizer;

defined( 'ABSPATH' ) || exit;

final class Provider {
	private static ?self $instance = null;

	public static function instance(): self {
		return self::$instance ??= new self();
	}

	public function availability(): bool {
		return (bool) apply_filters( 'ps_stats_provider_availability', Database::table_exists( Database::table() ) );
	}

	/**
	 * @return array<int,array{path:string,views:int}>
	 */
	public function landing_page_stats( string $from, string $to, array $paths = array() ): array {
		$rows = $this->grouped_query( 'target_path', $from, $to, $paths, false );
		$data = array_map(
			static fn ( array $row ): array => array( 'path' => (string) $row['label'], 'views' => (int) $row['views'] ),
			$rows
		);
		return (array) apply_filters( 'ps_stats_provider_landing_page_stats', $data, $from, $to, $paths );
	}

	/**
	 * @return array<int,array{domain:string,views:int}>
	 */
	public function referrer_stats( string $from, string $to, array $paths = array() ): array {
		$rows = $this->grouped_query( 'referrer_domain', $from, $to, $paths, true );
		$data = array_map(
			static fn ( array $row ): array => array( 'domain' => (string) $row['label'], 'views' => (int) $row['views'] ),
			$rows
		);
		return (array) apply_filters( 'ps_stats_provider_referrer_stats', $data, $from, $to, $paths );
	}

	/**
	 * @return array{oldest:?string,newest:?string}
	 */
	public function date_bounds(): array {
		return (array) apply_filters( 'ps_stats_provider_date_bounds', Database::date_bounds() );
	}

	/**
	 * @return array<int,array{date:string,views:int}>
	 */
	public function daily_visits( string $from, string $to, array $paths = array() ): array {
		global $wpdb;
		list( $from, $to ) = $this->dates( $from, $to );
		$args = array( $from, $to );
		$where = '';
		if ( $paths ) {
			$paths = array_values( array_unique( array_map( array( Normalizer::class, 'target_path' ), $paths ) ) );
			$where = ' AND target_path IN (' . implode( ',', array_fill( 0, count( $paths ), '%s' ) ) . ')';
			$args = array_merge( $args, $paths );
		}
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT stat_date AS date, SUM(view_count) AS views FROM ' . Database::table() .
				' WHERE stat_date BETWEEN %s AND %s' . $where . ' GROUP BY stat_date ORDER BY stat_date ASC',
				...$args
			),
			ARRAY_A
		);
		$this->check_query();
		return array_map(
			static fn ( array $row ): array => array( 'date' => (string) $row['date'], 'views' => (int) $row['views'] ),
			is_array( $rows ) ? $rows : array()
		);
	}

	/**
	 * @return array{views:int,sales:int,paid_sales:int,revenue:float,downloads:int}
	 */
	public function product_metrics( int $product_id, string $from, string $to ): array {
		global $wpdb;
		list( $from, $to ) = $this->dates( $from, $to );
		$metrics = array( 'views', 'sales', 'paid_sales', 'revenue', 'downloads' );
		$rows    = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT metric, SUM(metric_value) AS value FROM ' . Database::metrics_table() .
				' WHERE stat_date BETWEEN %s AND %s AND dimension_type = %s AND dimension_id = %d' .
				' AND metric IN (' . implode( ',', array_fill( 0, count( $metrics ), '%s' ) ) . ') GROUP BY metric',
				$from,
				$to,
				'product',
				$product_id,
				...$metrics
			),
			ARRAY_A
		);
		$this->check_query();
		$data = array( 'views' => 0, 'sales' => 0, 'paid_sales' => 0, 'revenue' => 0.0, 'downloads' => 0 );
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$metric = (string) $row['metric'];
			$data[ $metric ] = 'revenue' === $metric ? (float) $row['value'] : (int) $row['value'];
		}

		return (array) apply_filters( 'ps_stats_provider_product_metrics', $data, $product_id, $from, $to );
	}

	private function grouped_query( string $column, string $from, string $to, array $paths, bool $external_only ): array {
		global $wpdb;
		list( $from, $to ) = $this->dates( $from, $to );
		$args             = array( $from, $to );
		$where            = 'stat_date BETWEEN %s AND %s';
		$paths            = array_values( array_unique( array_map( array( Normalizer::class, 'target_path' ), $paths ) ) );
		if ( array() !== $paths ) {
			$where .= ' AND target_path IN (' . implode( ',', array_fill( 0, count( $paths ), '%s' ) ) . ')';
			$args   = array_merge( $args, $paths );
		}
		if ( $external_only ) {
			$where .= " AND referrer_domain <> ''";
		}

		$sql  = "SELECT {$column} AS label, SUM(view_count) AS views FROM " . Database::table() .
			" WHERE {$where} GROUP BY {$column} ORDER BY views DESC, {$column} ASC";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
		$this->check_query();
		return is_array( $rows ) ? $rows : array();
	}

	private function check_query(): void {
		global $wpdb;
		if ( '' !== $wpdb->last_error ) {
			do_action( 'ps_stats_database_error', 'provider', $wpdb->last_error );
			throw new \RuntimeException( 'PS Stats: Statistikdaten konnten nicht gelesen werden.' );
		}
	}

	private function dates( string $from, string $to ): array {
		if ( ! $this->valid_date( $from ) || ! $this->valid_date( $to ) || $from > $to ) {
			throw new \InvalidArgumentException( 'PS Stats: Ungültiger Statistikzeitraum.' );
		}
		return array( $from, $to );
	}

	private function valid_date( string $date ): bool {
		$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		return false !== $parsed && $parsed->format( 'Y-m-d' ) === $date;
	}
}
