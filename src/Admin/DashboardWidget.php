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
		$style_path = PS_STATS_DIR . '/css/dashboard.css';
		$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : PS_STATS_VERSION;
		wp_enqueue_style(
			'ps-stats-dashboard',
			plugins_url( 'css/dashboard.css', PS_STATS_FILE ),
			array( 'ps-stats-evaluation' ),
			$style_version
		);
	}

	public static function settings(): array {
		$legacy = get_option( 'psstats', array() );
		$options = get_option( 'ps_stats_dashboard', is_array( $legacy ) ? $legacy : array() );
		$options = is_array( $options ) ? $options : array();
		return array(
			'days_show' => min( 3650, max( 1, (int) ( $options['days_show'] ?? 14 ) ) ),
			'limit' => min( 100, max( 1, (int) ( $options['limit'] ?? 3 ) ) ),
			'today' => ! empty( $options['today'] ),
			'show_totals' => ! empty( $options['show_totals'] ),
		);
	}

	public static function update_settings( array $settings ): bool {
		$value = array(
			'days_show' => (int) $settings['days_show'],
			'limit' => (int) $settings['limit'],
			'today' => (bool) $settings['today'],
			'show_totals' => (bool) $settings['show_totals'],
		);
		update_option( 'ps_stats_dashboard', $value, false );
		return get_option( 'ps_stats_dashboard' ) === $value;
	}

	public static function configure(): void {
		if ( ! current_user_can( 'edit_dashboard' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
		if ( isset( $_POST['ps_stats_dashboard_save'] ) ) {
			check_admin_referer( 'ps_stats_dashboard' );
			$days = isset( $_POST['days_show'] ) && is_scalar( $_POST['days_show'] ) ? filter_var( $_POST['days_show'], FILTER_VALIDATE_INT ) : false;
			$limit = isset( $_POST['limit'] ) && is_scalar( $_POST['limit'] ) ? filter_var( $_POST['limit'], FILTER_VALIDATE_INT ) : false;
			if ( false === $days || $days < 1 || $days > 3650 || false === $limit || $limit < 1 || $limit > 100 ) {
				wp_die( esc_html__( 'Bitte 1–3650 Tage und 1–100 Listeneinträge angeben.', 'ps-stats' ), 400 );
			}
			$value = array( 'days_show' => $days, 'limit' => $limit, 'today' => isset( $_POST['today'] ), 'show_totals' => isset( $_POST['show_totals'] ) );
			if ( ! self::update_settings( $value ) ) {
				wp_die( esc_html__( 'Widget-Einstellungen konnten nicht gespeichert werden.', 'ps-stats' ), 500 );
			}
		}
		$settings = self::settings();
		wp_nonce_field( 'ps_stats_dashboard' );
		?>
		<input type="hidden" name="ps_stats_dashboard_save" value="1">
		<p><label for="ps-stats-widget-days"><?php esc_html_e( 'Angezeigte Tage', 'ps-stats' ); ?></label> <input id="ps-stats-widget-days" type="number" min="1" max="3650" name="days_show" value="<?php echo esc_attr( $settings['days_show'] ); ?>" required></p>
		<p><label for="ps-stats-widget-limit"><?php esc_html_e( 'Einträge pro Rangliste', 'ps-stats' ); ?></label> <input id="ps-stats-widget-limit" type="number" min="1" max="100" name="limit" value="<?php echo esc_attr( $settings['limit'] ); ?>" required></p>
		<p><label><input type="checkbox" name="today" value="1" <?php checked( $settings['today'] ); ?>> <?php esc_html_e( 'Ranglisten nur für heute', 'ps-stats' ); ?></label></p>
		<p><label><input type="checkbox" name="show_totals" value="1" <?php checked( $settings['show_totals'] ); ?>> <?php esc_html_e( 'Gesamtwerte anzeigen', 'ps-stats' ); ?></label></p>
		<?php
	}

	public static function render(): void {
		if ( ! apply_filters( 'psstats__user_can_see_stats', current_user_can( 'edit_dashboard' ) ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
		if ( isset( $_POST['psstats-fresh'] ) ) {
			check_admin_referer( 'psstats-dashboard-refresh' );
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
		$content = array_values( array_filter(
			$provider->landing_page_stats( $ranking_from, $today ),
			static fn ( array $row ): bool => null !== \PSStats\Privacy::route( $row['path'] )
		) );
		$referrers = $provider->referrer_stats( $ranking_from, $today );
		?>
		<div id="psstats_chart" role="img" aria-label="<?php esc_attr_e( 'Seitenaufrufe pro Tag', 'ps-stats' ); ?>">
			<table id="psstats_chart_data">
				<tbody><?php foreach ( $data as $row ) : ?>
					<tr><th><?php echo esc_html( $row[0] ); ?></th><td><?php echo esc_html( $row[1] ); ?></td></tr>
				<?php endforeach; ?></tbody>
			</table>
		</div>
		<script>jQuery(function () { eefpsstatsLineChart('#psstats_chart', <?php echo wp_json_encode( $data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>, 'dashboard'); });</script>
		<?php if ( $content ) : ?>
			<div class="table target">
				<p class="sub"><?php esc_html_e( 'Häufigste Inhalte', 'ps-stats' ); ?></p>
				<div><table>
					<?php foreach ( array_slice( $content, 0, $settings['limit'] ) as $row ) : ?>
						<tr>
							<td class="b"><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
							<td class="t"><a href="<?php echo esc_url( \PSStats\Normalizer::local_url( $row['path'] ) ); ?>"><?php echo esc_html( $row['path'] ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</table></div>
			</div>
		<?php endif; ?>
		<?php if ( $referrers ) : ?>
			<div class="table referrer">
				<p class="sub"><?php esc_html_e( 'Externe Referrer', 'ps-stats' ); ?></p>
				<div><table>
					<?php foreach ( array_slice( $referrers, 0, $settings['limit'] ) as $row ) : ?>
						<tr>
							<td class="b"><?php echo esc_html( number_format_i18n( $row['views'] ) ); ?></td>
							<td class="t"><a href="<?php echo esc_url( 'https://' . $row['domain'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $row['domain'] ); ?></a></td>
						</tr>
					<?php endforeach; ?>
				</table></div>
			</div>
		<?php endif; ?>
			<?php if ( $settings['show_totals'] ) :
				$bounds = $provider->date_bounds();
				$all = $provider->daily_visits( $bounds['oldest'] ?: $today, max( $bounds['newest'] ?: $today, $today ) );
				?>
				<div class="table total">
					<p class="sub"><?php esc_html_e( 'Summen', 'ps-stats' ); ?></p>
					<div><table>
						<tr><td class="b"><?php echo esc_html( number_format_i18n( $values[ $today ] ?? 0 ) ); ?></td><td class="t"><?php esc_html_e( 'Heute', 'ps-stats' ); ?></td></tr>
						<tr><td class="b"><?php echo esc_html( number_format_i18n( array_sum( array_column( $all, 'views' ) ) ) ); ?></td><td class="t"><?php esc_html_e( 'Insgesamt', 'ps-stats' ); ?></td></tr>
					</table></div>
				</div>
			<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'psstats-dashboard-refresh' ); ?>
			<button type="submit" class="button button-primary" name="psstats-fresh"><?php esc_html_e( 'Aktualisieren', 'ps-stats' ); ?></button>
		</form>
		<?php if ( current_user_can( 'manage_options' ) || current_user_can( 'see_psstats_evaluation' ) ) : ?>
			<p class="meta-links"><a href="<?php echo esc_url( admin_url( 'admin.php?page=ps-stats' ) ); ?>"><?php esc_html_e( 'Vollständige Auswertung öffnen', 'ps-stats' ); ?></a></p>
		<?php endif; ?>
		<?php
	}
}
