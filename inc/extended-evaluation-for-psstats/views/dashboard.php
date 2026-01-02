<?php
/**
 * The dashboard page.
 *
 * @package extended-evaluation-for-psstats
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get the selected post if one is set, otherwise: all posts.
if ( isset( $_POST['post'] ) && check_admin_referer( 'dashboard' ) ) {
	$selected_post = sanitize_text_field( wp_unslash( $_POST['post'] ) );
} elseif ( isset( $_GET['post'] ) ) {
	$selected_post = sanitize_text_field( wp_unslash( $_GET['post'] ) );
} else {
	$selected_post = '';
}

// Get the data necessary for all tabs.
$years = eefpsstats_get_years();
$months = eefpsstats_get_months();
$views_for_all_months = eefpsstats_get_views_for_all_months( $selected_post );

// Get the selected tab.
if ( isset( $_GET['year'] ) && 4 === strlen( sanitize_text_field( wp_unslash( $_GET['year'] ) ) ) ) {
	$selected_year = (int) sanitize_text_field( wp_unslash( $_GET['year'] ) );

	// Get the data shown on daily details tab for one year.
	$days = eefpsstats_get_days();
	$views_for_all_days = eefpsstats_get_views_for_all_days( $selected_post );
} else {
	$selected_year = 0; // 0 = show overview tab.

	// Get data shown on overview tab.
	$views_for_all_years = eefpsstats_get_views_for_all_years( $selected_post );
	$post_types = eefpsstats_get_post_types();
}
?>
<div class="wrap eefpsstats">
	<h1><?php esc_html_e( 'PSStats – Extended Evaluation', 'psstats' ); ?></h1>

	<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_html_e( 'Overview and Years', 'psstats' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=extended_evaluation_for_psstats_dashboard&post=' . $selected_post ) ); ?>"
			class="<?php eefpsstats_echo_tab_class( 0 === $selected_year ); ?>"><?php esc_html_e( 'Overview', 'psstats' ); ?></a>
	<?php foreach ( $years as $year ) { ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=extended_evaluation_for_psstats_dashboard&year=' . $year . '&post=' . $selected_post ) ); ?>"
			class="<?php eefpsstats_echo_tab_class( $selected_year === $year ); ?>"><?php echo esc_html( $year ); ?></a>
	<?php } ?>
	</nav>
	<form method="post" action="">
		<?php wp_nonce_field( 'dashboard' ); ?>
		<fieldset>
			<legend><?php esc_html_e( 'Per default the views of all posts are shown. To restrict the evaluation to one post/page, enter their path or name.', 'psstats' ); ?></legend>
			<?php eefpsstats_echo_post_selection( $selected_post ); ?>
			<button type="submit" class="button-secondary"><?php esc_html_e( 'Select post/page', 'psstats' ); ?></button>
		</fieldset>
	</form>
<?php
if ( 0 === $selected_year ) {
	// Display the overview tab.
	$filename_monthly = eefpsstats_get_filename(
		__( 'Monthly Views', 'psstats' )
		. '-' . eefpsstats_get_post_title_from_url( $selected_post )
	);
	?>
	<?php if ( count( $views_for_all_months ) === 0 ) { ?>
	<p><?php esc_html_e( 'No data available.', 'psstats' ); ?></p>
	<?php } else { ?>
	<section>
		<?php
		eefpsstats_echo_chart_container(
			'chart-monthly',
			__( 'Monthly Views', 'psstats' ),
			eefpsstats_get_post_title_from_url( $selected_post )
		);
		eefpsstats_echo_chart_container(
			'chart-yearly',
			__( 'Yearly Views', 'psstats' ),
			eefpsstats_get_post_title_from_url( $selected_post )
		);
		?>
		<script type="text/javascript">
			eefpsstatsLineChart(
				'#chart-monthly',
				[
					<?php
					foreach ( $views_for_all_months as $month => $views ) {
						echo "['" . esc_js( eefpsstats_get_month_year_name( $month ) ) . "'," . esc_js( $views ) . '],';
					}
					?>
				]
			);
			eefpsstatsLineChart(
				'#chart-yearly',
				[
					<?php
					foreach ( $views_for_all_years as $year => $views ) {
						echo "['" . esc_js( $year ) . "'," . esc_js( $views ) . '],';
					}
					?>
				]
			);
		</script>
	</section>
	<section>
		<h3><?php esc_html_e( 'Monthly / Yearly Views', 'psstats' ); ?>
			<?php
			echo esc_html( eefpsstats_get_post_type_name_and_title_from_url( $selected_post ) );
			eefpsstats_echo_export_button( $filename_monthly );
			?>
			</h3>
		<table id="table-data" class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Year', 'psstats' ); ?></th>
					<?php foreach ( $months as $month ) { ?>
					<th scope="col"><?php echo esc_html( eefpsstats_get_month_name( $month ) ); ?></th>
					<?php } ?>
					<th scope="col" class="right sum"><?php esc_html_e( 'Sum', 'psstats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $years as $year ) { ?>
				<tr>
					<th scope="row"><a href="<?php echo esc_url( admin_url( 'admin.php?page=extended_evaluation_for_psstats_dashboard&year=' . $year ) ); ?>"><?php echo esc_html( $year ); ?></a></th>
					<?php foreach ( $months as $month ) { ?>
					<td class="right"><?php eefpsstats_echo_number( eefpsstats_get_monthly_views( $views_for_all_months, $year, $month ) ); ?></td>
					<?php } ?>
					<td class="right sum"><?php eefpsstats_echo_number( eefpsstats_get_yearly_views( $views_for_all_years, $year ) ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
		</table>
	</section>
	<?php } ?>
	<?php
} else {
	$filename_daily = eefpsstats_get_filename(
		__( 'Daily Views', 'psstats' )
		. '-' . $selected_year . '-' . eefpsstats_get_post_title_from_url( $selected_post )
	);
	?>
	<section>
		<?php
		eefpsstats_echo_chart_container(
			'chart-daily',
			__( 'Daily Views', 'psstats' ) . ' ' . $selected_year,
			eefpsstats_get_post_title_from_url( $selected_post )
		);
		eefpsstats_echo_chart_container(
			'chart-monthly',
			__( 'Monthly Views', 'psstats' ) . ' ' . $selected_year,
			eefpsstats_get_post_title_from_url( $selected_post )
		);
		?>
		<script>
			eefpsstatsLineChart(
				'#chart-daily',
				[
					<?php
					foreach ( $months as $month ) {
						$days = eefpsstats_get_days( $month, $selected_year );
						foreach ( $days as $day ) {
							$views = eefpsstats_get_daily_views( $views_for_all_days, $selected_year, $month, $day );
							echo "['" . esc_js( $day ) . '. ' . esc_js( eefpsstats_get_month_name( $month ) ) . "'," . esc_js( $views ) . '],';
						}
					}
					?>
				],
				'daily'
			);
			eefpsstatsLineChart(
				'#chart-monthly',
				[
					<?php
					foreach ( $months as $month ) {
						$views = eefpsstats_get_monthly_views( $views_for_all_months, $selected_year, $month );
						echo "['" . esc_js( eefpsstats_get_month_name( $month ) ) . "'," . esc_js( $views ) . '],';
					}
					?>
				]
			);
		</script>
	</section>
	<section>
		<h3><?php echo esc_html( __( 'Daily Views', 'psstats' ) . ' ' . $selected_year ); ?>
			<?php
			echo esc_html( eefpsstats_get_post_type_name_and_title_from_url( $selected_post ) );
			eefpsstats_echo_export_button( $filename_daily );
			?>
		</h3>
		<table id="table-data" class="wp-list-table widefat striped">
			<thead>
				<tr>
					<td></td>
					<?php foreach ( $months as $month ) { ?>
					<th scope="col"><?php echo esc_html( eefpsstats_get_month_name( $month ) ); ?></th>
					<?php } ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $days as $day ) { ?>
				<tr>
					<th scope="row"><?php echo esc_html( $day ); ?></th>
					<?php foreach ( $months as $month ) { ?>
					<td class="right"><?php eefpsstats_echo_number( eefpsstats_get_daily_views( $views_for_all_days, $selected_year, $month, $day ) ); ?></td>
					<?php } ?>
				</tr>
				<?php } ?>
				<tr class="sum">
					<td><?php esc_html_e( 'Sum', 'psstats' ); ?></td>
					<?php foreach ( $months as $month ) { ?>
					<td class="right"><?php eefpsstats_echo_number( eefpsstats_get_monthly_views( $views_for_all_months, $selected_year, $month ) ); ?></td>
					<?php } ?>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Average', 'psstats' ); ?></td>
					<?php foreach ( $months as $month ) { ?>
					<td class="right"><?php eefpsstats_echo_number( eefpsstats_get_average_daily_views_of_month( $views_for_all_months, $selected_year, $month ) ); ?></td>
					<?php } ?>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Minimum', 'psstats' ); ?></td>
					<?php
					$daily_views = [];
					foreach ( $months as $month ) {
						$daily_views[ $month ] = eefpsstats_get_daily_views_of_month( $views_for_all_days, $selected_year, $month );
						?>
					<td class="right"><?php eefpsstats_echo_number( min( $daily_views[ $month ] ) ); ?></td>
					<?php } ?>
				</tr>
				<tr>
					<td><?php esc_html_e( 'Maximum', 'psstats' ); ?></td>
					<?php foreach ( $months as $month ) { ?>
					<td class="right"><?php eefpsstats_echo_number( max( $daily_views[ $month ] ) ); ?></td>
					<?php } ?>
				</tr>
			</tbody>
		</table>
	</section>
<?php } ?>
</div>
