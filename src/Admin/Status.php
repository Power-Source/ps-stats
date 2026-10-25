<?php

namespace PSStats\Admin;

use PSStats\Database;
use PSStats\Migration;
use PSStats\Plugin;

defined( 'ABSPATH' ) || exit;

final class Status {
	public static function render(): void {
		$last = (int) get_option( 'ps_stats_cleanup_last_success', 0 );
		$next = wp_next_scheduled( Plugin::CRON_CLEANUP );
		$error = get_option( 'ps_stats_cleanup_error', '' );
		$cache = ( defined( 'WP_CACHE' ) && WP_CACHE ) || file_exists( WP_CONTENT_DIR . '/advanced-cache.php' );
		?>
		<h2><?php esc_html_e( 'Systemstatus und Zählgrenzen', 'ps-stats' ); ?></h2>
		<ul>
			<li><?php echo esc_html( sprintf( __( 'Statistiktabellen: %s', 'ps-stats' ), Database::table_exists( Database::table() ) && Database::table_exists( Database::metrics_table() ) ? __( 'vorhanden', 'ps-stats' ) : __( 'unvollständig', 'ps-stats' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Letzte erfolgreiche Bereinigung: %s', 'ps-stats' ), $last ? wp_date( 'Y-m-d H:i', $last ) : __( 'noch nicht protokolliert', 'ps-stats' ) ) ); ?></li>
			<li><?php echo esc_html( sprintf( __( 'Migration: %s; aus Datenschutzgründen übersprungene Altzeilen: %d', 'ps-stats' ), Migration::state()['status'], Migration::state()['skipped'] ) ); ?></li>
		</ul>
		<?php if ( $error || ( Plugin::settings()['retention'] && ( ! $next || $next < time() - DAY_IN_SECONDS || ( $last && $last < time() - 2 * DAY_IN_SECONDS ) ) ) ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $error ?: __( 'Die Bereinigung ist nicht geplant oder überfällig. Bitte einen zuverlässigen Server-Cron einrichten und die Ausführung prüfen.', 'ps-stats' ) ); ?></p></div>
		<?php endif; ?>
		<?php if ( get_option( 'ps_stats_database_error' ) ) : ?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( sprintf( __( 'Zuletzt protokollierter Datenbankfehler: %s. Dieser Hinweis ist ein gespeicherter Fehlerstatus, keine aktuelle Verfügbarkeitsprüfung. Bitte die betroffene Funktion prüfen. Es wurden keine Anfrageinhalte im Fehlerstatus gespeichert.', 'ps-stats' ), get_option( 'ps_stats_database_error' ) ) ); ?></p></div>
		<?php endif; ?>
		<p><?php echo esc_html( $cache ? __( 'Ein Seiten-Cache wurde erkannt. Cache-Treffer können PHP umgehen und fehlen dann in dieser Statistik.', 'ps-stats' ) : __( 'Kein lokaler Seiten-Cache erkannt. Vorgeschaltete Caches oder CDNs können trotzdem PHP umgehen und zu Unterzählung führen.', 'ps-stats' ) ); ?></p>
		<p><?php esc_html_e( 'Gezählt werden ausschließlich geeignete GET-Anfragen, die ClassicPress/PHP erreichen. Kein Tracking-Pixel, kein Browser-Beacon und keine Umgehung des Caches werden automatisch installiert. Bots werden nur bestmöglich gefiltert; Aufrufe sind keine Besucherzahlen. Änderungen der Regeln wirken nur auf neue Daten.', 'ps-stats' ); ?></p>
		<?php if ( ! get_option( 'permalink_structure' ) ) : ?>
			<div class="notice notice-warning inline"><p><?php esc_html_e( 'Es sind keine sprechenden Permalinks eingestellt. Inhalte mit ?p= oder ?page_id= werden nicht als Startseitenaufrufe fehlgezählt, sondern ausgeschlossen. Für eine inhaltsbezogene Pfadauswertung benötigst Du sprechende Permalinks.', 'ps-stats' ); ?></p></div>
		<?php endif; ?>
		<?php
	}
}
