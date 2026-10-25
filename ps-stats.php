<?php
/**
 * Plugin Name: PS Stats
 * Description: Datenschutzfreundliche, vollständig lokale Seitenaufruf-Statistik für ClassicPress.
 * Version: 2.0.0
 * Requires PHP: 8.0
 * Author: DerN3rd
 * Author URI: https://github.com/Power-Source
 * Plugin URI: https://github.com/Power-Source/ps-stats
 * License: GPLv3 or later
 * Text Domain: ps-stats
 * Domain Path: /languages
 *
 * @package PSStats
 */

defined( 'ABSPATH' ) || exit;

define( 'PS_STATS_VERSION', '2.0.0' );
define( 'PS_STATS_SCHEMA_VERSION', '2.0.0' );
define( 'PS_STATS_FILE', __FILE__ );
define( 'PS_STATS_DIR', __DIR__ );

require_once PS_STATS_DIR . '/src/Autoloader.php';

\PSStats\Autoloader::register();

register_activation_hook( PS_STATS_FILE, array( \PSStats\Lifecycle::class, 'activate' ) );
register_deactivation_hook( PS_STATS_FILE, array( \PSStats\Lifecycle::class, 'deactivate' ) );
register_uninstall_hook( PS_STATS_FILE, array( \PSStats\Lifecycle::class, 'uninstall' ) );

add_action( 'plugins_loaded', array( \PSStats\Plugin::class, 'boot' ) );

/**
 * Liefert die optionale, stabile PS-Stats-Provider-API.
 */
function ps_stats_provider(): \PSStats\Api\Provider {
	return \PSStats\Api\Provider::instance();
}
