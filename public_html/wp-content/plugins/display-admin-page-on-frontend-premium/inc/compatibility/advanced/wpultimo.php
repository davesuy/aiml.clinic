<?php
if (!class_exists('WPFA_WP_Ultimo')) {

	class WPFA_WP_Ultimo {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {

			
			if (!class_exists('WP_Ultimo')) {
				return;
			}
			
				add_action('wp_frontend_admin/quick_settings/after_save', array($this, 'save_meta_box'), 99, 2);
				add_action('wp_frontend_admin/quick_settings/after_fields', array($this, 'render_meta_box'));
				add_filter('wp_frontend_admin/is_user_allowed_to_view_page', array($this, 'is_user_allowed_to_view_page'), 10, 3);
				add_filter('wp_ultimo_redirect_url_after_signup', array($this, 'wpultimo_signup_redirect_to'), 10, 3);
				add_action('wp', array($this, 'maybe_redirect_to_permissions_page'));
				add_filter('redux/options/' . VG_Admin_To_Frontend::$textname . '/sections', array($this, 'add_global_options'));
				add_filter('wp_get_nav_menu_items', array($this, 'remove_pages_from_menu'), 10, 3);
				add_filter('vg_admin_to_frontend/potential_issues', array($this, 'add_potential_issues'));
				add_action('template_redirect', array($this, 'modify_site_id_for_previews_late'));
				if (!is_network_admin()) {
					add_filter('vg_admin_to_frontend/is_page_blacklisted', array($this, 'whitelist_wu_account_pages'));
				}
			
		}

		function whitelist_wu_account_pages($is_blacklisted) {
			$site = wu_get_current_site();
			if (is_object($site->site_owner) && $site->site_owner->ID === get_current_user_id()) {
				$nonce_key = 'wpfa_wu_after_signup' . $site->site_owner->ID;
				if (!empty($_GET['wpfa_wu_after_signup']) && wp_verify_nonce($_GET['wpfa_wu_after_signup'], $nonce_key)) {
					$is_blacklisted = false;
				}

				if (!empty($_GET['page']) && $_GET['page'] === 'wu-my-account') {
					$is_blacklisted = false;
				}
			}
			return $is_blacklisted;
		}

		function modify_site_id_for_previews_late() {
			if (!is_admin() && VG_Admin_To_Frontend_Obj()->is_master_user() && is_singular()) {
				add_filter('wp_frontend_admin/site_id_for_admin_content', array($this, 'modify_site_id_for_previews'));
			}
		}

		function modify_site_id_for_previews($site_id) {
			global $wpdb;
			if (empty($GLOBALS['wpfa_current_shortcode']) || !preg_match('/(wu-my-account|index\.php)/', $GLOBALS['wpfa_current_shortcode']['page_url'])) {
				return $site_id;
			}
			$first_blog_id_with_owner = (int) $wpdb->get_var($wpdb->prepare("SELECT site_id FROM {$wpdb->base_prefix}wu_site_owner WHERE user_id = %d LIMIT 1", get_current_user_id()));

			if (!$first_blog_id_with_owner) {
				$first_blog_id_with_owner = (int) $wpdb->get_var("SELECT site_id FROM {$wpdb->base_prefix}wu_site_owner LIMIT 1");
			}
			if ($first_blog_id_with_owner) {
				$site_id = $first_blog_id_with_owner;
			}
			return $site_id;
		}

		function add_potential_issues($issues) {

			$settings = get_network_option(null, 'wp-ultimo_settings');
			if (isset($settings['enable_sso']) && (int) VG_Admin_To_Frontend_Obj()->get_settings('global_dashboard_id')) {
				$issues['ultimo_disable_sso'] = sprintf(__('You need to disable the "Single Sign On" in WP Ultimo settings because it is not compatible with our global dashboards feature. The single sign on is not necessary. <a href="%s" target="_blank" class="button">Fix it</a>', VG_Admin_To_Frontend::$textname), esc_url(network_admin_url('admin.php?page=wp-ultimo&wu-tab=domain_mapping')));
			}
			return $issues;
		}

		function remove_pages_from_menu($items, $menu, $args) {

			$available_menu_items = array();

			foreach ($items as $item) {
				if ($item->type === 'post_type' && !$this->is_user_allowed_to_view_page(true, $item->object_id)) {
					continue;
				}

				$available_menu_items[] = $item;
			}

			return $available_menu_items;
		}

		function add_global_options($sections) {
			$first_tab_index = current(array_keys($sections));
			$sections[$first_tab_index]['fields'][] = array(
				'id' => 'wu_remove_disallowed_pages_from_menus',
				'type' => 'switch',
				'title' => __('WP Ultimo: Remove disallowed pages from menus?', VG_Admin_To_Frontend::$textname),
				'desc' => __('By default, we show all the dashboard pages in the menu and the disallowed pages show a message when they are opened, saying they are not allowed or will redirect to a custom URL defined in the setting "Wrong permissions url" (above). You can enable this option to automatically remove the pages from the menu when they are not allowed for the current plan.', VG_Admin_To_Frontend::$textname),
			);
			return $sections;
		}

		/**
		 * If we have defined a login page and the inline login form is not used
		 * We check if the current has our shortcode and redirect the page to the login page
		 * 
		 * @return null
		 */
		function maybe_redirect_to_permissions_page() {
			if (!is_user_logged_in() || !is_singular()) {
				return;
			}
			if (!VG_Admin_To_Frontend_Obj()->is_wpfa_page()) {
				return;
			}

			$allowed = $this->is_user_allowed_to_view_page(true);
			$url = VG_Admin_To_Frontend_Obj()->get_settings('wrong_permissions_page_url', false);
			if (!$allowed && $url && filter_var($url, FILTER_VALIDATE_URL)) {
				wp_safe_redirect(esc_url($url));
				exit();
			}
		}

		function wpultimo_signup_redirect_to($url, $site_id, $user_id) {
			if (!WPFA_Advanced_Obj()->is_frontend_dashboard_user($user_id)) {
				return $url;
			}

			$nonce_key = 'wpfa_wu_after_signup' . wu_get_current_site()->site_owner->ID;
			$url = wp_nonce_url($url, wp_create_nonce($nonce_key), 'wpfa_wu_after_signup');

			return $url;
		}

		function format_plans($saved_plans) {
			if (empty($saved_plans) || !is_array($saved_plans)) {
				$saved_plans = array();
			}
			$saved_plans = array_unique(array_filter(array_map('intval', $saved_plans)));
			return $saved_plans;
		}

		function is_user_allowed_to_view_page($allowed = true, $post_id = null, $shortcode_atts = array()) {
			if (!$post_id) {
				$post_id = get_the_ID();
			}
			if (!$post_id) {
				return $allowed;
			}

			if (!empty($shortcode_atts['wu_plans'])) {
				$saved_plans = array_map('intval', array_map('trim', explode(',', $shortcode_atts['wu_plans'])));
			} else {
				$saved_plans = $this->format_plans(get_post_meta($post_id, 'wpfa_wu_plans', true));
			}
			$blog_id = VG_Admin_To_Frontend_Obj()->get_site_id_for_admin_content();
			if (!empty($shortcode_atts) && VG_Admin_To_Frontend_Obj()->is_master_user() && !is_admin() && strpos($shortcode_atts['page_url'], 'wu-my-account') !== false && !$blog_id) {
				return new WP_Error('wp_frontend_admin', sprintf(__('Note from WP Frontend Admin: The Account page is created by WP Ultimo only for sites with a WP Ultimo plan and you have zero sites with a WP Ultimo plan. Please <a href="%s" target="_blank">create one site</a> and associated it with a WP Ultimo plan and you will be able to preview the account page in the frontend here.', VG_Admin_To_Frontend::$textname), network_admin_url('site-new.php')));
			}


			$site = wu_get_site($blog_id);
			$plan = $site->get_plan();

			if (is_object($plan) && !empty($saved_plans) && !in_array($plan->id, $saved_plans, true) && !VG_Admin_To_Frontend_Obj()->is_master_user()) {
				$allowed = false;
			}

			return $allowed;
		}

		/**
		 * Meta box display callback.
		 *
		 * @param WP_Post $post Current post object.
		 */
		function render_meta_box($post) {
			$saved_plans = $this->format_plans(get_post_meta($post->ID, 'wpfa_wu_plans', true));
			$plans = WU_Plans::get_plans();
			?>
			<div id="wpfa-wu-wrapper">
				<label>
					<?php echo __('This page is available for these WP Ultimo plans', 'vg_admin_to_frontend'); ?> <a href="#" title="<?php esc_attr_e('If you select a specific plan, we will show the content of this page only for users with the selected plan and users of other plans will be see an error message or redirect to your plans/upgrade page defined in the WP Frontend Admin settings page. Leave this field empty to display this page for all the plans', 'vg_admin_to_frontend'); ?>">(?)</a>					
				</label>
				<?php foreach ($plans as $plan) { ?>
					<div class="wpfa-wu-plan-row">
						<label><input <?php checked(in_array($plan->id, $saved_plans, true)); ?> type="checkbox" name="wpfa_wu_plans[]" value="<?php echo (int) $plan->id; ?>"> <?php echo esc_html($plan->title); ?></label>
					</div>
				<?php }
				?>

			</div>
			<hr>
			<?php
		}

		function save_meta_box($post_id, $post) {
			if (!isset($_REQUEST['wpfa_wu_plans'])) {
				return;
			}
			$saved_plans = $this->format_plans($_REQUEST['wpfa_wu_plans']);
			update_post_meta($post_id, 'wpfa_wu_plans', $saved_plans);
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_WP_Ultimo::$instance) {
				WPFA_WP_Ultimo::$instance = new WPFA_WP_Ultimo();
				WPFA_WP_Ultimo::$instance->init();
			}
			return WPFA_WP_Ultimo::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_WP_Ultimo_Obj')) {

	function WPFA_WP_Ultimo_Obj() {
		return WPFA_WP_Ultimo::get_instance();
	}

}
add_action('plugins_loaded', 'WPFA_WP_Ultimo_Obj');
