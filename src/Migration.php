<?php

namespace PSStats;

defined( 'ABSPATH' ) || exit;

final class Migration {
	public const OPTION = 'ps_stats_migration';
	private const BATCH_SIZE = 250;

	public static function prepare(): void {
		if ( ! Database::lock() ) {
			self::schedule();
			return;
		}
		try {
			$state = self::state();
			if ( 'not_started' !== $state['status'] ) {
				if ( 'complete' !== $state['status'] ) {
					self::schedule();
				}
				return;
			}
			global $wpdb;
			$legacy = Database::legacy_table();
			if ( ! Database::table_exists( $legacy ) ) {
				if ( '' !== $wpdb->last_error ) {
					throw new \RuntimeException( $wpdb->last_error );
				}
				$state['status'] = 'complete';
			} else {
				$row = $wpdb->get_row( "SELECT COUNT(*) AS total, COALESCE(MAX(id),0) AS max_id FROM {$legacy}", ARRAY_A );
				if ( ! is_array( $row ) || '' !== $wpdb->last_error ) {
					throw new \RuntimeException( 'PS Stats: Legacy-Bestand konnte nicht geprüft werden.' );
				}
				$state['source_count'] = (int) $row['total'];
				$state['max_id'] = (int) $row['max_id'];
				$state['status'] = 'pending';
			}
			self::save( $state );
			if ( 'pending' === $state['status'] ) {
				self::schedule();
			}
		} catch ( \RuntimeException $error ) {
			self::fail( $error->getMessage() );
		} finally {
			Database::unlock();
		}
	}

	public static function run_batch(): void {
		if ( ! Database::lock() ) {
			self::schedule();
			return;
		}
		try {
			self::run_locked_batch();
		} finally {
			Database::unlock();
		}
	}

	private static function run_locked_batch(): void {
		global $wpdb;
		try {
			Database::require_transactional( array( Database::table(), $wpdb->options ) );
		} catch ( \RuntimeException $error ) {
			self::fail( $error->getMessage() );
			return;
		}
		$completed = false;
		$error = '';
		$success = Database::transaction( static function () use ( $wpdb, &$completed, &$error ): bool {
			// Read directly: a persistent object cache must not serve an older cursor.
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s FOR UPDATE", self::OPTION ) );
			if ( '' !== $wpdb->last_error ) {
				$error = $wpdb->last_error;
				return false;
			}
			$stored = maybe_unserialize( $value ?? '' );
			$state = array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
			if ( ! in_array( $state['status'], array( 'pending', 'running', 'failed' ), true ) ) {
				return true;
			}
			$legacy = Database::legacy_table();
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id,created,referrer,target FROM {$legacy} WHERE id > %d AND id <= %d ORDER BY id ASC LIMIT %d", $state['cursor'], $state['max_id'], self::BATCH_SIZE ),
				ARRAY_A
			);
			if ( ! is_array( $rows ) || '' !== $wpdb->last_error ) {
				$error = __( 'Legacy-Daten konnten nicht gelesen werden.', 'ps-stats' );
				return false;
			}
			foreach ( $rows as $row ) {
				$date = \DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $row['created'] );
				if ( false === $date || $date->format( 'Y-m-d' ) !== $row['created'] ) {
					$error = sprintf( __( 'Ungültiges Datum in Legacy-Zeile %d. Der Stapel wurde nicht übernommen.', 'ps-stats' ), $row['id'] );
					return false;
				}
				$path = Privacy::route( $row['target'] );
				if ( null === $path ) {
					++$state['skipped'];
				} elseif ( ! Database::increment( $row['created'], $path, Privacy::referrer( $row['referrer'] ) ) ) {
					$error = __( 'Aggregate konnten nicht geschrieben werden.', 'ps-stats' );
					return false;
				}
				$state['cursor'] = (int) $row['id'];
				++$state['processed'];
			}
			$state['status'] = 'running';
			$state['error'] = '';
			if ( array() === $rows ) {
				$total = $wpdb->get_var( "SELECT COUNT(*) FROM {$legacy}" );
				if ( '' !== $wpdb->last_error || (int) $total !== (int) $state['source_count'] || (int) $state['processed'] !== (int) $state['source_count'] ) {
					$error = __( 'Der Legacy-Bestand hat sich geändert oder wurde nicht vollständig verarbeitet. Die Quelltabelle bleibt erhalten.', 'ps-stats' );
					return false;
				}
				$state['status'] = 'complete';
				$completed = true;
			}
			// Cursor and aggregates commit together, including on a process crash.
			if ( false === $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s", maybe_serialize( $state ), self::OPTION ) ) ) {
				$error = __( 'Migrationsfortschritt konnte nicht gespeichert werden.', 'ps-stats' );
				return false;
			}
			return true;
		}, true );
		self::clear_cache();
		if ( ! $success ) {
			if ( '' !== $error ) {
				self::fail( $error );
			} else {
				self::schedule();
			}
			return;
		}
		if ( $completed ) {
			// Keep the source until an administrator explicitly approves its removal.
			do_action( 'ps_stats_migration_completed', self::state() );
		} elseif ( 'running' === self::state()['status'] ) {
			self::schedule();
		}
	}

	public static function delete_legacy(): bool {
		global $wpdb;
		if ( ! Database::lock() ) {
			return false;
		}
		try {
			$state = self::state();
			if ( 'complete' !== $state['status'] ) {
				return false;
			}
			$table = Database::legacy_table();
			if ( ! Database::table_exists( $table ) ) {
				return '' === $wpdb->last_error;
			}
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			if ( '' !== $wpdb->last_error || (int) $count !== (int) $state['source_count'] ) {
				do_action( 'ps_stats_database_error', 'delete_legacy', 'Legacy-Bestand hat sich geändert.' );
				return false;
			}
			return false !== $wpdb->query( "DROP TABLE {$table}" );
		} finally {
			Database::unlock();
		}
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( Plugin::CRON_MIGRATION ) ) {
			$result = wp_schedule_single_event( time() + 5, Plugin::CRON_MIGRATION, array(), true );
			if ( is_wp_error( $result ) ) {
				self::fail( __( 'Migration konnte nicht eingeplant werden: ', 'ps-stats' ) . $result->get_error_message() );
			}
		}
	}

	private static function defaults(): array {
		return array( 'status' => 'not_started', 'cursor' => 0, 'processed' => 0, 'source_count' => 0, 'max_id' => 0, 'skipped' => 0, 'error' => '' );
	}

	public static function state(): array {
		$state = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $state ) ? $state : array() );
	}

	private static function clear_cache(): void {
		wp_cache_delete( self::OPTION, 'options' );
		wp_cache_delete( 'alloptions', 'options' );
	}

	private static function save( array $state ): void {
		update_option( self::OPTION, $state, false );
		if ( get_option( self::OPTION ) !== $state ) {
			throw new \RuntimeException( 'PS Stats: Migrationsstatus konnte nicht gespeichert werden.' );
		}
	}

	private static function fail( string $message ): void {
		$state = self::state();
		$state['status'] = 'failed';
		$state['error'] = sanitize_text_field( $message );
		self::save( $state );
		do_action( 'ps_stats_migration_failed', $state );
	}
}
