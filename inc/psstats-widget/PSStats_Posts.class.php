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
		$counter = 0;
		$wpurl = parse_url(get_bloginfo('url'));
		$targets = self::get_all_targets(intVal($days));

		foreach ($targets as $entry) {
			$clear_url = str_replace($wpurl['host'],"",$entry['url']);
			
			// Add "frontpage" view counter, if post_type page/postpage and blog view is frontpage
			if ($clear_url == '/' && ($post_type == 'page' || $post_type == 'postpage') && 'page' != get_option('show_on_front')) {
				if (!isset($posts[0])) $posts[0] = 0;
				$posts[0] += $entry['count'];
				continue;
			}
			
			// Try to get ID from PSStats Count URL
			$id = url_to_postid(home_url( $clear_url ));
			
			// Get page by ID
			$page = get_page($id);
			
			// Find statistics for published "post&page" entries
			if ( (isset($page->post_type) && ($page->post_type == $post_type || $post_type == 'postpage')) && (isset($page->post_status) && $page->post_status == 'publish') ) {
                
				if (!empty($page->ID)) {
					// When category is select, then ignore other posts!
					if ($post_type == 'post' && $post_category > 0) {
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
			$wp_posts[] = new PSStats_Post(get_page($post_id), $views);
			
			// Manipulate Frontpage Post (ID==0) to fix values
			if ($post_id == 0) {
				$wp_posts[$post_id]->post_title = __('Frontpage', 'psstats');
				$wp_posts[$post_id]->post_permalink = get_home_url();
			}
			
		}
		

		
		return $wp_posts;
	}
	
	/**
	* Return array of post objects
	*
	* @since   1.3
	*/
	public static function get_post_list($post_type, $post_category, $amount, $days) {
		return new WP_Query(array('post__in' =>array_keys(self::get_post_ids($post_type, $post_category, $amount, $days))));
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
			$post_id = $post->ID;
		}

		$targets = self::get_all_targets(intval($days));
		$count = -1;

		foreach ($targets as $entry) {
			$clear_url = str_replace(get_bloginfo('wpurl'),"",$entry['url']);
			$id = url_to_postid(home_url( $clear_url ));

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
		$count = -1;

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

		return $a['prefix'] . " " . self::psstats_count($post->ID, $a['days']) . " " . $a['suffix'];
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

		return $a['prefix'] . " " . self::psstats_count_sum($a['days']) . " " . $a['suffix'];
	}

	/**
	* Return all targets from psstats and saved the values for 4 minutes.
	*
	* @since   1.1
	*/

	public static function get_all_targets($interval)
	{
		/* Look for cached values */
		if ($data = get_transient('psstats_targets_'.$interval)) {
			return $data;
		}

		if ($interval > 0) {
			$date = date("Y-m-d", strtotime('-' . $interval . ' days'));
		}

		global $wpdb;

		if ($interval > 0) {
			$data = $wpdb->get_results(
				"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->psstats` WHERE `created` >= '$date' GROUP BY `target` ORDER BY `count` DESC",
				ARRAY_A
			);
		} else {
			$data = $wpdb->get_results(
				"SELECT COUNT(`target`) as `count`, `target` as `url` FROM `$wpdb->psstats` GROUP BY `target` ORDER BY `count` DESC",
				ARRAY_A
			);
		}

		/* Save values for 4 minutes */
		set_transient(
			'psstats_targets_'.$interval, $data, 60 * 4 // = 4 minutes.
		);

		return $data;
	}

}