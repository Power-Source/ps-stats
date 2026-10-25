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
	public function daily_visits( string $from, string $to ): array {
		global $wpdb;
		list( $from, $to ) = $this->dates( $from, $to );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT stat_date AS date, SUM(view_count) AS views FROM ' . Database::table() .
				' WHERE stat_date BETWEEN %s AND %s GROUP BY stat_date ORDER BY stat_date ASC',
				$from,
				$to
			),
			ARRAY_A
		);
		return array_map(
			static fn ( array $row ): array => array( 'date' => (string) $row['date'], 'views' => (int) $row['views'] ),
			is_array( $rows ) ? $rows : array()
		);
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
		return is_array( $rows ) ? $rows : array();
	}

	private function dates( string $from, string $to ): array {
		$today = current_time( 'Y-m-d' );
		$from  = $this->valid_date( $from ) ? $from : $today;
		$to    = $this->valid_date( $to ) ? $to : $today;
		return $from <= $to ? array( $from, $to ) : array( $to, $from );
	}

	private function valid_date( string $date ): bool {
		$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		return false !== $parsed && $parsed->format( 'Y-m-d' ) === $date;
	}
}
