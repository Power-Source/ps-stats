<?php

namespace PSStats;

defined( 'ABSPATH' ) || exit;

final class Migration {
	public const OPTION = 'ps_stats_migration';
	private const BATCH_SIZE = 250;

	public static function prepare(): void {
		global $wpdb;
		$legacy = Database::legacy_table();
		if ( ! Database::table_exists( $legacy ) ) {
			self::save(
				array(
					'status'       => 'complete',
					'cursor'       => 0,
					'processed'    => 0,
					'source_count' => 0,
					'max_id'       => 0,
					'error'        => '',
				)
			);
			return;
		}

		$state = self::state();
		if ( in_array( $state['status'], array( 'running', 'pending', 'failed' ), true ) && $state['source_count'] > 0 ) {
			self::schedule();
			return;
		}

		$max_id = $wpdb->get_var( "SELECT MAX(id) FROM {$legacy}" );
		if ( '' !== $wpdb->last_error ) {
			self::fail( $wpdb->last_error );
			return;
		}
		$max_id = (int) $max_id;
		$count  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$legacy} WHERE id <= %d", $max_id ) );
		if ( '' !== $wpdb->last_error ) {
			self::fail( $wpdb->last_error );
			return;
		}

		self::save(
			array(
				'status'       => 'pending',
				'cursor'       => 0,
				'processed'    => 0,
				'source_count' => $count,
				'max_id'       => $max_id,
				'error'        => '',
			)
		);
		self::schedule();
	}

	public static function run_batch(): void {
		global $wpdb;
		$state  = self::state();
		$legacy = Database::legacy_table();

		if ( ! in_array( $state['status'], array( 'pending', 'running', 'failed' ), true ) ) {
			return;
		}
		if ( ! Database::table_exists( $legacy ) ) {
			self::fail( __( 'Die Legacy-Tabelle fehlt vor Abschluss der Migration.', 'ps-stats' ) );
			return;
		}

		self::save( array_merge( $state, array( 'status' => 'running', 'error' => '' ) ) );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id,created,referrer,target FROM {$legacy}
				WHERE id > %d AND id <= %d ORDER BY id ASC LIMIT %d",
				$state['cursor'],
				$state['max_id'],
				self::BATCH_SIZE
			),
			ARRAY_A
		);
		if ( null === $rows || '' !== $wpdb->last_error ) {
			self::fail( $wpdb->last_error ?: __( 'Legacy-Daten konnten nicht gelesen werden.', 'ps-stats' ) );
			return;
		}

		if ( array() === $rows ) {
			self::verify_and_finish();
			return;
		}

		$aggregates = array();
		foreach ( $rows as $row ) {
			$date = self::valid_date( (string) $row['created'] ) ? (string) $row['created'] : current_time( 'Y-m-d' );
			$path = Normalizer::target_path( $row['target'] );
			$host = Normalizer::referrer_domain( $row['referrer'] );
			$key  = $date . "\0" . $path . "\0" . $host;
			if ( ! isset( $aggregates[ $key ] ) ) {
				$aggregates[ $key ] = array( $date, $path, $host, 0 );
			}
			++$aggregates[ $key ][3];
		}

		$wpdb->query( 'START TRANSACTION' );
		foreach ( $aggregates as $aggregate ) {
			if ( ! Database::increment( $aggregate[0], $aggregate[1], $aggregate[2], $aggregate[3] ) ) {
				$wpdb->query( 'ROLLBACK' );
				self::fail( $wpdb->last_error ?: __( 'Aggregierte Daten konnten nicht geschrieben werden.', 'ps-stats' ) );
				return;
			}
		}
		if ( false === $wpdb->query( 'COMMIT' ) ) {
			self::fail( $wpdb->last_error ?: __( 'Die Migrationstransaktion konnte nicht abgeschlossen werden.', 'ps-stats' ) );
			return;
		}

		$state['cursor']    = (int) end( $rows )['id'];
		$state['processed'] += count( $rows );
		$state['status']     = 'running';
		self::save( $state );
		self::schedule();
	}

	private static function verify_and_finish(): void {
		global $wpdb;
		$state  = self::state();
		$legacy = Database::legacy_table();
		$count  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$legacy} WHERE id <= %d", $state['max_id'] ) );

		if ( '' !== $wpdb->last_error || $count !== $state['source_count'] || $state['processed'] !== $state['source_count'] ) {
			self::fail(
				sprintf(
					/* translators: 1: processed rows, 2: expected rows, 3: current source rows. */
					__( 'Prüfung fehlgeschlagen: %1$d verarbeitet, %2$d erwartet, %3$d vorhanden. Die Legacy-Tabelle bleibt erhalten.', 'ps-stats' ),
					$state['processed'],
					$state['source_count'],
					$count
				)
			);
			return;
		}

		if ( false === $wpdb->query( "DROP TABLE {$legacy}" ) ) {
			self::fail( __( 'Die geprüfte Legacy-Tabelle konnte nicht entfernt werden: ', 'ps-stats' ) . $wpdb->last_error );
			return;
		}

		$state['status'] = 'complete';
		$state['error']  = '';
		self::save( $state );
		do_action( 'ps_stats_migration_completed', $state );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( Plugin::CRON_MIGRATION ) ) {
			wp_schedule_single_event( time() + 5, Plugin::CRON_MIGRATION );
		}
	}

	public static function state(): array {
		$defaults = array(
			'status'       => 'not_started',
			'cursor'       => 0,
			'processed'    => 0,
			'source_count' => 0,
			'max_id'       => 0,
			'error'        => '',
		);
		$state = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $state ) ? $state : array(), $defaults );
	}

	private static function save( array $state ): void {
		update_option( self::OPTION, $state, false );
	}

	private static function fail( string $message ): void {
		$state           = self::state();
		$state['status'] = 'failed';
		$state['error']  = sanitize_text_field( $message );
		self::save( $state );
		do_action( 'ps_stats_migration_failed', $state );
	}

	private static function valid_date( string $date ): bool {
		$parsed = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		return false !== $parsed && $parsed->format( 'Y-m-d' ) === $date;
	}
}
