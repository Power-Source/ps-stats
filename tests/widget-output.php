<?php
/**
 * Run with: wp --skip-plugins --skip-themes eval-file tests/widget-output.php
 */

defined( 'ABSPATH' ) || exit;
require_once dirname( __DIR__ ) . '/inc/psstats-widget/psstats-widget.php';

$post = (object) array(
	'post_title' => '<img src=x onerror=alert(1)>',
	'post_permalink' => 'javascript:alert(1)',
	'post_suffix' => '<script>alert(1)</script>',
);
$widget = new PSStatsWidget();

ob_start();
$widget->psstats_widget_template( array( $post ) );
$output = ob_get_clean();

if (
	! str_contains( $output, '&lt;img src=x onerror=alert(1)&gt;' )
	|| ! str_contains( $output, 'href=""' )
	|| str_contains( $output, '<script>' )
) {
	throw new RuntimeException( 'Popular widget output must escape stored titles, links, and suffixes.' );
}

echo "PASS: popular widget escapes stored titles, unsafe URLs and suffixes.\n";
