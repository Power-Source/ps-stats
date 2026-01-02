<?php
/**
 * PS-Stats: Widget Backend View
 *
 * This file contains the viewmodel for the plugin's widget backend.
 *
 * @package   PS-Stats
 */

// Quit if accessed outside WP context.
class_exists( 'PSStats' ) || exit; ?>

<?php if ( current_user_can( 'manage_options' ) ) : ?>
<p class="meta-links settings-link">
	<a href="<?php echo esc_attr( add_query_arg( array( 'page' => 'psstats-settings' ), admin_url( '/options-general.php' ) ) ); ?>"
		title="<?php esc_attr_e( 'Open full settings page', 'psstats' ); ?>">
		<span class="dashicons dashicons-admin-generic"></span>
		<?php esc_html_e( 'All Settings', 'psstats' ); ?></a>
</p>

<br>
<?php endif; ?>

<h3><?php esc_html_e( 'Widget Settings', 'psstats' ); ?></h3>
<div>
	<label>
		<input name="psstats[days_show]" type="number" min="1"
			   value="<?php echo esc_attr( PSStats::$_options['days_show'] ); ?>">
		<?php esc_html_e( 'days', 'psstats' ); ?> -
		<?php esc_html_e( 'Period of data display in Dashboard', 'psstats' ); ?>
	</label>
	<label>
		<input name="psstats[limit]" type="number" min="1" max="100"
			   value="<?php echo esc_attr( PSStats::$_options['limit'] ); ?>">
		<?php esc_html_e( 'Number of entries in top lists', 'psstats' ); ?>
	</label>
	<label>
		<input type="checkbox" name="psstats[today]" value="1" <?php checked( PSStats::$_options['today'], 1 ); ?>>
		<?php esc_html_e( 'Entries in top lists only for today', 'psstats' ); ?>
	</label>
	<label>
		<input type="checkbox" name="psstats[show_totals]" value="1" <?php checked( PSStats::$_options['show_totals'], 1 ); ?>>
		<?php esc_html_e( 'Show totals', 'psstats' ); ?>
	</label>
</div>
<?php wp_nonce_field( 'psstats-dashboard' ); ?>

<p class="meta-links">
	<a href="<?php echo esc_url( __( 'https://github.com/cp-psource/ps-stats/wiki', 'psstats' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Dokumentation', 'psstats' ); ?></a>
	&bull; <a href="<?php echo esc_url( __( 'https://github.com/cp-psource/ps-stats/discussions', 'psstats' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'psstats' ); ?></a>
</p>
