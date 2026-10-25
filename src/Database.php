<?php

namespace PSStats;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class Database {
	public static function table(): string {
		global $wpdb;
		return $wpdb->get_blog_prefix() . 'ps_stats_daily';
	}

	public static function legacy_table(): string {
		global $wpdb;
		return $wpdb->get_blog_prefix() . 'psstats';
	}

	public static function create_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			stat_date date NOT NULL,
			target_path varchar(300) NOT NULL,
			referrer_domain varchar(190) NOT NULL DEFAULT '',
			view_count bigint(20) unsigned NOT NULL DEFAULT 0,
			UNIQUE KEY aggregate_key (stat_date,target_path,referrer_domain),
			KEY stat_date (stat_date),
			KEY target_date (target_path(100),stat_date),
			KEY referrer_date (referrer_domain,stat_date)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql );
		if ( '' !== $wpdb->last_error || ! self::table_exists( $table ) ) {
			throw new RuntimeException( 'PS Stats: Die Aggregattabelle konnte nicht erstellt werden: ' . $wpdb->last_error );
		}
	}

	public static function table_exists( string $table ): bool {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
		return $found === $table;
	}

	public static function increment( string $date, string $path, string $domain, int $count = 1 ): bool {
		global $wpdb;
		$table = self::table();
		$sql   = $wpdb->prepare(
			"INSERT INTO {$table} (stat_date,target_path,referrer_domain,view_count)
			VALUES (%s,%s,%s,%d)
			ON DUPLICATE KEY UPDATE view_count = view_count + VALUES(view_count)",
			$date,
			$path,
			$domain,
			max( 1, $count )
		);

		return false !== $wpdb->query( $sql );
	}

	public static function cleanup(): void {
		global $wpdb;
		$retention = Plugin::settings()['retention'];
		if ( 0 === $retention ) {
			return;
		}

		$cutoff = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . " -{$retention} days" ) );
		$result = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE stat_date < %s', $cutoff ) );
		if ( false === $result ) {
			do_action( 'ps_stats_database_error', 'cleanup', $wpdb->last_error );
		}
	}

	public static function delete_before( string $date ): bool {
		global $wpdb;
		return false !== $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE stat_date < %s', $date ) );
	}

	public static function delete_referrers(): bool {
		global $wpdb;
		$table = self::table();
		$wpdb->query( 'START TRANSACTION' );
		$merged = $wpdb->query(
			"INSERT INTO {$table} (stat_date,target_path,referrer_domain,view_count)
			SELECT stat_date,target_path,'',SUM(view_count) FROM {$table} GROUP BY stat_date,target_path
			ON DUPLICATE KEY UPDATE view_count = VALUES(view_count)"
		);
		$deleted = false !== $merged ? $wpdb->query( "DELETE FROM {$table} WHERE referrer_domain <> ''" ) : false;
		if ( false === $merged || false === $deleted ) {
			$wpdb->query( 'ROLLBACK' );
			return false;
		}
		$wpdb->query( 'COMMIT' );
		return true;
	}

	public static function delete_all(): bool {
		global $wpdb;
		return false !== $wpdb->query( 'DELETE FROM ' . self::table() );
	}

	public static function date_bounds(): array {
		global $wpdb;
		$row = $wpdb->get_row( 'SELECT MIN(stat_date) AS oldest, MAX(stat_date) AS newest FROM ' . self::table(), ARRAY_A );
		return array(
			'oldest' => ! empty( $row['oldest'] ) ? (string) $row['oldest'] : null,
			'newest' => ! empty( $row['newest'] ) ? (string) $row['newest'] : null,
		);
	}
}
