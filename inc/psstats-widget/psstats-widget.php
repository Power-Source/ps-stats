<?php
/*
Plugin Name: PSStats Widget: Popular Posts
Version: 1.3.8
*/

require( 'PSStats_Post.class.php' );
require( 'PSStats_Posts.class.php' );

define('DEFAULT_AMOUNT', 5);
define('DEFAULT_POST_TYPE','post');
define('DEFAULT_INTERVAL', 0);
define('DEFAULT_SUFFIX', '');

class PSStatsWidget extends WP_Widget {

	/*
	* Register PSStatsWidget to Wordpress
	*/
	function __construct() {
		$widget_ops = array('classname' => 'psstats-widget', 'description' => __('Shows the most popular content. Based on PS-Stats Plugin.', 'psstats'));
		parent::__construct(
			'PSStatsWidget',
			__('PS-Stats Widget', 'plugin_name'),
			$widget_ops
		);
		
		add_shortcode( "psstats-count", "PSStats_Posts::psstats_count_shortcode");
		add_shortcode( "psstats-count-sum", "PSStats_Posts::psstats_count_sum_shortcode");
	}

	/*
	* Generating a from for settings
	*/
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array(
			'title' => '',
			'amount' => DEFAULT_AMOUNT,
			'post_type' => DEFAULT_POST_TYPE,
			'interval' => DEFAULT_INTERVAL,
			'show_visits' => 0,
			'list_style_type' => "ol",
			'suffix' => __('%VIEWS% views', 'psstats'),
			'post_category' => 0) );

    	$title = $instance['title'];
		$amount = $instance['amount'];
		$post_type = $instance['post_type'];
		$interval = $instance['interval'];
		$show_visits = $instance['show_visits'];
		$suffix = $instance['suffix'];
		$post_category = $instance['post_category'];
?>
        <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Widget title:', 'psstats'); ?>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
          </label>
        </p>
        <p class="post_select">
        <label for="<?php echo $this->get_field_id('post_type'); ?>"><?php _e( 'Post type:', 'psstats'); ?>
        <select class="widefat" id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>">
		  <option value="postpage"><?php _e( 'posts + pages', 'psstats'); ?></option>
          <?php
                $post_types = get_post_types( array('public'=>true, 'show_ui'=>true), 'objects' );
                foreach ( $post_types as $type ) {
                    echo '<option value="'. esc_attr($type->name) . '" '. selected( $post_type, $type->name, false ) .'>' . esc_attr($type->labels->name) . '</option>';
                }
          ?>
        </select>
        </p>
		<p class="category_select" style="display:none;">
		<label for="<?php echo $this->get_field_id('post_category'); ?>"><?php _e( 'Post category:', 'psstats'); ?>
      <select class="widefat" id="<?php echo $this->get_field_id('post_category'); ?>" name="<?php echo $this->get_field_name('post_category'); ?>">
			<option value="0">Alle Kategorien</option>
		  <?php
				$post_categories = get_categories( array('hide_empty'=>0 ),'objects' );
                foreach ( $post_categories as $type ) {
                    echo '<option value="'. esc_attr($type->cat_ID) . '" '. selected( $post_category, $type->cat_ID, false ) .'>' . esc_attr($type->name) . '</option>';
                }
          ?>
        </select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('interval'); ?>"><?php _e( 'Last ', 'psstats'); ?>
				<input id="<?php echo $this->get_field_id('interval'); ?>" name="<?php echo $this->get_field_name('interval'); ?>" type="text" size="3" value="<?php echo esc_attr($interval); ?>" />
			</label><?php _e( ' days. (If enough stats exists)', 'psstats'); ?>
			<br /><small><?php _e( '0 days = show all items', 'psstats'); ?></small>
		</p>
        <p>
          <label for="<?php echo $this->get_field_id('amount'); ?>"><?php _e( 'Amounts:', 'psstats'); ?>
            <input id="<?php echo $this->get_field_id('amount'); ?>" name="<?php echo $this->get_field_name('amount'); ?>" type="text" size="3" value="<?php echo esc_attr($amount); ?>" />
          </label>
		</p>
		<p>
          <label for="<?php echo $this->get_field_id('show_visits'); ?>">
            <input class="checkbox" type="checkbox" id="<?php echo $this->get_field_id('show_visits'); ?>" name="<?php echo $this->get_field_name('show_visits'); ?>" value="1" <?php checked($show_visits,1); ?>>
            <?php _e( 'Show view counter?', 'psstats'); ?></label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('suffix'); ?>"><?php _e( 'Custom text:', 'psstats'); ?>
            <input id="<?php echo $this->get_field_id('suffix'); ?>" class="widefat" name="<?php echo $this->get_field_name('suffix'); ?>" type="text" value="<?php echo esc_attr($suffix); ?>" />
			</label>
            <small><?php _e( '%VIEWS% = amount of views', 'psstats'); ?></small>
        </p>
<?php
	}

	/*
	* Override old instance with new instance.
	*/
	function update($new_instance, $old_instance) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['post_type'] = ( ! empty( $new_instance['post_type'] ) ) ? $new_instance['post_type'] : DEFAULT_POST_TYPE;
		if(! empty( $new_instance['interval'] ) && $new_instance['interval'] != $old_instance['interval']) {
			delete_transient('psstats_targets_'.$old_instance['interval']);
		}
		$instance['interval'] = ( ! empty( $new_instance['interval'] ) ) ? $new_instance['interval'] : DEFAULT_INTERVAL;
		$instance['amount'] = ( ! empty( $new_instance['amount'] ) ) ? sanitize_text_field( $new_instance['amount'] ) : DEFAULT_AMOUNT;
		$instance['show_visits'] = ( ! empty( $new_instance['show_visits'] ) ) ? $new_instance['show_visits'] : 0;
		$instance['suffix'] = ( ! empty( $new_instance['suffix'] ) ) ? sanitize_text_field( $new_instance['suffix'] ) : DEFAULT_SUFFIX;
		$instance['post_category'] = ( ! empty( $new_instance['post_category'] ) ) ? sanitize_text_field( $new_instance['post_category'] ) : 0;
		return $instance;
	}
	
	
	/*
	* Post Layout
	*/
	function psstats_widget_template($posts) {
	?>
			<?php if ( empty($posts) ): ?>
			<p><?php __( 'There are no posts yet.', 'psstats' ) ?></p>
			<?php else: ?>

			<ol class="psstats-widget-list">
				<?php foreach ($posts as $post): ?>
				<li class="psstats-widget-element">
					<?php echo do_action( "psstats_widget_before_link", $post); ?>
					<a class='psstats-widget-link' title='<?php echo $post->post_title ?>' href='<?php echo $post->post_permalink ?>'><?php echo $post->post_title ?></a> <?php if (isset($post->post_suffix)) echo "<span>". $post->post_suffix ."</span>" ?>
					<?php echo do_action( "psstats_widget_after_link", $post); ?>
				</li>
				<?php endforeach; ?>
			</ol>

			<?php endif; ?>
	<?php
	}

	/*
	* Print the widget
	*/
	function widget($args, $instance) {
		extract($args, EXTR_SKIP);

		echo $before_widget;

		$title = empty($instance['title']) ? '' : apply_filters('widget_title', $instance['title']);
		$amount = empty($instance['amount']) ? DEFAULT_AMOUNT : $instance['amount'];
		$post_type = empty($instance['post_type']) ? DEFAULT_POST_TYPE : $instance['post_type'];
		$interval = empty($instance['interval']) ? DEFAULT_INTERVAL : $instance['interval'];
		$show_visits = empty($instance['show_visits']) ? 0 : 1;
		$suffix_text =  empty($instance['suffix']) ? DEFAULT_SUFFIX : $instance['suffix'];
		$post_category = empty($instance['post_category']) ? 0 : $instance['post_category'];

		if (!empty($title)) echo $before_title . $title . $after_title;
		
		$posts = PSStats_Posts::get_posts($post_type, $post_category, $amount, $interval);
		
		// Replace custom suffix text with views and add to WP_Post object
		if ($show_visits) {
			foreach($posts as $post) {
				$post->post_suffix = str_replace(__("%VIEWS%","psstats-widget"), intval($post->post_views), $suffix_text);
			}
		}
		
		$this->psstats_widget_template($posts);
		
		echo $after_widget;
	}

	/*
	* Return the psstats widget class for the hook
	* @since 1.1.8
	*/

	function psstats_widget_class_callback( $className ) {
		$widgetClass = $className;
	}

}

/*
* Print error message in admin interface
*/
function showErrorMessages() {
	$html = '<div class="error"><p>';
	$html .= __( 'Please install <a target="_blank" href="http://wordpress.org/plugins/psstats/">PSStats</a> plugin first.', 'psstats');
	$html .= '</p></div>';
	echo $html;
}

/*
* Check if PSStats is acitivated
*/
function requires_psstats_plugin() {
    $plugin_bcd_plugin = 'ps-stats/ps-stats.php';
    $plugin = plugin_basename( __FILE__ );
    $plugin_data = get_plugin_data( __FILE__, false );

	if ( !is_plugin_active( $plugin_bcd_plugin) ) {
        deactivate_plugins ( $plugin );
		add_action('admin_notices', 'showErrorMessages');
    }
}

/**
 * Register PSStats-Widget
 *
 * @since 1.1.7
 */
function register_psstats_widget() {
	register_widget( 'PSStatsWidget' );
}
add_action( 'widgets_init', 'register_psstats_widget' );
add_action( 'admin_init', 'requires_psstats_plugin' );

/*
 * Get psstats count for post id in themes or shortcode
 * @since 1.1.6
 * @deprecated 1.3.3
*/
function psstats_count($post_id, $days = 0) {
	echo PSStats_Posts::psstats_count($post_id, $days);
}

/*
 * Echo psstats count for post id in themes or shortcode
 * @since 1.1.9
 */
function get_psstats_count($post_id, $days = 0) {
	return PSStats_Posts::psstats_count($post_id, $days);
}

/*
 * Return sum of all visits for the site
 * @since 1.2
 */
function get_psstats_count_sum($days = 0) {
	return PSStats_Posts::psstats_count_sum($days);
}

/*
 * Echo sum of all visits for the site
 * @since 1.2
 * @deprecated 1.3.3
 */
function psstats_count_sum($days = 0) {
	echo PSStats_Posts::psstats_count_sum($days);
}

/*
 * Return all posts for type as array
 * @since 1.3
 */
function psstats_popular_posts($amount = 5, $days = 0, $post_type = 'post', $post_category = 0) {
	return PSStats_Posts::get_post_list($post_type, $post_category, $amount, $days);
}
