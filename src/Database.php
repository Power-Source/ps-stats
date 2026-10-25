<?php

namespace PSStats;

use RuntimeException;

defined( 'ABSPATH' ) || exit;

final class Database {
	public static function lock(): bool {
		global $wpdb;
		$locked = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', self::lock_name() ) );
		if ( '1' !== (string) $locked ) {
			do_action( 'ps_stats_database_error', 'lock', __( 'Statistikpflege läuft bereits oder die Datenbanksperre ist nicht verfügbar.', 'ps-stats' ) );
			return false;
		}
		return true;
	}

	public static function unlock(): void {
		global $wpdb;
		$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', self::lock_name() ) );
	}

	private static function lock_name(): string {
		return 'ps_stats_' . md5( DB_NAME . ':' . self::table() );
	}

	public static function transaction( callable $operation, bool $already_locked = false ): bool {
		global $wpdb;
		if ( ! $already_locked && ! self::lock() ) {
			return false;
		}
		try {
			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				do_action( 'ps_stats_database_error', 'transaction', $wpdb->last_error );
				return false;
			}
			if ( ! $operation() || false === $wpdb->query( 'COMMIT' ) ) {
				$error = $wpdb->last_error;
				$wpdb->query( 'ROLLBACK' );
				do_action( 'ps_stats_database_error', 'transaction', $error ?: __( 'Statistikpflege fehlgeschlagen.', 'ps-stats' ) );
				return false;
			}
			return true;
		} catch ( \Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			throw $error;
		} finally {
			if ( ! $already_locked ) {
				self::unlock();
			}
		}
	}

	public static function require_transactional( array $tables ): void {
		global $wpdb;
		foreach ( $tables as $table ) {
			$status = $wpdb->get_row( $wpdb->prepare( 'SHOW TABLE STATUS LIKE %s', $wpdb->esc_like( $table ) ), ARRAY_A );
			if ( ! is_array( $status ) || 'INNODB' !== strtoupper( (string) $status['Engine'] ) ) {
				throw new RuntimeException( 'PS Stats: Sichere Pflege benötigt InnoDB für ' . $table . '. Bitte vor der Migration durch die Administration prüfen lassen.' );
			}
		}
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->get_blog_prefix() . 'ps_stats_daily';
	}

	public static function legacy_table(): string {
		global $wpdb;
		return $wpdb->get_blog_prefix() . 'psstats';
	}

	public static function metrics_table(): string {
		global $wpdb;
		return $wpdb->get_blog_prefix() . 'ps_stats_daily_metrics';
	}

	public static function create_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table         = self::table();
		$metrics_table = self::metrics_table();
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
		) ENGINE=InnoDB {$charset};
		CREATE TABLE {$metrics_table} (
			stat_date date NOT NULL,
			dimension_type varchar(32) NOT NULL,
			dimension_id bigint(20) unsigned NOT NULL,
			metric varchar(32) NOT NULL,
			metric_value decimal(20,4) NOT NULL DEFAULT 0,
			UNIQUE KEY metric_aggregate (stat_date,dimension_type,dimension_id,metric),
			KEY dimension_date (dimension_type,dimension_id,stat_date),
			KEY metric_date (metric,stat_date)
		) ENGINE=InnoDB {$charset};";

		dbDelta( $sql );
		if ( '' !== $wpdb->last_error || ! self::table_exists( $table ) || ! self::table_exists( $metrics_table ) ) {
			throw new RuntimeException( 'PS Stats: Die Aggregattabelle konnte nicht erstellt werden: ' . $wpdb->last_error );
		}
		self::require_transactional( array( $table, $metrics_table, $wpdb->options ) );
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

	public static function increment_metric( string $date, string $dimension_type, int $dimension_id, string $metric, int|float $value = 1 ): bool {
		global $wpdb;
		$table = self::metrics_table();
		$sql   = $wpdb->prepare(
			"INSERT INTO {$table} (stat_date,dimension_type,dimension_id,metric,metric_value)
			VALUES (%s,%s,%d,%s,%f)
			ON DUPLICATE KEY UPDATE metric_value = metric_value + VALUES(metric_value)",
			$date,
			$dimension_type,
			$dimension_id,
			$metric,
			$value
		);

		return false !== $wpdb->query( $sql );
	}

	public static function cleanup(): void {
		$retention = Plugin::settings()['retention'];
		if ( 0 === $retention ) {
			return;
		}
		if ( in_array( Migration::state()['status'], array( 'pending', 'running', 'failed' ), true ) ) {
			update_option( 'ps_stats_cleanup_error', __( 'Bereinigung bis zum Abschluss der Migration pausiert.', 'ps-stats' ), false );
			return;
		}
		$cutoff = gmdate( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . " -{$retention} days" ) );
		if ( self::delete_before( $cutoff ) ) {
			update_option( 'ps_stats_cleanup_last_success', time(), false );
			delete_option( 'ps_stats_cleanup_error' );
		} else {
			update_option( 'ps_stats_cleanup_error', __( 'Automatische Bereinigung fehlgeschlagen. Bitte Datenbank und Migration prüfen.', 'ps-stats' ), false );
		}
	}

	public static function delete_before( string $date ): bool {
		global $wpdb;
		return self::delete_rows( $wpdb->prepare( ' WHERE stat_date < %s', $date ) );
	}

	public static function delete_referrers(): bool {
		global $wpdb;
		$table = self::table();
		return self::transaction( static function () use ( $wpdb, $table ): bool {
			if ( in_array( Migration::state()['status'], array( 'pending', 'running', 'failed' ), true ) ) {
				return false;
			}
			$staging = 'ps_stats_referrer_merge';
			if ( false === $wpdb->query( "CREATE TEMPORARY TABLE {$staging} ENGINE=InnoDB AS SELECT stat_date,target_path,SUM(view_count) AS view_count FROM {$table} GROUP BY stat_date,target_path LOCK IN SHARE MODE" ) ) {
				return false;
			}
			try {
				$merged = $wpdb->query(
					"INSERT INTO {$table} (stat_date,target_path,referrer_domain,view_count)
					SELECT stat_date,target_path,'',view_count FROM {$staging}
					ON DUPLICATE KEY UPDATE view_count = VALUES(view_count)"
				);
				$deleted = false !== $merged ? $wpdb->query( "DELETE FROM {$table} WHERE referrer_domain <> ''" ) : false;
				return false !== $merged && false !== $deleted;
			} finally {
				$wpdb->query( "DROP TEMPORARY TABLE {$staging}" );
			}
		} );
	}

	public static function delete_all(): bool {
		return self::delete_rows( '' );
	}

	private static function delete_rows( string $where ): bool {
		global $wpdb;
		return self::transaction( static function () use ( $wpdb, $where ): bool {
			if ( in_array( Migration::state()['status'], array( 'pending', 'running', 'failed' ), true ) ) {
				return false;
			}
			return false !== $wpdb->query( 'DELETE FROM ' . self::table() . $where )
				&& false !== $wpdb->query( 'DELETE FROM ' . self::metrics_table() . $where );
		} );
	}

	public static function date_bounds(): array {
		global $wpdb;
		$row = $wpdb->get_row( 'SELECT MIN(stat_date) AS oldest, MAX(stat_date) AS newest FROM ' . self::table(), ARRAY_A );
		if ( '' !== $wpdb->last_error ) {
			do_action( 'ps_stats_database_error', 'date_bounds', $wpdb->last_error );
			throw new RuntimeException( 'PS Stats: Statistikzeitraum konnte nicht gelesen werden.' );
		}
		return array(
			'oldest' => ! empty( $row['oldest'] ) ? (string) $row['oldest'] : null,
			'newest' => ! empty( $row['newest'] ) ? (string) $row['newest'] : null,
		);
	}
}
