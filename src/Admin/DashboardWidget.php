<?php

namespace PSStats\Admin;

use PSStats\Api\Provider;

defined( 'ABSPATH' ) || exit;

final class DashboardWidget {
	public static function register(): void {
		if ( ! apply_filters( 'psstats__user_can_see_stats', current_user_can( 'edit_dashboard' ) ) ) {
			return;
		}
		wp_add_dashboard_widget( 'psstats_dashboard', 'PS Stats', array( self::class, 'render' ), array( self::class, 'configure' ) );
		Evaluation::assets();
	}

	private static function settings(): array {
		$legacy = get_option( 'psstats', array() );
		$options = get_option( 'ps_stats_dashboard', is_array( $legacy ) ? $legacy : array() );
		$options = is_array( $options ) ? $options : array();
		return array(
			'days_show' => min( 365, max( 1, (int) ( $options['days_show'] ?? 14 ) ) ),
			'limit' => min( 50, max( 1, (int) ( $options['limit'] ?? 5 ) ) ),
			'today' => ! empty( $options['today'] ),
			'show_totals' => ! isset( $options['show_totals'] ) || ! empty( $options['show_totals'] ),
		);
	}

	public static function configure(): void {
		if ( ! current_user_can( 'edit_dashboard' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
		if ( isset( $_POST['ps_stats_dashboard_save'] ) ) {
			check_admin_referer( 'ps_stats_dashboard' );
			$days = isset( $_POST['days_show'] ) && is_scalar( $_POST['days_show'] ) ? filter_var( $_POST['days_show'], FILTER_VALIDATE_INT ) : false;
			$limit = isset( $_POST['limit'] ) && is_scalar( $_POST['limit'] ) ? filter_var( $_POST['limit'], FILTER_VALIDATE_INT ) : false;
			if ( false === $days || $days < 1 || $days > 365 || false === $limit || $limit < 1 || $limit > 50 ) {
				wp_die( esc_html__( 'Bitte 1–365 Tage und 1–50 Listeneinträge angeben.', 'ps-stats' ), 400 );
			}
			$value = array( 'days_show' => $days, 'limit' => $limit, 'today' => isset( $_POST['today'] ), 'show_totals' => isset( $_POST['show_totals'] ) );
			update_option( 'ps_stats_dashboard', $value, false );
			if ( get_option( 'ps_stats_dashboard' ) !== $value ) {
				wp_die( esc_html__( 'Widget-Einstellungen konnten nicht gespeichert werden.', 'ps-stats' ), 500 );
			}
		}
		$settings = self::settings();
		wp_nonce_field( 'ps_stats_dashboard' );
		?>
		<input type="hidden" name="ps_stats_dashboard_save" value="1">
		<p><label for="ps-stats-widget-days"><?php esc_html_e( 'Angezeigte Tage', 'ps-stats' ); ?></label> <input id="ps-stats-widget-days" type="number" min="1" max="365" name="days_show" value="<?php echo esc_attr( $settings['days_show'] ); ?>" required></p>
		<p><label for="ps-stats-widget-limit"><?php esc_html_e( 'Einträge pro Rangliste', 'ps-stats' ); ?></label> <input id="ps-stats-widget-limit" type="number" min="1" max="50" name="limit" value="<?php echo esc_attr( $settings['limit'] ); ?>" required></p>
		<p><label><input type="checkbox" name="today" value="1" <?php checked( $settings['today'] ); ?>> <?php esc_html_e( 'Ranglisten nur für heute', 'ps-stats' ); ?></label></p>
		<p><label><input type="checkbox" name="show_totals" value="1" <?php checked( $settings['show_totals'] ); ?>> <?php esc_html_e( 'Gesamtwerte anzeigen', 'ps-stats' ); ?></label></p>
		<?php
	}

	public static function render(): void {
		if ( ! apply_filters( 'psstats__user_can_see_stats', current_user_can( 'edit_dashboard' ) ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
		$today = current_time( 'Y-m-d' );
		$settings = self::settings();
		$from = gmdate( 'Y-m-d', strtotime( $today . ' -' . ( $settings['days_show'] - 1 ) . ' days' ) );
		$ranking_from = $settings['today'] ? $today : $from;
		$provider = Provider::instance();
		$rows = $provider->daily_visits( $from, $today );
		$values = array_column( $rows, 'views', 'date' );
		$data = array();
		for ( $date = $from; $date <= $today; $date = gmdate( 'Y-m-d', strtotime( $date . ' +1 day' ) ) ) {
			$data[] = array( $date, $values[ $date ] ?? 0 );
		}
		?>
		<div class="eefpsstats">
			<h3><?php echo esc_html( sprintf( __( 'Seitenaufrufe der letzten %d Tage', 'ps-stats' ), $settings['days_show'] ) ); ?></h3>
			<div id="ps-stats-dashboard-chart" role="img" aria-label="<?php esc_attr_e( 'Aufrufe pro Tag; die Werte stehen in der folgenden Tabelle.', 'ps-stats' ); ?>"></div>
			<script>jQuery(function () { eefpsstatsLineChart('#ps-stats-dashboard-chart', <?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>); });</script>
			<details><summary><?php esc_html_e( 'Tageswerte anzeigen', 'ps-stats' ); ?></summary>
			<table class="widefat striped"><caption class="screen-reader-text"><?php esc_html_e( 'Aufrufe pro Tag', 'ps-stats' ); ?></caption>
				<thead><tr><th scope="col"><?php esc_html_e( 'Datum', 'ps-stats' ); ?></th><th scope="col"><?php esc_html_e( 'Aufrufe', 'ps-stats' ); ?></th></tr></thead>
				<tbody><?php foreach ( $data as $row ) : ?><tr><th scope="row"><?php echo esc_html( $row[0] ); ?></th><td><?php echo esc_html( number_format_i18n( $row[1] ) ); ?></td></tr><?php endforeach; ?></tbody>
			</table></details>
			<?php foreach ( array(
				array( __( 'Häufigste Inhalte', 'ps-stats' ), $provider->landing_page_stats( $ranking_from, $today ), 'path' ),
				array( __( 'Externe Referrer', 'ps-stats' ), $provider->referrer_stats( $ranking_from, $today ), 'domain' ),
			) as $ranking ) : ?>
				<h3><?php echo esc_html( $ranking[0] ); ?></h3>
				<ol><?php foreach ( array_slice( $ranking[1], 0, $settings['limit'] ) as $row ) : ?><li><?php echo esc_html( $row[ $ranking[2] ] . ': ' . number_format_i18n( $row['views'] ) ); ?></li><?php endforeach; ?></ol>
				<?php if ( ! $ranking[1] ) : ?><p><?php esc_html_e( 'Noch keine Daten.', 'ps-stats' ); ?></p><?php endif; ?>
			<?php endforeach; ?>
			<?php if ( $settings['show_totals'] ) :
				$bounds = $provider->date_bounds();
				$all = $provider->daily_visits( $bounds['oldest'] ?: $today, max( $bounds['newest'] ?: $today, $today ) );
				?>
				<p><?php echo esc_html( sprintf( __( 'Heute: %1$s · Insgesamt: %2$s', 'ps-stats' ), number_format_i18n( $values[ $today ] ?? 0 ), number_format_i18n( array_sum( array_column( $all, 'views' ) ) ) ) ); ?></p>
			<?php endif; ?>
			<?php if ( current_user_can( 'manage_options' ) || current_user_can( 'see_psstats_evaluation' ) ) : ?>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=ps-stats' ) ); ?>"><?php esc_html_e( 'Vollständige Auswertung öffnen', 'ps-stats' ); ?></a></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
