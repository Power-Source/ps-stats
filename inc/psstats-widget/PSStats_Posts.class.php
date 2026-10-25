<?php

/**
 * Main class return the posts
 * 
 * @since 1.0
 */
class PSStats_Posts {
	
	/**
	* Return the content
	*
	* @since   1.0
	* @change  1.3.1
	*/
	public static function get_post_ids($post_type, $post_category, $amount, $days) {
		$posts = array();
		$targets = self::get_all_targets(intVal($days));
		$amount = max( 1, absint( $amount ) );

		foreach ($targets as $entry) {
			$clear_url = $entry['url'];
			if ( null === \PSStats\Privacy::route( $clear_url ) ) {
				continue;
			}
			
			// Add "frontpage" view counter, if post_type page/postpage and blog view is frontpage
			if ($clear_url === \PSStats\Normalizer::target_path( home_url( '/' ) ) && ($post_type == 'page' || $post_type == 'postpage') && 'page' != get_option('show_on_front')) {
				if (!isset($posts[0])) $posts[0] = 0;
				$posts[0] += $entry['count'];
				if ( count( $posts ) >= $amount ) break;
				continue;
			}
			
			// Try to get ID from PSStats Count URL
			$id = url_to_postid( \PSStats\Normalizer::local_url( $clear_url ) );
			
			// Get page by ID
			$page = get_page($id);
			
			// Find statistics for published "post&page" entries
			if ( (isset($page->post_type) && ($page->post_type == $post_type || ( $post_type == 'postpage' && in_array( $page->post_type, array( 'post', 'page' ), true ) ))) && (isset($page->post_status) && $page->post_status == 'publish') ) {
                
				if (!empty($page->ID)) {
					// When category is select, then ignore other posts!
					if ($post_type == 'post' && $post_category > 0) {
						$termIDArray = array();
						$categories = get_the_category($page->ID);
						foreach($categories as $category) $termIDArray[] = $category->term_id;
						if (!in_array($post_category,$termIDArray)) continue;
					}
					
					if (!isset($posts[$page->ID])) $posts[$page->ID] = 0;
					$posts[$page->ID] += $entry['count'];
				}
				
			}
			if (sizeof($posts) >= $amount) break;
		}
		
		return $posts;
	}
	
	/**
	* Return array of post objects
	*
	* @since   1.3
	*/
	public static function get_posts($post_type, $post_category, $amount, $days) {
		$posts = self::get_post_ids($post_type, $post_category, $amount, $days);
		$wp_posts = array();
		
		foreach ($posts as $post_id=>$views) {
			$item = new PSStats_Post( $post_id ? get_post( $post_id ) : new WP_Post( (object) array( 'ID' => 0 ) ), $views );
			
			// Manipulate Frontpage Post (ID==0) to fix values
			if ($post_id == 0) {
				$item->post_title = __('Frontpage', 'psstats');
				$item->post_permalink = get_home_url();
			}
			$wp_posts[] = $item;
			
		}
		

		
		return $wp_posts;
	}
	
	/**
	* Return array of post objects
	*
	* @since   1.3
	*/
	public static function get_post_list($post_type, $post_category, $amount, $days) {
		$ids = array_filter( array_keys( self::get_post_ids( $post_type, $post_category, $amount, $days ) ) );
		return new WP_Query( array( 'post__in' => $ids ?: array( 0 ), 'post_type' => 'postpage' === $post_type ? array( 'post', 'page' ) : $post_type, 'post_status' => 'publish', 'orderby' => 'post__in', 'posts_per_page' => max( 1, absint( $amount ) ) ) );
	}

	/**
	* Sorted by views
	*
	* @since   1.0
	*/

	private static function visitSort($a, $b) {
		if ($a==$b) return 0;
		return ($a['visits']>$b['visits'])?-1:1;
	}

	/**
	* Returns amount of view per id
	*
	* @since   1.1.4
	*/

	public static function get_psstats_count($post_id, $days) {
		if (empty($post_id)) {
			global $post;
			$post_id = $post instanceof WP_Post ? $post->ID : 0;
			if ( ! $post_id ) return 0;
		}

		$targets = self::get_all_targets(intval($days));
		$count = 0;
		if ( null === \PSStats\Privacy::route( get_permalink( $post_id ) ) ) {
			return 0;
		}

		foreach ($targets as $entry) {
			$clear_url = str_replace(get_bloginfo('wpurl'),"",$entry['url']);
			$id = url_to_postid( \PSStats\Normalizer::local_url( $clear_url ) );

			if ($id == $post_id) {
				$count = $entry['count'];
				break;
			}
		}

		return $count;
	}

	/**
	* Returns all views
	*
	* @since   1.2
	*/

	public static function psstats_count_sum($days) {
		$wpurl = parse_url(get_bloginfo('wpurl'));
		$targets = self::get_all_targets(intval($days));
		$count = 0;

		foreach ($targets as $entry) {
			$count += $entry['count'];
		}

		return $count;
	}

	/**
	* Return amount of views per id
	*
	* @since   1.1.4
	* @change  1.1.6
	*/

	public static function psstats_count($post_id, $days) {
		$count = self::get_psstats_count($post_id, $days);
		return $count >= 0 ? $count : 0;
	}

	/**
	* The shorctode for call amount of views per id.
	*
	* @since   1.1.4
	* @change  1.3.1
	*/

	public static function psstats_count_shortcode( $atts ) {
		global $post;

		// Attr
		$a = shortcode_atts( array(
			'prefix' => '',
			'suffix' => __('views', 'psstats'),
			'days' => 0
		), $atts );

		return esc_html( $a['prefix'] ) . " " . self::psstats_count( $post instanceof WP_Post ? $post->ID : 0, $a['days'] ) . " " . esc_html( $a['suffix'] );
	}

	/**
	* The shortcode for call all amount of views.
	*
	* @since   1.2
	* @change  1.3.1
	*/

	public static function psstats_count_sum_shortcode( $atts ) {
		global $post;

		// Attr
		$a = shortcode_atts( array(
			'prefix' => '',
			'suffix' => __('views', 'psstats'),
			'days' => 0
		), $atts );

		return esc_html( $a['prefix'] ) . " " . self::psstats_count_sum($a['days']) . " " . esc_html( $a['suffix'] );
	}

	/**
	* Return all targets from psstats and saved the values for 4 minutes.
	*
	* @since   1.1
	*/

	public static function get_all_targets($interval)
	{
		$provider = \PSStats\Api\Provider::instance();
		$bounds = $provider->date_bounds();
		$today = current_time( 'Y-m-d' );
		$from = (int) $interval > 0 ? gmdate( 'Y-m-d', strtotime( $today . ' -' . max( 0, (int) $interval - 1 ) . ' days' ) ) : ( $bounds['oldest'] ?: $today );
		$to = $bounds['newest'] ?: $today;
		$rows = $from > $to ? array() : $provider->landing_page_stats( $from, $to );
		return array_map( static fn ( array $row ): array => array( 'url' => $row['path'], 'count' => $row['views'] ), $rows );
	}

}