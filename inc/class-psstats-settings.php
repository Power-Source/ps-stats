<?php
/**
 * PSStats: PSStats_Settings class
 *
 * This file contains the plugin's settings capabilities.
 *
 * @package   PSStats
 * @since     1.7
 */

// Quit if accessed directly..
defined( 'ABSPATH' ) || exit;

/**
 * Class PSStats_Settings
 *
 * @since 1.7
 */
class PSStats_Settings {

	/**
	 * Registers all options using the WP Settings API.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting( 'psstats', 'psstats', array( __CLASS__, 'sanitize_options' ) );

		// Global settings.
		add_settings_section(
			'psstats-global',
			__( 'Global settings', 'psstats' ),
			null,
			'psstats'
		);
		add_settings_field(
			'psstats-days',
			__( 'Period of data saving', 'psstats' ),
			array( __CLASS__, 'options_days' ),
			'psstats',
			'psstats-global',
			array( 'label_for' => 'psstats-days' )
		);
		add_settings_field(
			'psstats-snippet',
			__( 'Tracking method', 'psstats' ),
			array( __CLASS__, 'options_snippet' ),
			'psstats',
			'psstats-global',
			array( 'label_for' => 'psstats-snippet' )
		);

		// Dashboard widget settings.
		add_settings_section(
			'psstats-dashboard',
			__( 'Dashboard Widget', 'psstats' ),
			array( __CLASS__, 'header_dashboard' ),
			'psstats'
		);
		add_settings_field(
			'psstats-days_show',
			__( 'Period of data display in Dashboard', 'psstats' ),
			array( __CLASS__, 'options_days_show' ),
			'psstats',
			'psstats-dashboard',
			array( 'label_for' => 'psstats-days-show' )
		);
		add_settings_field(
			'psstats-limit',
			__( 'Number of entries in top lists', 'psstats' ),
			array( __CLASS__, 'options_limit' ),
			'psstats',
			'psstats-dashboard',
			array( 'label_for' => 'psstats-limit' )
		);
		add_settings_field(
			'psstats-today',
			__( 'Top lists only for today', 'psstats' ),
			array( __CLASS__, 'options_today' ),
			'psstats',
			'psstats-dashboard',
			array( 'label_for' => 'psstats-today' )
		);
		add_settings_field(
			'psstats-show-totals',
			__( 'Show totals', 'psstats' ),
			array( __CLASS__, 'options_show_totals' ),
			'psstats',
			'psstats-dashboard',
			array( 'label_for' => 'psstats-show-totals' )
		);

		// Exclusion settings.
		add_settings_section(
			'psstats-skip',
			__( 'Skip tracking for ...', 'psstats' ),
			array( __CLASS__, 'header_skip' ),
			'psstats'
		);
		add_settings_field(
			'psstats-skip-referrer',
			__( 'Disallowed referrers', 'psstats' ),
			array( __CLASS__, 'options_skip_blacklist' ),
			'psstats',
			'psstats-skip',
			array( 'label_for' => 'psstats-skip-referrer' )
		);
		add_settings_field(
			'psstats-skip-logged_in',
			__( 'Logged in users', 'psstats' ),
			array( __CLASS__, 'options_skip_logged_in' ),
			'psstats',
			'psstats-skip',
			array( 'label_for' => 'psstats-skip-logged_in' )
		);
	}

	/**
	 * Option for data collection period.
	 *
	 * @return void
	 */
	public static function options_days() {
		?>
		<input id="psstats-days" name="psstats[days]" type="number" min="1" value="<?php echo esc_attr( PSStats::$_options['days'] ); ?>">
		<?php esc_html_e( 'days', 'psstats' ); ?>
		(<?php esc_html_e( 'Default', 'psstats' ); ?>: 14)
		<?php
	}

	/**
	 * Option for tracking via JS.
	 *
	 * @return void
	 */
	public static function options_snippet() {
		?>
		<p>
			<?php self::show_snippet_option( PSStats::TRACKING_METHOD_DEFAULT, __( 'Default tracking', 'psstats' ) ); ?>
			<br>
			<?php self::show_snippet_option( PSStats::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK, __( 'JavaScript based tracking with nonce check', 'psstats' ) ); ?>
			<br>
			<?php self::show_snippet_option( PSStats::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK, __( 'JavaScript based tracking without nonce check', 'psstats' ) ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'JavaScript based tracking is strongly recommended if caching or AMP is in use.', 'psstats' ); ?>
			<?php esc_html_e( 'Disable the nonce check if the caching time is longer than the nonce time or you miss views due to 403 Forbidden errors.', 'psstats' ); ?>
		</p>
		<?php
	}

	/**
	 * Outputs the input radio for an option.
	 *
	 * @param int    $value the value for the input radio.
	 * @param string $label the label.
	 */
	private static function show_snippet_option( $value, $label ) {
		?>
			<label>
				<input name="psstats[snippet]" type="radio" value="<?php echo esc_html( $value ); ?>" <?php checked( PSStats::$_options['snippet'], $value ); ?>>
				<?php echo esc_html( $label ); ?>
			</label>
		<?php
	}

	/**
	 * Section header for "Dashboard Widget" section.
	 *
	 * @return void
	 */
	public static function header_dashboard() {
		?>
		<p>
			<?php esc_html_e( 'The following options affect the admin dashboard widget.', 'psstats' ); ?>
		</p>
		<?php
	}

	/**
	 * Option for data display period.
	 *
	 * @return void
	 */
	public static function options_days_show() {
		?>
		<input id="psstats-days-show" name="psstats[days_show]" type="number" min="1" value="<?php echo esc_attr( PSStats::$_options['days_show'] ); ?>">
		<?php esc_html_e( 'days', 'psstats' ); ?>
		(<?php esc_html_e( 'Default', 'psstats' ); ?>: 14)
		<?php
	}

	/**
	 * Option for number of entries in top lists.
	 *
	 * @return void
	 */
	public static function options_limit() {
		?>
		<input id="psstats-limit" name="psstats[limit]" type="number" min="1" max="100" value="<?php echo esc_attr( PSStats::$_options['limit'] ); ?>">
		(<?php esc_html_e( 'Default', 'psstats' ); ?>: 3)
		<?php
	}

	/**
	 * Option for number of entries in top lists.
	 *
	 * @return void
	 */
	public static function options_today() {
		?>
		<input  id="psstats-today" type="checkbox" name="psstats[today]" value="1" <?php checked( PSStats::$_options['today'], 1 ); ?>>
		(<?php esc_html_e( 'Default', 'psstats' ); ?>: <?php esc_html_e( 'No', 'psstats' ); ?>)
		<?php
	}

	/**
	 * Option for showing visit totals.
	 *
	 * @return void
	 */
	public static function options_show_totals() {
		?>
		<input  id="psstats-show-totals" type="checkbox" name="psstats[show_totals]" value="1" <?php checked( PSStats::$_options['show_totals'], 1 ); ?>>
		(<?php esc_html_e( 'Default', 'psstats' ); ?>: <?php esc_html_e( 'No', 'psstats' ); ?>)
		<?php
	}

	/**
	 * Section header for "Skip tracking for..." section.
	 *
	 * @return void
	 */
	public static function header_skip() {
		?>
		<p>
			<?php echo wp_kses( __( 'The following options define cases in which a view will <strong>not</strong> be tracked.', 'psstats' ), array( 'strong' => array() ) ); ?>
		</p>
		<?php
	}

	/**
	 * Option to skip tracking for disallowed referrers.
	 *
	 * @return void
	 */
	public static function options_skip_blacklist() {
		?>
		<input id="psstats-skip-referrer" type="checkbox" name="psstats[blacklist]" value="1"<?php checked( PSStats::$_options['blacklist'] ); ?>>
		(<?php esc_html_e( 'Default', 'psstats' ); ?>: <?php esc_html_e( 'No', 'psstats' ); ?>)
		<p class="description"><?php esc_html_e( 'Enabling this option excludes any views with referrers listed in the list of disallowed comment keys.', 'psstats' ); ?></p>
		<?php
	}

	/**
	 * Option to skip tracking for logged in uses.
	 *
	 * @return void
	 */
	public static function options_skip_logged_in() {
		?>
		<input id="psstats-skip-logged_in" type="checkbox" name="psstats[skip][logged_in]" value="1"<?php checked( PSStats::$_options['skip']['logged_in'] ); ?>>
		(<?php esc_html_e( 'Default', 'psstats' ); ?>: <?php esc_html_e( 'Yes', 'psstats' ); ?>)
		<p class="description"><?php esc_html_e( 'Enabling this option excludes any views of logged-in users from tracking.', 'psstats' ); ?></p>
		<?php
	}

	/**
	 * Action to be triggered after PSStats options have been saved.
	 * Delete transient data to refresh the dashboard widget and flushes Cachify cache, if the plugin is available and
	 * JS settings have changed.
	 *
	 * @since 1.7.1
	 *
	 * @param array $old_value The old options value.
	 * @param array $value     The updated options value.
	 *
	 * @return void
	 */
	public static function action_update_options( $old_value, $value ) {
		// Delete transient.
		delete_transient( 'psstats_data' );

		// Clear Cachify cache, if JS settings have changed.
		if ( $old_value['snippet'] !== $value['snippet'] && has_action( 'cachify_flush_cache' ) ) {
			do_action( 'cachify_flush_cache' );
		}
	}

	/**
	 * Validate and sanitize submitted options.
	 *
	 * @param array $options Original options.
	 *
	 * @return array Validated and sanitized options.
	 */
	public static function sanitize_options( $options ) {

		// Sanitize numeric values.
		$res = array();
		foreach ( array( 'days', 'days_show', 'limit' ) as $o ) {
			$res[ $o ] = PSStats::$_options[ $o ];
			if ( isset( $options[ $o ] ) && (int) $options[ $o ] > 0 ) {
				$res[ $o ] = (int) $options[ $o ];
			}
		}
		if ( $res['limit'] > 100 ) {
			$res['limit'] = 100;
		}

		if ( isset( $options['snippet'] ) ) {
			$method = (int) $options['snippet'];
			if ( in_array(
				$method,
				array(
					PSStats::TRACKING_METHOD_DEFAULT,
					PSStats::TRACKING_METHOD_JAVASCRIPT_WITH_NONCE_CHECK,
					PSStats::TRACKING_METHOD_JAVASCRIPT_WITHOUT_NONCE_CHECK,
				),
				true
			) ) {
				$res['snippet'] = $method;
			}
		}

		// Get checkbox values.
		foreach ( array( 'today', 'blacklist', 'show_totals' ) as $o ) {
			$res[ $o ] = isset( $options[ $o ] ) && 1 === (int) $options[ $o ] ? 1 : 0;
		}
		$res['skip']['logged_in'] = isset( $options['skip']['logged_in'] ) && 1 === (int) $options['skip']['logged_in'] ? 1 : 0;

		return $res;
	}

	/**
	 * Creates a menu entry in the settings menu.
	 *
	 * @return void
	 */
	public static function add_admin_menu() {
		add_options_page(
			__( 'PS-Stats', 'psstats' ),
			__( 'PS-Stats', 'psstats' ),
			'manage_options',
			'psstats-settings',
			array( __CLASS__, 'create_settings_page' )
		);
	}

	/**
	 * Creates the settings pages.
	 *
	 * @return void
	 */
	public static function create_settings_page() {
		?>

		<div class="wrap">
			<h1><?php esc_html_e( 'PSStats Settings', 'psstats' ); ?></h1>

			<form id="psstats-settings" method="post" action="options.php">
				<?php
				settings_fields( 'psstats' );
				do_settings_sections( 'psstats' );
				submit_button();
				?>
				<p class="alignright">
				<a href="<?php echo esc_url( __( 'https://github.com/cp-psource/ps-stats/wiki', 'psstats' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Dokumentation', 'psstats' ); ?></a>
				&bull; <a href="<?php echo esc_url( __( 'https://github.com/cp-psource/ps-stats/discussions', 'psstats' ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Support', 'psstats' ); ?></a>
				</p>

			</form>
		</div>

		<?php
	}

}
