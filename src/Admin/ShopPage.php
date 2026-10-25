<?php

namespace PSStats\Admin;

use PSStats\Integration\MarketPress;
use PSStats\Plugin;

defined( 'ABSPATH' ) || exit;

final class ShopPage {
	public static function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
		$from = isset( $_GET['from'] ) && is_string( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : current_time( 'Y-01-01' );
		$to = isset( $_GET['to'] ) && is_string( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : current_time( 'Y-m-d' );
		?>
		<div class="wrap eefpsstats">
			<h1><?php esc_html_e( 'PS Stats – MarketPress-Quelle', 'ps-stats' ); ?></h1>
			<p><?php esc_html_e( 'Nur lesende Monatsauswertung der vorhandenen MarketPress-Statistik. Keine zweite Erfassung, kein Import und keine Speicherung von Bestell- oder Käuferkennungen in PS Stats.', 'ps-stats' ); ?></p>
			<p><?php esc_html_e( 'Der Bestellwert entspricht der Quelle: Summe von mp_order_total nach Bestelldatum, ohne Zahlungsstatusfilter und ohne gesonderte Erstattungskorrektur. Das ist kein bestätigter Netto- oder Zahlungsumsatz. Gratis-Downloads sind Ereignisse, keine eindeutigen Personen oder garantierten Dateitransfers. Die Werte werden in der Shop-Währung angezeigt.', 'ps-stats' ); ?></p>
			<form method="get">
				<input type="hidden" name="page" value="ps-stats-shop">
				<label for="shop-from"><?php esc_html_e( 'Von', 'ps-stats' ); ?></label>
				<input id="shop-from" type="date" name="from" value="<?php echo esc_attr( $from ); ?>" required>
				<label for="shop-to"><?php esc_html_e( 'Bis', 'ps-stats' ); ?></label>
				<input id="shop-to" type="date" name="to" value="<?php echo esc_attr( $to ); ?>" required>
				<?php submit_button( __( 'Zeitraum anzeigen', 'ps-stats' ), 'secondary', 'submit', false ); ?>
			</form>
			<?php
			if ( ! Plugin::settings()['marketpress_metrics'] ) {
				echo '<p>' . esc_html__( 'Die MarketPress-Quelle ist deaktiviert. Du kannst sie unter Einstellungen → PS Stats freigeben.', 'ps-stats' ) . '</p></div>';
				return;
			}
			try {
				$rows = MarketPress::summary( $from, $to );
			} catch ( \RuntimeException | \InvalidArgumentException $error ) {
				echo '<div class="notice notice-error"><p>' . esc_html( $error->getMessage() ) . '</p></div></div>';
				return;
			}
			?>
			<table class="widefat striped">
				<caption><?php esc_html_e( 'Monatswerte aus MarketPress', 'ps-stats' ); ?></caption>
				<thead><tr><th scope="col"><?php esc_html_e( 'Monat', 'ps-stats' ); ?></th><th scope="col"><?php esc_html_e( 'Bestellwert (Shop-Währung)', 'ps-stats' ); ?></th><th scope="col"><?php esc_html_e( 'Gratis-Download-Ereignisse', 'ps-stats' ); ?></th></tr></thead>
				<tbody><?php foreach ( $rows as $row ) : ?><tr><th scope="row"><?php echo esc_html( $row['month'] ); ?></th><td><?php echo esc_html( number_format_i18n( $row['order_value'], 2 ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['free_downloads'] ) ); ?></td></tr><?php endforeach; ?>
				<?php if ( ! $rows ) : ?><tr><td colspan="3"><?php esc_html_e( 'Noch keine Daten im gewählten Zeitraum.', 'ps-stats' ); ?></td></tr><?php endif; ?></tbody>
			</table>
		</div>
		<?php
	}
}
