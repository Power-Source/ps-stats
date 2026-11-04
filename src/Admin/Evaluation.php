<?php

namespace PSStats\Admin;

defined( 'ABSPATH' ) || exit;

final class Evaluation {
	private const DIRECTORY = '/inc/extended-evaluation-for-psstats/';

	public static function assets(): void {
		$base = plugins_url( self::DIRECTORY, PS_STATS_FILE );
		$script_path = PS_STATS_DIR . self::DIRECTORY . 'js/functions.js';
		$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : PS_STATS_VERSION;
		wp_enqueue_style( 'ps-stats-chartist', $base . 'lib/chartist.min.css', array(), PS_STATS_VERSION );
		wp_enqueue_style( 'ps-stats-tooltip', $base . 'lib/chartist-plugin-tooltip.min.css', array( 'ps-stats-chartist' ), PS_STATS_VERSION );
		wp_enqueue_style( 'ps-stats-evaluation', $base . 'css/style.css', array( 'ps-stats-tooltip' ), PS_STATS_VERSION );
		wp_enqueue_script( 'ps-stats-chartist', $base . 'lib/chartist.min.js', array(), PS_STATS_VERSION );
		wp_enqueue_script( 'ps-stats-tooltip', $base . 'lib/chartist-plugin-tooltip.min.js', array( 'ps-stats-chartist', 'jquery' ), PS_STATS_VERSION );
		wp_enqueue_script( 'ps-stats-evaluation', $base . 'js/functions.js', array( 'ps-stats-tooltip' ), $script_version );
		wp_localize_script(
			'ps-stats-evaluation',
			'eefpsstats_translations',
			array(
				'view'  => __( 'Aufruf', 'ps-stats' ),
				'views' => __( 'Aufrufe', 'ps-stats' ),
			)
		);
	}

	public static function render( string $view ): void {
		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'see_psstats_evaluation' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Aktion.', 'ps-stats' ), 403 );
		}
		$directory = PS_STATS_DIR . self::DIRECTORY;
		require_once $directory . 'inc/queries.php';
		require_once $directory . 'inc/formatting.php';
		switch ( $view ) {
			case 'dashboard':
				require $directory . 'views/dashboard.php';
				break;
			case 'content':
				require $directory . 'views/content.php';
				break;
			case 'referrers':
				require $directory . 'views/referrers.php';
				break;
			default:
				wp_die( esc_html__( 'Unbekannte Statistikansicht.', 'ps-stats' ), 400 );
		}
	}

	public static function content(): void {
		self::render( 'content' );
	}

	public static function referrers(): void {
		self::render( 'referrers' );
	}
}
