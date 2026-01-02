<?php
/**
 * PSStats: PSStats_Backend class
 *
 * This file contains the derived class for the plugin's backend features.
 *
 * @package   PSStats
 * @since     1.4.0
 */

// Quit if accessed outside WP context.
defined( 'ABSPATH' ) || exit;

/**
 * PSStats_Backend
 *
 * @since 1.4.0
 */
class PSStats_Backend {

	/**
	 * Add plugin meta links
	 *
	 * @since    0.1.0
	 * @version  1.4.0
	 *
	 * @param   array  $input Registered links.
	 * @param   string $file  Current plugin file.
	 *
	 * @return  array           Merged links
	 */
	public static function add_meta_link( $input, $file ) {

		// Other plugins?
		if ( CPSTATS_BASE !== $file ) {
			return $input;
		}

		return array_merge(
			$input,
			array(
				'<a href="https://github.com/cp-psource/ps-stats/wiki" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Dokumentation', 'psstats' ) . '</a>',
				'<a href="' . esc_url( __( 'https://github.com/cp-psource/ps-stats/discussions', 'psstats' ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'psstats' ) . '</a>',
			)
		);
	}

	/**
	 * Add plugin action links
	 *
	 * @since   0.1.0
	 * @version 1.4.0
	 *
	 * @param   array $input Registered links.
	 *
	 * @return  array           Merged links
	 */
	public static function add_action_link( $input ) {

		// Rights?
		if ( ! current_user_can( 'manage_options' ) ) {
			return $input;
		}

		// Merge.
		return array_merge(
			$input,
			array(
				sprintf(
					/* @lang  Disable language injection for Url query argument. */
					'<a href="%s">%s</a>',
					add_query_arg(
						array( 'page' => 'psstats-settings' ),
						admin_url( '/options-general.php' )
					),
					esc_html__( 'Settings', 'psstats' )
				),
			)
		);
	}
}
