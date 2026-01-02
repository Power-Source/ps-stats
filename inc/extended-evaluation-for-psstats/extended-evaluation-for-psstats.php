<?php
/**
 * Plugin Name: PSStats – Extended Evaluation
 * Description: Extended evaluation for the compact, easy-to-use and privacy-compliant PSStats plugin.
 * Version: 2.6.3
 * 
 * @package extended-evaluation-for-psstats
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

define( 'EEFSTATFIFY_VERSION', '2.6.3' );

// Includes.
//require_once 'inc/queries.php';
//require_once 'inc/formatting.php';
require_once( 'inc/queries.php' );
require_once( 'inc/formatting.php' );

/**
 * Requires PSStats to be installed and activated during installation.
 */
function eefpsstats_activate() {
	if ( ! eefpsstats_is_psstats_active() ) {
		deactivate_plugins( __FILE__ );
		wp_die(
			esc_html(
				__(
					'PS-Stats – Extended Evaluation requires the plugin PS-Stats which has to be installed and activated! Please install and activate PS-Stats before activating this plugin!', 'psstats'
				)
			),
			esc_html(
				__(
					'Activation Error: PS-Stats – Extended Evaluation requires PS-Stats!', 'psstats'
				)
			),
			array(
				'response'  => 200,
				'back_link' => true,
			)
		);
	}
}

// Hook to run during plugin activation.
register_activation_hook( __FILE__, 'eefpsstats_activate' );

/**
 * Delete database entries created by the plugin on uninstall.
 */
function eefpsstats_uninstall() {
	// Nothing to do.
}

// Hook to run during plugin uninstall.
register_uninstall_hook( __FILE__, 'eefpsstats_uninstall' );

/**
 * Check for PSStats plugin being installed.
 * If it isn't, display message and deactivate this plugin.
 */
function eefpsstats_check_status() {
	if ( ! eefpsstats_is_psstats_active() ) {
		// Display warning in the admin area.
		echo '<div class="error"><p>'
			. esc_html(
				__(
					'PS-Stats – Extended Evaluation requires the plugin PS-Stats which has to be installed and activated! Please install and activate PS-Stats before activating this plugin!', 'psstats'
				)
			)
			 . '</p></div>';
		// Deactivate this plugin.
		deactivate_plugins( __FILE__ );
	}
}

// Add status check to the admin notices.
add_action( 'admin_notices', 'eefpsstats_check_status' );

/**
 * Test whether PSStats is active.
 *
 * @return boolean true if and only if PSStats is installed and active.
 */
function eefpsstats_is_psstats_active() {
	return is_plugin_active( 'ps-stats/ps-stats.php' );
}

/**
 * Register and load the style sheets and JavaScript libraries.
 */
function eefpsstats_register_and_load_assets() {
	if ( eefpsstats_current_user_can_see_evaluation() ) {
		eefpsstats_enqueue_style(
			'chartist',
			'/lib/chartist.min.css'
		);
		eefpsstats_enqueue_style(
			'chartist-plugin-tooltip',
			'lib/chartist-plugin-tooltip.min.css'
		);
		eefpsstats_enqueue_style(
			'eefpsstats',
			'/css/style.min.css'
		);

		eefpsstats_enqueue_script(
			'chartist',
			'/lib/chartist.min.js'
		);
		eefpsstats_enqueue_script(
			'chartist-plugin-tooltip',
			'lib/chartist-plugin-tooltip.min.js'
		);
		eefpsstats_enqueue_script(
			'eefpsstats_functions',
			'/js/functions.min.js',
			[ 'chartist', 'chartist-plugin-tooltip', 'jquery' ]
		);

		wp_localize_script(
			'eefpsstats_functions',
			'eefpsstats_translations',
			array(
				'view'  => strip_tags( esc_html__( 'View', 'psstats' ) ),
				'views' => strip_tags( esc_html__( 'Views', 'psstats' ) ),
			)
		);
	}
}

/**
 * Loads the CSS file.
 *
 * @param string $style_name the name of the style.
 * @param string $style_path the plugin-relative path of the CSS file.
 */
function eefpsstats_enqueue_style( $style_name, $style_path ) {
	wp_enqueue_style(
		$style_name,
		plugins_url(
			$style_path,
			__FILE__
		),
		[],
		EEFSTATFIFY_VERSION
	);
}

/**
 * Loads the JavaScript file.
 *
 * @param string $script_name the name of the script.
 * @param string $script_path the plugin-relative path of the JavaScript.
 * @param array  $dependencies the dependencies.
 */
function eefpsstats_enqueue_script( $script_name, $script_path, $dependencies = [] ) {
	wp_enqueue_script(
		$script_name,
		plugins_url(
			$script_path,
			__FILE__
		),
		$dependencies,
		EEFSTATFIFY_VERSION
	);
}

/**
 * Create an item and submenu items in the WordPress admin menu.
 */
function eefpsstats_add_menu() {
	$page_hook_suffixes   = [];
	$page_hook_suffixes[] = add_menu_page(
		__( 'PS-Stats – Extended Evaluation', 'psstats' ), // page title.
		'PS-Stats', // title in the menu.
		'see_psstats_evaluation',
		'extended_evaluation_for_psstats_dashboard',
		'eefpsstats_show_dashboard',
		'dashicons-chart-bar',
		50
	);
	$page_hook_suffixes[] = add_submenu_page(
		'extended_evaluation_for_psstats_dashboard',
		__( 'Content', 'psstats' )
		. ' &mdash; ' . __( 'PSStats – Extended Evaluation', 'psstats' ),
		__( 'Content', 'psstats' ),
		'see_psstats_evaluation',
		'extended_evaluation_for_psstats_content',
		'eefpsstats_show_content'
	);
	$page_hook_suffixes[] = add_submenu_page(
		'extended_evaluation_for_psstats_dashboard',
		__( 'Referrers', 'psstats' )
		. ' &mdash; ' . __( 'PSStats – Extended Evaluation', 'psstats' ),
		__( 'Referrers', 'psstats' ),
		'see_psstats_evaluation',
		'extended_evaluation_for_psstats_referrer',
		'eefpsstats_show_referrer'
	);

	// Load CSS and JavaScript on plugin pages.
	foreach ( $page_hook_suffixes as $page_hook_suffix ) {
		add_action( "admin_print_styles-{$page_hook_suffix}", 'eefpsstats_register_and_load_assets' );
	}
}

// Register the menu building function.
add_action( 'admin_menu', 'eefpsstats_add_menu' );

/**
 * Adds a custom capability for users who see the evaluation pages.
 */
function eefpsstats_add_capability() {
	// Only administrators can see the evaluation by default.
	$role = get_role( 'administrator' );
	if ( $role ) {
		$role->add_cap( 'see_psstats_evaluation' );
	}

	// Remove old role PSStats Analyst.
	$role = get_role( 'psstats_evaluator' );
	if ( $role ) {
		remove_role( 'psstats_evaluator' );
	}
}

// Adds the capability.
add_action( 'admin_init', 'eefpsstats_add_capability' );

/**
 * Checks whether the current user is in the admin area and has the capability to see the evaluation.
 *
 * @return true if and only if the current user is allowed to see plugin pages
 */
function eefpsstats_current_user_can_see_evaluation() {
	return is_admin() && current_user_can( 'see_psstats_evaluation' );
}

/**
 * Show the dashboard page.
 */
function eefpsstats_show_dashboard() {
	eefpsstats_load_view( __DIR__ . '/views/dashboard.php' );
}

/**
 * Show the most popular content statistics page.
 */
function eefpsstats_show_content() {
	eefpsstats_load_view( __DIR__ . '/views/content.php' );
}

/**
 * Show the referrer statistics page.
 */
function eefpsstats_show_referrer() {
	eefpsstats_load_view( __DIR__ . '/views/referrers.php' );
}

/**
 * Loads the view template.
 *
 * @param string $view_path the path to the view template.
 */
function eefpsstats_load_view( $view_path ) {
	if ( eefpsstats_current_user_can_see_evaluation() ) {
		load_template(
			wp_normalize_path( $view_path )
		);
	}
}
