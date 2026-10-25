<?php

namespace PSStats\Admin;

use PSStats\Api\Provider;
use PSStats\Database;
use PSStats\Migration;
use PSStats\Normalizer;
use PSStats\Plugin;
use PSStats\Privacy;

defined( 'ABSPATH' ) || exit;

final class AdminPage {
	private const PAGE = 'ps-stats';
	private const SETTINGS_PAGE = 'ps-stats-settings';

	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_menu', array( self::class, 'redirect_legacy_page' ), 1 );
		add_action( 'admin_post_ps_stats_save', array( self::class, 'save_settings' ) );
		add_action( 'admin_post_ps_stats_delete_before', array( self::class, 'delete_before' ) );
		add_action( 'admin_post_ps_stats_delete_referrers', array( self::class, 'delete_referrers' ) );
		add_action( 'admin_post_ps_stats_delete_all', array( self::class, 'delete_all' ) );
		add_action( 'admin_post_ps_stats_resume_migration', array( self::class, 'resume_migration' ) );
		add_action( 'admin_post_ps_stats_delete_legacy', array( self::class, 'delete_legacy' ) );
	}

	public static function redirect_legacy_page(): void {
		$page = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$aliases = array(
			'extended_evaluation_for_psstats_dashboard' => self::PAGE,
			'extended_evaluation_for_psstats_content'   => 'ps-stats-content',
			'extended_evaluation_for_psstats_referrer'  => 'ps-stats-referrers',
			'psstats-settings'                         => self::SETTINGS_PAGE,
		);
		if ( ! isset( $aliases[ $page ] ) ) {
			return;
		}
		$args = array( 'page' => $aliases[ $page ] );
		foreach ( array( 'year', 'post', 'posttype' ) as $key ) {
			if ( isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) ) {
				$args[ $key ] = rawurlencode( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) );
			}
		}
		$base = self::SETTINGS_PAGE === $args['page'] ? 'options-general.php' : 'admin.php';
		wp_safe_redirect( add_query_arg( $args, admin_url( $base ) ) );
		exit;
	}

	public static function menu(): void {
		$capability = current_user_can( 'manage_options' ) ? 'manage_options' : 'see_psstats_evaluation';
		$pages = array();
		$pages[] = add_menu_page(
			__( 'PS Stats', 'ps-stats' ),
			__( 'PS Stats', 'ps-stats' ),
			$capability,
			self::PAGE,
			array( self::class, 'render' ),
			'dashicons-chart-bar',
			50
		);
		add_submenu_page( self::PAGE, __( 'Übersicht', 'ps-stats' ), __( 'Übersicht', 'ps-stats' ), $capability, self::PAGE, array( self::class, 'render' ) );
		$pages[] = add_submenu_page( self::PAGE, __( 'Inhalte', 'ps-stats' ), __( 'Inhalte', 'ps-stats' ), $capability, 'ps-stats-content', array( Evaluation::class, 'content' ) );
		$pages[] = add_submenu_page( self::PAGE, __( 'Referrer', 'ps-stats' ), __( 'Referrer', 'ps-stats' ), $capability, 'ps-stats-referrers', array( Evaluation::class, 'referrers' ) );
		add_submenu_page( self::PAGE, __( 'MarketPress-Quelle', 'ps-stats' ), __( 'MarketPress', 'ps-stats' ), 'manage_options', 'ps-stats-shop', array( ShopPage::class, 'render' ) );
		foreach ( $pages as $page ) {
			add_action( 'admin_print_styles-' . $page, array( Evaluation::class, 'assets' ) );
		}
		load_plugin_textdomain( 'psstats', false, dirname( plugin_basename( PS_STATS_FILE ) ) . '/languages' );
		add_options_page(
			__( 'PS Stats', 'ps-stats' ),
			__( 'PS Stats', 'ps-stats' ),
			'manage_options',
			self::SETTINGS_PAGE,
			array( self::class, 'render_settings' )
		);
	}

	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'see_psstats_evaluation' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
		$today    = current_time( 'Y-m-d' );
		$overview = self::overview( $today );
		?>
		<div class="wrap ps-stats">
			<h1><?php esc_html_e( 'PS Stats', 'ps-stats' ); ?></h1>
			<?php self::notice(); ?>
			<?php if ( get_option( 'ps_stats_install_error' ) ) : ?>
				<div class="notice notice-error inline">
					<p>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: installation error. */
								__( 'PS Stats konnte die Statistikdatenbank nicht vorbereiten: %s', 'ps-stats' ),
								get_option( 'ps_stats_install_error' )
							)
						);
						?>
					</p>
				</div>
			<?php endif; ?>
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

			<?php Evaluation::render( 'dashboard' ); ?>
		</div>
		<?php
	}

	public static function render_settings(): void {
		self::guard();
		$settings = Plugin::settings();
		$bounds = Provider::instance()->date_bounds();
		$migration = Migration::state();
		$next_cleanup = wp_next_scheduled( Plugin::CRON_CLEANUP );
		?>
		<div class="wrap ps-stats">
			<h1><?php esc_html_e( 'PS Stats – Einstellungen', 'ps-stats' ); ?></h1>
			<?php self::notice(); ?>
			<?php Status::render(); ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Datensparsame Erfassung: Nur die Startseite und kanonische, veröffentlichte öffentliche Einzelinhalte ohne Passwort werden neu gezählt. Unbekannte dynamische Routen, Autoren-/Mitgliederseiten und typische Konto-, Bestell-, Checkout-, Nachrichten- und Download-Pfade werden ausgeschlossen. Eigene sensible öffentliche Inhalte müssen zusätzlich ausgeschlossen werden. Bestehende Daten werden nicht automatisch umgeschrieben.', 'ps-stats' ); ?></p></div>
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
			<?php if ( 'complete' === $migration['status'] && Database::table_exists( Database::legacy_table() ) ) : ?>
				<div class="notice notice-warning inline"><p><?php esc_html_e( 'Die alte Quelltabelle ist noch vorhanden und kann vollständige Referrer oder sensible Pfade enthalten. Nach Prüfung der Migration ausdrücklich löschen; auch Sicherungskopien separat berücksichtigen.', 'ps-stats' ); ?></p></div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="ps_stats_delete_legacy">
					<?php wp_nonce_field( 'ps_stats_delete_legacy' ); ?>
					<label><input type="checkbox" name="confirm" value="1" required> <?php esc_html_e( 'Migration geprüft; alte Quelltabelle unwiderruflich entfernen.', 'ps-stats' ); ?></label>
					<?php submit_button( __( 'Alte Quelltabelle löschen', 'ps-stats' ), 'secondary', 'submit', false ); ?>
				</form>
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
							<?php foreach ( array_unique( array( 7, 14, 30, 90, 180, 365, 0, $settings['retention'] ) ) as $days ) : ?>
								<option value="<?php echo esc_attr( $days ); ?>" <?php selected( $settings['retention'], $days ); ?>>
									<?php echo esc_html( 0 === $days ? __( 'Unbegrenzt', 'ps-stats' ) : sprintf( __( '%d Tage', 'ps-stats' ), $days ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Eine kürzere Frist wirkt beim nächsten Bereinigungslauf auch auf vorhandene Daten.', 'ps-stats' ); ?></p>
						<label><input type="checkbox" name="confirm_shorter_retention" value="1"> <?php esc_html_e( 'Falls ich die Frist verkürze: automatische Löschung älterer Daten ausdrücklich bestätigen.', 'ps-stats' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Referrer-Erfassung', 'ps-stats' ); ?></th>
						<td><label><input type="checkbox" name="collect_referrers" value="1" <?php checked( $settings['collect_referrers'] ); ?>> <?php esc_html_e( 'Neue Referrer nur aus der folgenden Freigabeliste erfassen', 'ps-stats' ); ?></label>
						<p><label for="ps-stats-referrer-domains"><?php esc_html_e( 'Freigegebene externe Domains (exakte Domain, eine pro Zeile)', 'ps-stats' ); ?></label></p>
						<textarea id="ps-stats-referrer-domains" name="referrer_domains" rows="4" class="large-text code"><?php echo esc_textarea( implode( "\n", $settings['referrer_domains'] ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Keine Freigabe für personenbezogene oder kundenspezifische Subdomains erteilen. Andere Herkünfte werden ohne Domain gezählt. Deaktivieren löscht keine alten Referrer; dafür die Datenpflege verwenden.', 'ps-stats' ); ?></p></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'MarketPress-Zusatzstatistik', 'ps-stats' ); ?></th>
						<td><label><input type="checkbox" name="marketpress_metrics" value="1" <?php checked( $settings['marketpress_metrics'] ); ?>> <?php esc_html_e( 'Vorhandene MarketPress-Statistik als nur lesende Monatsquelle verwenden', 'ps-stats' ); ?></label>
						<p class="description"><?php esc_html_e( 'Standardmäßig aus. Unter PS Stats → MarketPress werden Bestellwerte und Gratis-Download-Ereignisse aus der aktivierten MarketPress-Statistik zusammengefasst. PS Stats erfasst keine eigenen Shop-Ereignisse und kopiert keine Kennungen. Die Quelldaten und deren Löschfristen bleiben in MarketPress. Vorhandene alte PS-Stats-Shopkennzahlen bleiben unverändert und werden nicht addiert.', 'ps-stats' ); ?></p></td>
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
			<?php echo wpautop( esc_html( Privacy::text() ) ); ?>
		</div>
		<?php
	}

	public static function save_settings(): void {
		self::verify( 'ps_stats_save' );
		$current = Plugin::settings();
		$allowed = array( 0, 7, 14, 30, 90, 180, 365, $current['retention'] );
		$retention = isset( $_POST['retention'] ) && is_scalar( $_POST['retention'] ) ? filter_var( $_POST['retention'], FILTER_VALIDATE_INT ) : false;
		if ( false === $retention || ! in_array( $retention, $allowed, true ) ) {
			self::redirect( 'invalid_settings', true );
		}
		if ( 0 !== $retention && ( 0 === $current['retention'] || $retention < $current['retention'] ) && '1' !== ( $_POST['confirm_shorter_retention'] ?? '' ) ) {
			self::redirect( 'retention_confirmation_required', true );
		}
		$domains = array();
		$raw_domains = isset( $_POST['referrer_domains'] ) && is_string( $_POST['referrer_domains'] ) ? wp_unslash( $_POST['referrer_domains'] ) : '';
		foreach ( preg_split( '/\R/', $raw_domains ) as $domain ) {
			$domain = strtolower( trim( $domain ) );
			if ( '' === $domain ) {
				continue;
			}
			if ( Normalizer::referrer_domain( 'https://' . $domain ) !== $domain ) {
				self::redirect( 'invalid_settings', true );
			}
			$domains[] = $domain;
		}
		if ( isset( $_POST['excluded_paths'] ) && ! is_string( $_POST['excluded_paths'] ) ) {
			self::redirect( 'invalid_settings', true );
		}
		$raw = isset( $_POST['excluded_paths'] ) ? sanitize_textarea_field( wp_unslash( $_POST['excluded_paths'] ) ) : '';
		$paths     = array();
		foreach ( preg_split( '/\R/', $raw ) ?: array() as $path ) {
			if ( '' !== trim( $path ) ) {
				$paths[] = Normalizer::target_path( $path );
			}
		}
		$new = array_merge( $current, array(
			'retention' => $retention,
			'skip_logged_in' => isset( $_POST['skip_logged_in'] ),
			'excluded_paths' => array_values( array_unique( $paths ) ),
			'collect_referrers' => isset( $_POST['collect_referrers'] ),
			'referrer_domains' => array_values( array_unique( $domains ) ),
			'marketpress_metrics' => isset( $_POST['marketpress_metrics'] ),
		) );
		update_option(
			Plugin::OPTION_SETTINGS,
			$new,
			false
		);
		if ( get_option( Plugin::OPTION_SETTINGS ) !== $new ) {
			self::redirect( 'db_error', true );
		}
		self::redirect( 'saved' );
	}

	public static function delete_before(): void {
		self::verify_destructive( 'ps_stats_delete_before' );
		$date = isset( $_POST['before_date'] ) ? sanitize_text_field( wp_unslash( $_POST['before_date'] ) ) : '';
		$valid = \DateTimeImmutable::createFromFormat( '!Y-m-d', $date );
		if ( false === $valid || $valid->format( 'Y-m-d' ) !== $date ) {
			self::redirect( 'invalid_date', true );
		}
		$success = Database::delete_before( $date );
		self::redirect( $success ? 'deleted_before' : 'db_error', ! $success );
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

	public static function delete_legacy(): void {
		self::verify_destructive( 'ps_stats_delete_legacy' );
		$success = Migration::delete_legacy();
		self::redirect( $success ? 'legacy_deleted' : 'db_error', ! $success );
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
		if ( '' !== $wpdb->last_error ) {
			do_action( 'ps_stats_database_error', 'overview', $wpdb->last_error );
			wp_die( esc_html__( 'Die Statistikübersicht konnte nicht geladen werden.', 'ps-stats' ), 500 );
		}
		return array_map( 'intval', is_array( $row ) ? $row : array( 'total' => 0, 'today' => 0, 'paths' => 0, 'domains' => 0 ) );
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

	private static function redirect( string $message, bool $error = false ): void {
		wp_safe_redirect( add_query_arg( array( 'page' => self::SETTINGS_PAGE, 'ps_stats_notice' => $message, 'ps_stats_error' => $error ? 1 : 0 ), admin_url( 'options-general.php' ) ) );
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
			'legacy_deleted' => __( 'Alte Quelltabelle gelöscht.', 'ps-stats' ),
			'invalid_settings' => __( 'Ungültige Einstellungen. Bitte Aufbewahrung und Domain-Freigaben prüfen. Es wurde nichts gespeichert.', 'ps-stats' ),
			'retention_confirmation_required' => __( 'Die kürzere Aufbewahrung wurde nicht gespeichert: Bitte die automatische Löschung ausdrücklich bestätigen.', 'ps-stats' ),
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
