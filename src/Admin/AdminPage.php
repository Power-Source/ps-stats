<?php

namespace PSStats\Admin;

use PSStats\Api\Provider;
use PSStats\Database;
use PSStats\Migration;
use PSStats\Normalizer;
use PSStats\Plugin;

defined( 'ABSPATH' ) || exit;

final class AdminPage {
	private const PAGE = 'ps-stats';

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_ps_stats_save', array( self::class, 'save_settings' ) );
		add_action( 'admin_post_ps_stats_delete_before', array( self::class, 'delete_before' ) );
		add_action( 'admin_post_ps_stats_delete_referrers', array( self::class, 'delete_referrers' ) );
		add_action( 'admin_post_ps_stats_delete_all', array( self::class, 'delete_all' ) );
		add_action( 'admin_post_ps_stats_resume_migration', array( self::class, 'resume_migration' ) );
	}

	public static function menu(): void {
		add_dashboard_page(
			__( 'PS Stats', 'ps-stats' ),
			__( 'PS Stats', 'ps-stats' ),
			'manage_options',
			self::PAGE,
			array( self::class, 'render' )
		);
		add_options_page(
			__( 'PS Stats', 'ps-stats' ),
			__( 'PS Stats', 'ps-stats' ),
			'manage_options',
			self::PAGE,
			array( self::class, 'render' )
		);
	}

	public static function render(): void {
		self::guard();
		$provider = Provider::instance();
		$settings = Plugin::settings();
		$today    = current_time( 'Y-m-d' );
		$from     = gmdate( 'Y-m-d', strtotime( $today . ' -29 days' ) );
		$daily    = $provider->daily_visits( $from, $today );
		$landing  = array_slice( $provider->landing_page_stats( $from, $today ), 0, 10 );
		$referrer = array_slice( $provider->referrer_stats( $from, $today ), 0, 10 );
		$bounds   = $provider->date_bounds();
		$overview = self::overview( $today );
		$migration = Migration::state();
		$next_cleanup = wp_next_scheduled( Plugin::CRON_CLEANUP );
		?>
		<div class="wrap ps-stats">
			<h1><?php esc_html_e( 'PS Stats', 'ps-stats' ); ?></h1>
			<?php self::notice(); ?>
			<p><?php esc_html_e( 'PS Stats zählt ausschließlich Seitenaufrufe auf dieser Website. Es werden keine Personen oder Sitzungen verfolgt.', 'ps-stats' ); ?></p>

			<h2><?php esc_html_e( 'Übersicht', 'ps-stats' ); ?></h2>
			<table class="widefat striped" style="max-width:900px">
				<tbody>
					<tr><th><?php esc_html_e( 'Aufrufe heute', 'ps-stats' ); ?></th><td><?php echo esc_html( number_format_i18n( $overview['today'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Aufrufe insgesamt', 'ps-stats' ); ?></th><td><?php echo esc_html( number_format_i18n( $overview['total'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Erfasste Zielpfade', 'ps-stats' ); ?></th><td><?php echo esc_html( number_format_i18n( $overview['paths'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Externe Referrer-Domains', 'ps-stats' ); ?></th><td><?php echo esc_html( number_format_i18n( $overview['domains'] ) ); ?></td></tr>
				</tbody>
			</table>

			<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:20px;max-width:1100px">
				<?php self::daily_table( $daily ); ?>
				<?php self::ranking_table( __( 'Top-Zielpfade (30 Tage)', 'ps-stats' ), __( 'Pfad', 'ps-stats' ), 'path', $landing ); ?>
				<?php self::ranking_table( __( 'Top-Referrer-Domains (30 Tage)', 'ps-stats' ), __( 'Domain', 'ps-stats' ), 'domain', $referrer ); ?>
			</div>

			<h2><?php esc_html_e( 'Migration', 'ps-stats' ); ?></h2>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: status, 2: processed rows, 3: source rows. */
						__( 'Status: %1$s — %2$d von %3$d Legacy-Zeilen verarbeitet.', 'ps-stats' ),
						self::status_label( $migration['status'] ),
						$migration['processed'],
						$migration['source_count']
					)
				);
				?>
			</p>
			<?php if ( $migration['error'] ) : ?>
				<div class="notice notice-error inline"><p><?php echo esc_html( $migration['error'] ); ?></p></div>
			<?php endif; ?>
			<?php if ( in_array( $migration['status'], array( 'pending', 'running', 'failed' ), true ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ps_stats_resume_migration">
					<?php wp_nonce_field( 'ps_stats_resume_migration' ); ?>
					<?php submit_button( __( 'Migration fortsetzen', 'ps-stats' ), 'secondary', 'submit', false ); ?>
				</form>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Einstellungen', 'ps-stats' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="ps_stats_save">
				<?php wp_nonce_field( 'ps_stats_save' ); ?>
				<table class="form-table"><tbody>
					<tr>
						<th><label for="ps-stats-retention"><?php esc_html_e( 'Aufbewahrung', 'ps-stats' ); ?></label></th>
						<td><select id="ps-stats-retention" name="retention">
							<?php foreach ( array( 7, 14, 30, 90, 180, 365, 0 ) as $days ) : ?>
								<option value="<?php echo esc_attr( $days ); ?>" <?php selected( $settings['retention'], $days ); ?>>
									<?php echo esc_html( 0 === $days ? __( 'Unbegrenzt', 'ps-stats' ) : sprintf( __( '%d Tage', 'ps-stats' ), $days ) ); ?>
								</option>
							<?php endforeach; ?>
						</select></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Angemeldete Personen', 'ps-stats' ); ?></th>
						<td><label><input type="checkbox" name="skip_logged_in" value="1" <?php checked( $settings['skip_logged_in'] ); ?>> <?php esc_html_e( 'Aufrufe angemeldeter Personen nicht zählen', 'ps-stats' ); ?></label></td>
					</tr>
					<tr>
						<th><label for="ps-stats-excluded"><?php esc_html_e( 'Ausgeschlossene Pfade', 'ps-stats' ); ?></label></th>
						<td><textarea id="ps-stats-excluded" name="excluded_paths" rows="6" class="large-text code"><?php echo esc_textarea( implode( "\n", $settings['excluded_paths'] ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Ein lokaler Pfad pro Zeile. Unterpfade werden ebenfalls ausgeschlossen; Abfragen und Fragmente werden ignoriert.', 'ps-stats' ); ?></p></td>
					</tr>
				</tbody></table>
				<?php submit_button( __( 'Einstellungen speichern', 'ps-stats' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Datenpflege', 'ps-stats' ); ?></h2>
			<p>
				<?php
				echo esc_html( sprintf( __( 'Älteste Daten: %s', 'ps-stats' ), $bounds['oldest'] ?: __( 'keine', 'ps-stats' ) ) );
				echo ' · ';
				echo esc_html( sprintf( __( 'Nächste automatische Bereinigung: %s', 'ps-stats' ), $next_cleanup ? wp_date( 'Y-m-d H:i', $next_cleanup ) : __( 'nicht geplant', 'ps-stats' ) ) );
				?>
			</p>
			<?php self::destructive_forms(); ?>

			<h2><?php esc_html_e( 'Datenschutz und Datenumfang', 'ps-stats' ); ?></h2>
			<p><?php esc_html_e( 'Gespeichert werden ausschließlich das lokale Datum, der normalisierte lokale Zielpfad ohne Abfrage oder Fragment, die externe Referrer-Domain ohne Pfad oder Abfrage und die aggregierte Anzahl der Aufrufe. PS Stats speichert keine IP-Adressen oder Hashes, Cookies, lokalen Kennungen, Benutzerkennungen, User-Agents, Geräte-, Fingerprint- oder Sitzungsdaten und keine vollständigen Referrer. Es gibt keine externen Anfragen, Drittanbieter-Analysen oder KI-Funktionen. Deshalb existiert kein personenbezogener Datenexport.', 'ps-stats' ); ?></p>
		</div>
		<?php
	}

	public static function save_settings(): void {
		self::verify( 'ps_stats_save' );
		$allowed   = array( 0, 7, 14, 30, 90, 180, 365 );
		$retention = isset( $_POST['retention'] ) ? (int) $_POST['retention'] : 30;
		$retention = in_array( $retention, $allowed, true ) ? $retention : 30;
		$raw       = isset( $_POST['excluded_paths'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excluded_paths'] ) ) : '';
		$paths     = array();
		foreach ( preg_split( '/\R/', $raw ) ?: array() as $path ) {
			if ( '' !== trim( $path ) ) {
				$paths[] = Normalizer::target_path( $path );
			}
		}
		update_option(
			Plugin::OPTION_SETTINGS,
			array(
				'retention'      => $retention,
				'skip_logged_in' => isset( $_POST['skip_logged_in'] ),
				'excluded_paths' => array_values( array_unique( $paths ) ),
			),
			false
		);
		self::redirect( 'saved' );
	}

	public static function delete_before(): void {
		self::verify_destructive( 'ps_stats_delete_before' );
		$date = isset( $_POST['before_date'] ) ? sanitize_text_field( wp_unslash( $_POST['before_date'] ) ) : '';
		$valid = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		if ( false === $valid || $valid->format( 'Y-m-d' ) !== $date ) {
			self::redirect( 'invalid_date', true );
		}
		self::redirect( Database::delete_before( $date ) ? 'deleted_before' : 'db_error', ! Database::delete_before( $date ) );
	}

	public static function delete_referrers(): void {
		self::verify_destructive( 'ps_stats_delete_referrers' );
		$success = Database::delete_referrers();
		self::redirect( $success ? 'referrers_deleted' : 'db_error', ! $success );
	}

	public static function delete_all(): void {
		self::verify_destructive( 'ps_stats_delete_all' );
		$success = Database::delete_all();
		self::redirect( $success ? 'all_deleted' : 'db_error', ! $success );
	}

	public static function resume_migration(): void {
		self::verify( 'ps_stats_resume_migration' );
		Migration::schedule();
		self::redirect( 'migration_scheduled' );
	}

	private static function overview( string $today ): array {
		global $wpdb;
		$table = Database::table();
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(view_count),0) AS total,
				COALESCE(SUM(CASE WHEN stat_date=%s THEN view_count ELSE 0 END),0) AS today,
				COUNT(DISTINCT target_path) AS paths,
				COUNT(DISTINCT NULLIF(referrer_domain,'')) AS domains FROM {$table}",
				$today
			),
			ARRAY_A
		);
		return array_map( 'intval', is_array( $row ) ? $row : array( 'total' => 0, 'today' => 0, 'paths' => 0, 'domains' => 0 ) );
	}

	private static function daily_table( array $rows ): void {
		?>
		<div><h3><?php esc_html_e( 'Tägliche Aufrufe (30 Tage)', 'ps-stats' ); ?></h3><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Datum', 'ps-stats' ); ?></th><th><?php esc_html_e( 'Aufrufe', 'ps-stats' ); ?></th></tr></thead><tbody>
		<?php foreach ( array_reverse( $rows ) as $row ) : ?><tr><td><?php echo esc_html( $row['date'] ); ?></td><td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td></tr><?php endforeach; ?>
		<?php if ( array() === $rows ) : ?><tr><td colspan="2"><?php esc_html_e( 'Noch keine Daten.', 'ps-stats' ); ?></td></tr><?php endif; ?>
		</tbody></table></div>
		<?php
	}

	private static function ranking_table( string $title, string $label, string $key, array $rows ): void {
		?>
		<div><h3><?php echo esc_html( $title ); ?></h3><table class="widefat striped"><thead><tr><th><?php echo esc_html( $label ); ?></th><th><?php esc_html_e( 'Aufrufe', 'ps-stats' ); ?></th></tr></thead><tbody>
		<?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row[ $key ] ); ?></td><td><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td></tr><?php endforeach; ?>
		<?php if ( array() === $rows ) : ?><tr><td colspan="2"><?php esc_html_e( 'Noch keine Daten.', 'ps-stats' ); ?></td></tr><?php endif; ?>
		</tbody></table></div>
		<?php
	}

	private static function destructive_forms(): void {
		$action = esc_url( admin_url( 'admin-post.php' ) );
		?>
		<div style="display:grid;gap:16px;max-width:900px">
			<form method="post" action="<?php echo $action; ?>"><input type="hidden" name="action" value="ps_stats_delete_before"><?php wp_nonce_field( 'ps_stats_delete_before' ); ?>
				<label><?php esc_html_e( 'Daten löschen vor', 'ps-stats' ); ?> <input type="date" name="before_date" required></label>
				<label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'Ich bestätige die unwiderrufliche Löschung.', 'ps-stats' ); ?></label>
				<?php submit_button( __( 'Ältere Daten löschen', 'ps-stats' ), 'secondary', 'submit', false ); ?>
			</form>
			<form method="post" action="<?php echo $action; ?>"><input type="hidden" name="action" value="ps_stats_delete_referrers"><?php wp_nonce_field( 'ps_stats_delete_referrers' ); ?>
				<label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'Ich bestätige das unwiderrufliche Entfernen aller Referrer-Domains.', 'ps-stats' ); ?></label>
				<?php submit_button( __( 'Referrer-Daten löschen', 'ps-stats' ), 'secondary', 'submit', false ); ?>
			</form>
			<form method="post" action="<?php echo $action; ?>"><input type="hidden" name="action" value="ps_stats_delete_all"><?php wp_nonce_field( 'ps_stats_delete_all' ); ?>
				<label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'Ich bestätige die unwiderrufliche Löschung aller Statistiken.', 'ps-stats' ); ?></label>
				<?php submit_button( __( 'Alle Statistiken löschen', 'ps-stats' ), 'delete', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	private static function verify( string $nonce ): void {
		self::guard();
		check_admin_referer( $nonce );
	}

	private static function verify_destructive( string $nonce ): void {
		self::verify( $nonce );
		if ( '1' !== ( $_POST['confirm'] ?? '' ) ) {
			self::redirect( 'confirmation_required', true );
		}
	}

	private static function guard(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
	}

	private static function redirect( string $message, bool $error = false ): never {
		wp_safe_redirect( add_query_arg( array( 'page' => self::PAGE, 'ps_stats_notice' => $message, 'ps_stats_error' => $error ? 1 : 0 ), admin_url( 'index.php' ) ) );
		exit;
	}

	private static function notice(): void {
		if ( empty( $_GET['ps_stats_notice'] ) ) {
			return;
		}
		$key = sanitize_key( wp_unslash( $_GET['ps_stats_notice'] ) );
		$messages = array(
			'saved' => __( 'Einstellungen gespeichert.', 'ps-stats' ),
			'deleted_before' => __( 'Ältere Statistikdaten wurden gelöscht.', 'ps-stats' ),
			'referrers_deleted' => __( 'Alle Referrer-Daten wurden gelöscht.', 'ps-stats' ),
			'all_deleted' => __( 'Alle Statistikdaten wurden gelöscht.', 'ps-stats' ),
			'migration_scheduled' => __( 'Die Fortsetzung der Migration wurde eingeplant.', 'ps-stats' ),
			'invalid_date' => __( 'Das angegebene Datum ist ungültig.', 'ps-stats' ),
			'db_error' => __( 'Die Datenbankaktion ist fehlgeschlagen.', 'ps-stats' ),
			'confirmation_required' => __( 'Die ausdrückliche Bestätigung fehlt.', 'ps-stats' ),
		);
		if ( isset( $messages[ $key ] ) ) {
			$class = ! empty( $_GET['ps_stats_error'] ) ? 'notice notice-error' : 'notice notice-success';
			printf( '<div class="%s"><p>%s</p></div>', esc_attr( $class ), esc_html( $messages[ $key ] ) );
		}
	}

	private static function status_label( string $status ): string {
		$labels = array(
			'not_started' => __( 'nicht gestartet', 'ps-stats' ),
			'pending' => __( 'ausstehend', 'ps-stats' ),
			'running' => __( 'läuft', 'ps-stats' ),
			'failed' => __( 'fehlgeschlagen', 'ps-stats' ),
			'complete' => __( 'abgeschlossen', 'ps-stats' ),
		);
		return $labels[ $status ] ?? $status;
	}
}
