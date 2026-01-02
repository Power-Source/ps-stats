<?php
/**
 * Plugin Name: PS Stats
 * Description: Kompaktes, benutzerfreundliches und datenschutzkonformes Statistik-Plugin für WordPress.
 * Text Domain: psstats
 * Author:      DerN3rd
 * Author URI:  https://github.com/Power-Source
 * Plugin URI:  https://cp-psource.github.io/ps-stats/
 * License:     GPLv3 or later
 * Version:     1.0.0
 * Domain Path: languages
 *
 * @package WordPress
 */

// PS Update Manager - Hinweis wenn nicht installiert
add_action( 'admin_notices', function() {
    // Prüfe ob Update Manager aktiv ist
    if ( ! function_exists( 'ps_register_product' ) && current_user_can( 'install_plugins' ) ) {
        $screen = get_current_screen();
        if ( $screen && in_array( $screen->id, array( 'plugins', 'plugins-network' ) ) ) {
            // Prüfe ob bereits installiert aber inaktiv
            $plugin_file = 'ps-update-manager/ps-update-manager.php';
            $all_plugins = get_plugins();
            $is_installed = isset( $all_plugins[ $plugin_file ] );
            
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo '<strong>PSOURCE MANAGER:</strong> ';
            
            if ( $is_installed ) {
                // Installiert aber inaktiv - Aktivierungs-Link
                $activate_url = wp_nonce_url(
                    admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $plugin_file ) ),
                    'activate-plugin_' . $plugin_file
                );
                echo sprintf(
                    __( 'Aktiviere den <a href="%s">PS Update Manager</a> für automatische Updates von GitHub.', 'psource-chat' ),
                    esc_url( $activate_url )
                );
            } else {
                // Nicht installiert - Download-Link
                echo sprintf(
                    __( 'Installiere den <a href="%s" target="_blank">PS Update Manager</a> für automatische Updates aller PSource Plugins & Themes.', 'psource-chat' ),
                    'https://github.com/Power-Source/ps-update-manager/releases/latest'
                );
            }
            
            echo '</p></div>';
        }
    }
});

/* Quit */
defined( 'ABSPATH' ) || exit;


/*  Constants */
define( 'CPSTATS_FILE', __FILE__ );
define( 'CPSTATS_DIR', dirname( __FILE__ ) );
define( 'CPSTATS_BASE', plugin_basename( __FILE__ ) );
define( 'CPSTATS_VERSION', '1.0.0' );


/* Hooks */
add_action(
	'plugins_loaded',
	array(
		'PSStats',
		'init',
	)
);
register_activation_hook(
	CPSTATS_FILE,
	array(
		'PSStats_Install',
		'init',
	)
);
register_deactivation_hook(
	CPSTATS_FILE,
	array(
		'PSStats_Deactivate',
		'init',
	)
);
register_uninstall_hook(
	CPSTATS_FILE,
	array(
		'PSStats_Uninstall',
		'init',
	)
);


/* Autoload */
spl_autoload_register( 'psstats_autoload' );

/**
 * Include classes via autoload.
 *
 * @param string $class Name of an class-file name, without file extension.
 */
function psstats_autoload( $class ) {

	$plugin_classes = array(
		'PSStats',
		'PSStats_Backend',
		'PSStats_Frontend',
		'PSStats_Dashboard',
		'PSStats_Install',
		'PSStats_Uninstall',
		'PSStats_Deactivate',
		'PSStats_Settings',
		'PSStats_Table',
		'PSStats_XMLRPC',
		'PSStats_Cron',
	);

	if ( in_array( $class, $plugin_classes, true ) ) {
		require_once sprintf(
			'%s/inc/class-%s.php',
			CPSTATS_DIR,
			strtolower( str_replace( '_', '-', $class ) )
		);
	}
}

// Load psstats-widget plugin
function load_psstats_widget() {
	require_once( plugin_dir_path( __FILE__ ) . 'inc/psstats-widget/psstats-widget.php' );
}
add_action( 'plugins_loaded', 'load_psstats_widget' );

// Load psstats-blacklist plugin
function load_psstats_blacklist() {
	require_once( plugin_dir_path( __FILE__ ) . 'inc/psstats-blacklist/psstats-blacklist.php' );
}
add_action( 'plugins_loaded', 'load_psstats_blacklist' );

// Load psstats-extended-evaluation plugin
function load_extended_evaluation_for_psstats() {
	require_once( plugin_dir_path( __FILE__ ) . 'inc/extended-evaluation-for-psstats/extended-evaluation-for-psstats.php' );
}

function load_psstats_textdomain() {
    load_plugin_textdomain( 'psstats', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'load_psstats_textdomain' );

add_action( 'plugins_loaded', 'load_extended_evaluation_for_psstats' );
