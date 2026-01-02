<?php
/**
 * The content page.
 *
 * @package extended-evaluation-for-psstats
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Get the data necessary for all tabs.
$post_types = eefpsstats_get_post_types();

// Get the selected tab.
if ( isset( $_GET['posttype'] ) && in_array( wp_unslash( $_GET['posttype'] ), $post_types, true ) ) {
	$selected_post_type = sanitize_text_field( wp_unslash( $_GET['posttype'] ) );
} else {
	$selected_post_type = 'popular'; // popular = show most popular content.
}

// Reset variables and Get post parameters for the dates if submitted..
$valid_start = false;
$valid_end = false;
$message = '';
$start = '';
$end = '';

// Check for at least one date set and valid wp_nonce.
if ( isset( $_POST['start'], $_POST['end'] ) && check_admin_referer( 'content' ) ) {
	$start = sanitize_text_field( wp_unslash( $_POST['start'] ) );
	$end = sanitize_text_field( wp_unslash( $_POST['end'] ) );
	if ( '' !== $start || '' !== $end ) {
		$valid_start = eefpsstats_is_valid_date_string( $start );
		$valid_end = eefpsstats_is_valid_date_string( $end );
		if ( ! $valid_start || ! $valid_end ) {
			// Error message if at least one date is not valid.
			$message = __( 'No valid date period set. Please enter a valid start and a valid end date!', 'psstats' );
		}
	}
}
?>
<div class="wrap eefpsstats">
	<h1><?php esc_html_e( 'PS-Stats – Extended Evaluation', 'psstats' ); ?>
			&rsaquo; <?php esc_html_e( 'Content', 'psstats' ); ?></h1>
	<?php if ( '' !== $message ) { ?>
	<div class="notice notice-error">
		<p><?php echo esc_html( $message ); ?></p>
	</div>
	<?php } ?>
	<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_html_e( 'Popular Content and Post Types', 'psstats' ); ?>">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=extended_evaluation_for_psstats_content' ) ); ?>"
			class="<?php eefpsstats_echo_tab_class( 'popular' === $selected_post_type ); ?>">
				<?php esc_html_e( 'Most Popular Content', 'psstats' ); ?></a>
	<?php foreach ( $post_types as $post_type ) { ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=extended_evaluation_for_psstats_content&posttype=' . $post_type ) ); ?>"
			class="<?php eefpsstats_echo_tab_class( $selected_post_type === $post_type ); ?>">
				<?php echo esc_html( get_post_type_object( $post_type )->labels->name ); ?></a>
	<?php } ?>
	</nav>
<?php
if ( 'popular' === $selected_post_type ) {
	// Show most popular content.
	if ( $valid_start && $valid_end ) {
		$views_per_post = eefpsstats_get_views_of_most_popular_posts( $start, $end );
	} else {
		$views_per_post = eefpsstats_get_views_of_most_popular_posts();
	}
	$views_per_post_for_diagram = array_slice( $views_per_post, 0, 24, true );

	$filename = eefpsstats_get_filename(
		__( 'Most Popular Content', 'psstats' )
		. eefpsstats_get_date_period_string( $start, $end, $valid_start && $valid_end )
	);
	?>
	<form method="post" action="">
		<?php wp_nonce_field( 'content' ); ?>
		<?php eefpsstats_echo_date_selection( $valid_start, $start, $valid_end, $end ); ?>
	</form>
	<?php if ( count( $views_per_post ) === 0 ) { ?>
	<p><?php esc_html_e( 'No data available.', 'psstats' ); ?></p>
	<?php } else { ?>
	<section>
		<?php
		$legend = [];
		foreach ( $views_per_post_for_diagram as $post ) {
			$legend[] = eefpsstats_get_post_title_from_url( $post['url'] );
		}

		eefpsstats_echo_chart_container(
			'chart-popular-content',
			__( 'Most Popular Content', 'psstats' ),
			eefpsstats_get_date_period_string( $start, $end, $valid_start && $valid_end, true ),
			$legend
		);
		?>
		<script>
			eefpsstatsColumnChart(
				'#chart-popular-content',
				[
					<?php
					foreach ( $views_per_post_for_diagram as $index => $post ) {
						echo "['"
							 . esc_js( $index + 1 . '. ' . eefpsstats_get_post_title_from_url( $post['url'] ) )
							 . "',"
							 . esc_js( $post['count'] )
							 . '],';
					}
					?>
				]
			);
		</script>	
	</section>
	<section>
		<h3><?php esc_html_e( 'Most Popular Content', 'psstats' ); ?>
			<?php
			echo esc_html( eefpsstats_get_date_period_string( $start, $end, $valid_start && $valid_end, true ) );
			eefpsstats_echo_export_button( $filename );
			?>
		</h3>
		<table id="table-data" class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Post/Page', 'psstats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'URL', 'psstats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Post Type', 'psstats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Views', 'psstats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Proportion', 'psstats' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				$total = 0;
				foreach ( $views_per_post as $post ) {
					$total += $post['count'];
				}
				foreach ( $views_per_post as $post ) {
					?>
				<tr>
					<td><a href="<?php echo esc_url( home_url( $post['url'] ) ); ?>" target="_blank">
							<?php echo esc_html( eefpsstats_get_post_title_from_url( $post['url'] ) ); ?>
						</a></td>
					<td><?php echo esc_url( $post['url'] ); ?></td>
					<td><?php echo esc_html( eefpsstats_get_post_type_name_from_url( $post['url'] ) ); ?></td>
					<td class="right"><?php eefpsstats_echo_number( $post['count'] ); ?></td>
					<td class="right"><?php eefpsstats_echo_percentage( $post['count'] / $total ); ?></td>
				</tr>
				<?php } ?>
			</tbody>
			<tfoot>
				<tr>
					<td><?php esc_html_e( 'Sum', 'psstats' ); ?></td>
					<td></td>
					<td></td>
					<td class="right"><?php eefpsstats_echo_number( $total ); ?></td>
					<td class="right"><?php eefpsstats_echo_percentage( 1 ); ?></td>
				</tr>
			</tfoot>
		</table>
	</section>
	<?php } ?>
	<?php
} else {
	$post_type = $selected_post_type;
	$filename = eefpsstats_get_filename(
		get_post_type_object( $post_type )->labels->name
		. eefpsstats_get_date_period_string( $start, $end, $valid_start && $valid_end )
	);
	?>
	<form method="post" action="">
		<?php wp_nonce_field( 'content' ); ?>
		<?php eefpsstats_echo_date_selection( $valid_start, $start, $valid_end, $end ); ?>
	</form>	
	<?php
	// Query for the post of the selected post type.
	$args = array(
		'post_type'      => $post_type,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);
	$query = new WP_Query( $args );

	if ( ! $query->have_posts() ) {
		?>
<p><?php esc_html_e( 'No data available.', 'psstats' ); ?></p>
	<?php } else { ?>
	<section>
		<h3><?php echo esc_html( get_post_type_object( $post_type )->labels->name ); ?>
			<?php
			echo esc_html( eefpsstats_get_date_period_string( $start, $end, $valid_start && $valid_end, true ) );
			eefpsstats_echo_export_button( $filename );
			?>
		</h3>
		<table id="table-data" class="wp-list-table widefat striped">
			<thead>
				<tr>
					<th scope="col"><?php echo esc_html( get_post_type_object( $post_type )->labels->singular_name ); ?></th>
					<th scope="col"><?php esc_html_e( 'URL', 'psstats' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Views', 'psstats' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			while ( $query->have_posts() ) :
				$query->the_post();
				if ( $valid_start && $valid_end ) {
					$views = eefpsstats_get_views_of_post(
						str_replace( home_url(), '', get_permalink() ),
						$start,
						$end
					);
				} else {
					$views = eefpsstats_get_views_of_post( str_replace( home_url(), '', get_permalink() ) );
				}
				?>
				<tr>
					<td><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></td>
					<td><?php echo esc_url( wp_make_link_relative( get_the_permalink() ) ); ?></td>
					<td class="right"><?php eefpsstats_echo_number( $views ); ?></td>
				</tr>
			<?php endwhile; ?>
			</tbody>
		</table>
	</section>
		<?php
	}

	// Restore global post data stomped by the_post().
	wp_reset_postdata();
}
?>
</div>
