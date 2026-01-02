<?php
/**
 * PSStats Filter: Settings View
 *
 * This file contains the dynamic HTML skeleton for the plugin's settings page.
 *
 * @package    PSStats_Blacklist
 * @subpackage Admin
 * @since      1.0.0
 */

// phpcs:disable WordPress.WhiteSpace.PrecisionAlignment.Found

// Quit.
defined( 'ABSPATH' ) || exit;

// Update plugin options.
if ( ! empty( $_POST['psstatsblacklist'] ) ) {
	// Verify nonce.
	check_admin_referer( 'psstats-blacklist-settings' );

	// Check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		die( esc_html__( 'Are you sure you want to do this?', 'psstats' ) );
	}

	if ( ! empty( $_POST['cleanUp'] ) ) {
		// CleanUp DB.
		PSStatsBlacklist_Admin::cleanup_database();
	} else {
		// Extract referer array.
		if ( isset( $_POST['psstatsblacklist']['referer']['blacklist'] ) ) {
			$referer_str = sanitize_textarea_field( wp_unslash( $_POST['psstatsblacklist']['referer']['blacklist'] ) );
		}
		if ( empty( trim( $referer_str ) ) ) {
			$referer = array();
		} else {
			$referer = array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", $referer_str )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}

		// Extract target array.
		if ( isset( $_POST['psstatsblacklist']['target']['blacklist'] ) ) {
			$target_str = sanitize_textarea_field( wp_unslash( $_POST['psstatsblacklist']['target']['blacklist'] ) );
		}
		if ( empty( trim( $target_str ) ) ) {
			$target = array();
		} else {
			$target = array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", str_replace( '\\\\', '\\', $target_str ) )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}

		// Extract IP array.
		if ( isset( $_POST['psstatsblacklist']['ip']['blacklist'] ) ) {
			$ip_str = sanitize_textarea_field( wp_unslash( $_POST['psstatsblacklist']['ip']['blacklist'] ) );
		}
		if ( empty( trim( $ip_str ) ) ) {
			$ip = array();
		} else {
			$ip = array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", $ip_str )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}

		// Extract user agent array.
		if ( isset( $_POST['psstatsblacklist']['ua']['blacklist'] ) ) {
			$ua_string = sanitize_textarea_field( wp_unslash( $_POST['psstatsblacklist']['ua']['blacklist'] ) );
		}
		if ( empty( trim( $ua_string ) ) ) {
			$ua = array();
		} else {
			$ua = array_filter(
				array_map(
					function ( $a ) {
						return trim( $a );
					},
					explode( "\r\n", str_replace( '\\\\', '\\', $ua_string ) )
				),
				function ( $a ) {
					return ! empty( $a );
				}
			);
		}

		// Update options (data will be sanitized).
		$psstatsblacklist_update_result = PSStatsBlacklist_Admin::update_options(
			array(
				'referer' => array(
					'active'    => isset( $_POST['psstatsblacklist']['referer']['active'] )
						? (int) $_POST['psstatsblacklist']['referer']['active'] : 0,
					'cron'      => isset( $_POST['psstatsblacklist']['referer']['cron'] )
						? (int) $_POST['psstatsblacklist']['referer']['cron'] : 0,
					'regexp'    => isset( $_POST['psstatsblacklist']['referer']['regexp'] )
						? (int) $_POST['psstatsblacklist']['referer']['regexp'] : 0,
					'blacklist' => array_flip( $referer ),
				),
				'target'  => array(
					'active'    => isset( $_POST['psstatsblacklist']['target']['active'] )
						? (int) $_POST['psstatsblacklist']['target']['active'] : 0,
					'cron'      => isset( $_POST['psstatsblacklist']['target']['cron'] )
						? (int) $_POST['psstatsblacklist']['target']['cron'] : 0,
					'regexp'    => isset( $_POST['psstatsblacklist']['target']['regexp'] )
						? (int) $_POST['psstatsblacklist']['target']['regexp'] : 0,
					'blacklist' => array_flip( $target ),
				),
				'ip'      => array(
					'active'    => isset( $_POST['psstatsblacklist']['ip']['active'] )
						? (int) $_POST['psstatsblacklist']['ip']['active'] : 0,
					'blacklist' => $ip,
				),
				'ua'      => array(
					'active'    => isset( $_POST['psstatsblacklist']['ua']['active'] )
						? (int) $_POST['psstatsblacklist']['ua']['active'] : 0,
					'regexp'    => isset( $_POST['psstatsblacklist']['ua']['regexp'] )
						? (int) $_POST['psstatsblacklist']['ua']['regexp'] : 0,
					'blacklist' => array_flip( $ua ),
				),
				'version' => PSStatsBlacklist::VERSION_MAIN,
			)
		);

		// Generate messages.
		if ( false !== $psstatsblacklist_update_result ) {
			$psstatsblacklist_post_warning = array();
			if ( ! empty( $psstatsblacklist_update_result['referer']['diff'] ) ) {
				$psstatsblacklist_post_warning[] = __( 'Some URLs are invalid and have been sanitized.', 'psstats' );
			}
			if ( ! empty( $psstatsblacklist_update_result['referer']['invalid'] ) ) {
				$psstatsblacklist_post_warning[] = __( 'Some regular expressions are invalid:', 'psstats' ) . '<br>' . implode( '<br>', $psstatsblacklist_update_result['referer']['invalid'] );
			}
			if ( ! empty( $psstatsblacklist_update_result['ip']['diff'] ) ) {
				// translators: List of invalid IP addresses (comma separated).
				$psstatsblacklist_post_warning[] = sprintf( __( 'Some IPs are invalid: %s', 'psstats' ), implode( ', ', $psstatsblacklist_update_result['ip']['diff'] ) );
			}
		} else {
			$psstatsblacklist_post_success = __( 'Settings updated successfully.', 'psstats' );
		}
	}
}

/*
 * Disable some code style rules that are impractical for textarea content:
 *
 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentBeforeOpen
 * phpcs:disable Squiz.PHP.EmbeddedPhp.ContentAfterEnd
 */
?>

<div class="wrap">
	<h1><?php esc_html_e( 'PSStats Filter', 'psstats' ); ?></h1>
	<?php
	if ( is_plugin_inactive( 'psstats/psstats.php' ) ) {
		print '<div class="notice notice-warning"><p>';
		esc_html_e( 'PSStats plugin is not active.', 'psstats' );
		print '</p></div>';
	}
	if ( isset( $psstatsblacklist_post_warning ) ) {
		foreach ( $psstatsblacklist_post_warning as $w ) {
			print '<div class="notice notice-warning"><p>' .
				wp_kses( $w, array( 'br' => array() ) ) .
				'</p></div>';
		}
		print '<div class="notice notice-warning"><p>' . esc_html__( 'Settings have not been saved yet.', 'psstats' ) . '</p></div>';
	}
	if ( isset( $psstatsblacklist_post_success ) ) {
		print '<div class="notice notice-success"><p>' .
			esc_html( $psstatsblacklist_post_success ) .
			'</p></div>';
	}
	?>
	<form action="" method="post" id="psstats-blacklist-settings">
		<?php wp_nonce_field( 'psstats-blacklist-settings' ); ?>

		<h2><?php esc_html_e( 'Referer filter', 'psstats' ); ?></h2>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_active_referer">
						<?php esc_html_e( 'Activate live filter', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="psstatsblacklist[referer][active]"
						   id="psstats-blacklist_active_referer"
						   value="1" <?php checked( PSStatsBlacklist::$options['referer']['active'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_cron_referer">
						<?php esc_html_e( 'CronJob execution', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="psstatsblacklist[referer][cron]" id="psstats-blacklist_cron_referer"
						   value="1" <?php checked( PSStatsBlacklist::$options['referer']['cron'], 1 ); ?>>
					<p class="description"><?php esc_html_e( 'Periodically clean up database in background', 'psstats' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_referer_regexp"><?php esc_html_e( 'Matching method', 'psstats' ); ?></label>
				</th>
				<td>
					<select name="psstatsblacklist[referer][regexp]" id="psstats-blacklist_referer_regexp">
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_NORMAL ); ?>" <?php selected( PSStatsBlacklist::$options['referer']['regexp'], PSStatsBlacklist::MODE_NORMAL ); ?>>
							<?php esc_html_e( 'Domain', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_KEYWORD ); ?>" <?php selected( PSStatsBlacklist::$options['referer']['regexp'], PSStatsBlacklist::MODE_KEYWORD ); ?>>
							<?php esc_html_e( 'Keyword', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_REGEX ); ?>" <?php selected( PSStatsBlacklist::$options['referer']['regexp'], PSStatsBlacklist::MODE_REGEX ); ?>>
							<?php esc_html_e( 'RegEx case-sensitive', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_REGEX_CI ); ?>" <?php selected( PSStatsBlacklist::$options['referer']['regexp'], PSStatsBlacklist::MODE_REGEX_CI ); ?>>
							<?php esc_html_e( 'RegEx case-insensitive', 'psstats' ); ?>
						</option>
					</select>

					<p class="description">
						<?php esc_html_e( 'Domain', 'psstats' ); ?> - <?php esc_html_e( 'Match given domain including subdomains', 'psstats' ); ?>
						<br>
						<?php esc_html_e( 'Keyword', 'psstats' ); ?> - <?php esc_html_e( 'Match every referer that contains one of the keywords', 'psstats' ); ?>
						<br>
						<?php esc_html_e( 'RegEx', 'psstats' ); ?> - <?php esc_html_e( 'Match referer by regular expression', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_referer"><?php esc_html_e( 'Referer filter', 'psstats' ); ?></label>
				</th>
				<td>
					<textarea cols="40" rows="5" name="psstatsblacklist[referer][blacklist]" id="psstats-blacklist_referer"><?php
					if ( empty( $psstatsblacklist_update_result['referer'] ) ) {
						print esc_html( implode( "\r\n", array_keys( PSStatsBlacklist::$options['referer']['blacklist'] ) ) );
					} else {
						print esc_html( implode( "\r\n", array_keys( $psstatsblacklist_update_result['referer']['sanitized'] ) ) );
					}
					?></textarea>
					<p class="description">
						<?php esc_html_e( 'Add one domain (without subdomains) each line, e.g. example.com', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'Target filter', 'psstats' ); ?></h2>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_active_target">
						<?php esc_html_e( 'Activate live filter', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="psstatsblacklist[target][active]"
						   id="psstats-blacklist_active_target"
						   value="1" <?php checked( PSStatsBlacklist::$options['target']['active'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_cron_target">
						<?php esc_html_e( 'CronJob execution', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="psstatsblacklist[target][cron]" id="psstats-blacklist_cron_target"
						   value="1" <?php checked( PSStatsBlacklist::$options['target']['cron'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Clean database periodically in background', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_target_regexp">
						<?php esc_html_e( 'Matching method', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<select name="psstatsblacklist[target][regexp]" id="psstats-blacklist_target_regexp">
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_NORMAL ); ?>" <?php selected( PSStatsBlacklist::$options['target']['regexp'], PSStatsBlacklist::MODE_NORMAL ); ?>>
							<?php esc_html_e( 'Exact', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_REGEX ); ?>" <?php selected( PSStatsBlacklist::$options['target']['regexp'], PSStatsBlacklist::MODE_REGEX ); ?>>
							<?php esc_html_e( 'RegEx case-sensitive', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_REGEX_CI ); ?>" <?php selected( PSStatsBlacklist::$options['target']['regexp'], PSStatsBlacklist::MODE_REGEX_CI ); ?>>
							<?php esc_html_e( 'RegEx case-insensitive', 'psstats' ); ?>
						</option>
					</select>

					<p class="description">
						<?php esc_html_e( 'Exact', 'psstats' ); ?> - <?php esc_html_e( 'Match only given targets', 'psstats' ); ?>
						<br>
						<?php esc_html_e( 'RegEx', 'psstats' ); ?> - <?php esc_html_e( 'Match target by regular expression', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_target">
						<?php esc_html_e( 'Target filter', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<textarea cols="40" rows="5" name="psstatsblacklist[target][blacklist]" id="psstats-blacklist_target"><?php
					if ( empty( $psstatsblacklist_update_result['target'] ) ) {
						print esc_html( implode( "\r\n", array_keys( PSStatsBlacklist::$options['target']['blacklist'] ) ) );
					} else {
						print esc_html( implode( "\r\n", array_keys( $psstatsblacklist_update_result['target']['sanitized'] ) ) );
					}
					?></textarea>

					<p class="description">
						<?php esc_html_e( 'Add one target URL each line, e.g.', 'psstats' ); ?> /, /test/page/, /?page_id=123
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'IP filter', 'psstats' ); ?></h2>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_active_ip">
						<?php esc_html_e( 'Activate live filter', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="psstatsblacklist[ip][active]" id="psstats-blacklist_active_ip"
						   value="1" <?php checked( PSStatsBlacklist::$options['ip']['active'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'psstats' ); ?>
						<br>
						<?php esc_html_e( 'Cron execution is not possible for IP filter, because IP addresses are not stored.', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_ip"><?php esc_html_e( 'IP filter', 'psstats' ); ?></label>:
				</th>
				<td>
					<textarea cols="40" rows="5" name="psstatsblacklist[ip][blacklist]" id="psstats-blacklist_ip"><?php
					if ( empty( $psstatsblacklist_update_result['ip'] ) ) {
						print esc_html( implode( "\r\n", PSStatsBlacklist::$options['ip']['blacklist'] ) );
					} else {
						print esc_html( implode( "\r\n", $psstatsblacklist_update_result['ip']['sanitized'] ) );
					}
					?></textarea>

					<p class="description">
						<?php esc_html_e( 'Add one IP address or range per line, e.g.', 'psstats' ); ?>
						127.0.0.1, 192.168.123.0/24, 2001:db8:a0b:12f0::1/64
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<h2><?php esc_html_e( 'User agent filter', 'psstats' ); ?></h2>

		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_active_ua">
						<?php esc_html_e( 'Activate live filter', 'psstats' ); ?>
					</label>
				</th>
				<td>
					<input type="checkbox" name="psstatsblacklist[ua][active]" id="psstats-blacklist_active_ua"
						   value="1" <?php checked( PSStatsBlacklist::$options['ua']['active'], 1 ); ?>>
					<p class="description">
						<?php esc_html_e( 'Filter at time of tracking, before anything is stored', 'psstats' ); ?>
						<br>
						<?php esc_html_e( 'Cron execution is not possible for user agent filter, because the user agent is stored.', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_ua_regexp"><?php esc_html_e( 'Matching method', 'psstats' ); ?></label>
				</th>
				<td>
					<select name="psstatsblacklist[ua][regexp]" id="psstats-blacklist_ua_regexp">
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_NORMAL ); ?>" <?php selected( PSStatsBlacklist::$options['ua']['regexp'], PSStatsBlacklist::MODE_NORMAL ); ?>>
							<?php esc_html_e( 'Exact', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_KEYWORD ); ?>" <?php selected( PSStatsBlacklist::$options['ua']['regexp'], PSStatsBlacklist::MODE_KEYWORD ); ?>>
							<?php esc_html_e( 'Keyword', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_REGEX ); ?>" <?php selected( PSStatsBlacklist::$options['ua']['regexp'], PSStatsBlacklist::MODE_REGEX ); ?>>
							<?php esc_html_e( 'RegEx case-sensitive', 'psstats' ); ?>
						</option>
						<option value="<?php print esc_attr( PSStatsBlacklist::MODE_REGEX_CI ); ?>" <?php selected( PSStatsBlacklist::$options['ua']['regexp'], PSStatsBlacklist::MODE_REGEX_CI ); ?>>
							<?php esc_html_e( 'RegEx case-insensitive', 'psstats' ); ?>
						</option>
					</select>

					<p class="description">
						<?php esc_html_e( 'Exact', 'psstats' ); ?> - <?php esc_html_e( 'Match only given user agents', 'psstats' ); ?>
						<br>
						<?php esc_html_e( 'Keyword', 'psstats' ); ?> - <?php esc_html_e( 'Match every referer that contains one of the keywords', 'psstats' ); ?>
						<br>
						<?php esc_html_e( 'RegEx', 'psstats' ); ?> - <?php esc_html_e( 'Match user agent by regular expression', 'psstats' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="psstats-blacklist_ua"><?php esc_html_e( 'User agent filter', 'psstats' ); ?></label>:
				</th>
				<td>
					<textarea cols="40" rows="5" name="psstatsblacklist[ua][blacklist]" id="psstats-blacklist_ua"><?php
					if ( empty( $psstatsblacklist_update_result['ua'] ) ) {
						print esc_html( implode( "\r\n", array_keys( PSStatsBlacklist::$options['ua']['blacklist'] ) ) );
					} else {
						print esc_html( implode( "\r\n", array_keys( $psstatsblacklist_update_result['ua']['sanitized'] ) ) );
					}
					?></textarea>

					<p class="description">
						<?php esc_html_e( 'Add one user agent string per line, e.g.', 'psstats' ); ?>
						MyBot/1.23
					</p>
				</td>
			</tr>
			</tbody>
		</table>

		<p class="submit">
			<input class="button-primary" type="submit" name="submit" value="<?php esc_html_e( 'Save Changes', 'psstats' ); ?>">
			<hr>
			<input class="button-secondary" type="submit" name="cleanUp"
				   value="<?php esc_html_e( 'CleanUp Database', 'psstats' ); ?>"
				   onclick="return confirm('Do you really want to apply filters to database? This cannot be undone.');">
			<br>
			<p class="description">
				<?php esc_html_e( 'Applies referer and target filter (even if disabled) to data stored in database.', 'psstats' ); ?>
				<em><?php esc_html_e( 'This cannot be undone!', 'psstats' ); ?></em>
			</p>
		</p>
	</form>
</div>
