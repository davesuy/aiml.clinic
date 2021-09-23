<?php
if (!class_exists('WPFA_Access_Restrictions')) {

	class WPFA_Access_Restrictions {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			remove_action('admin_init', 'send_frame_options_header');
			remove_action('login_init', 'send_frame_options_header');
			add_action('admin_init', array($this, 'allow_iframes_on_origin'));
			add_action('login_init', array($this, 'allow_iframes_on_origin'));
			add_action('admin_init', array($this, 'apply_page_blacklist'));
			add_action('admin_head', array($this, 'enforce_frontend_dashboard'), 1);
			add_action('wp', array($this, 'maybe_redirect_to_login_page'));
			add_action('wp_head', array($this, 'redirect_to_main_window_from_iframe'), 1);
			add_action('wp_frontend_admin/frontend_page_created', array($this, 'whitelist_pages_after_creation'));
			if (!empty($_GET['wpfa_auto_whitelist_urls'])) {
				add_action('admin_init', array($this, 'whitelist_existing_urls'), 10, 0);
			}
			add_filter('vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/before_saving', array($this, 'modify_whitelisted_urls_before_saving'));
			add_action('pre_get_users', array($this, 'filter_users_list__premium_only'));
			add_filter('login_url', array($this, 'maybe_filter_login_url__premium_only'), 80, 2);
			
		}

		function maybe_filter_login_url__premium_only($login_url, $redirect) {
			$wpfa_login_page_url = VG_Admin_To_Frontend_Obj()->get_settings('login_page_url');
			if (strpos($login_url, 'wp-login.php') !== false && !empty($wpfa_login_page_url)) {
				$login_url = esc_url(add_query_arg('redirect_to', $redirect, $wpfa_login_page_url));
			}
			return $login_url;
		}

		function filter_users_list__premium_only($query) {
			global $pagenow;
			if (is_admin() && 'users.php' == $pagenow && !VG_Admin_To_Frontend_Obj()->is_master_user()) {
				$roles = wp_roles();
				$allowed_roles = null;
				foreach ($roles->roles as $role_key => $role) {
					if (!VG_Admin_To_Frontend_Obj()->user_has_any_role(array($role_key))) {
						continue;
					}
					$option_key = 'user_roles_visible_for_' . $role_key;
					$value = VG_Admin_To_Frontend_Obj()->get_settings($option_key, '');
					if (empty($value)) {
						continue;
					}
					$allowed_roles = array_map('trim', explode(',', $value));
					if ($allowed_roles) {
						break;
					}
				}
				if ($allowed_roles) {
					$query->set('role__in', $allowed_roles);
				}
			}
		}

		function modify_whitelisted_urls_before_saving($options) {
			if (empty($options['whitelisted_admin_urls'])) {
				return $options;
			}

			if (is_array($options['whitelisted_admin_urls'])) {
				$urls = $options['whitelisted_admin_urls'];
			} else {
				$urls = array_map('trim', preg_split('/\r\n|\r|\n/', $options['whitelisted_admin_urls']));
			}
			foreach ($urls as $index => $url) {
				$urls[$index] = remove_query_arg(array('post', 'token', '_wpnonce', 'user_id', 'wp_http_referer', 's', 'updated', 'page_id'), html_entity_decode($url));
			}

			$all_urls = serialize($urls);
			// Automatically whitelist post-related URLs if they whitelisted the list of posts or post editor
			// The allowed URLs are the trash action, editor for existing posts, and create new post
			if (preg_match('/(edit\.php|post-new\.php)/', $all_urls)) {
				if (strpos($all_urls, 'post.php?action=edit') === false) {
					$urls[] = admin_url('post.php?action=edit');
				}
				if (strpos($all_urls, 'post.php?action=trash"') === false) {
					$urls[] = admin_url('post.php?action=trash');
				}
				if (strpos($all_urls, 'post.php"') === false) {
					$urls[] = admin_url('post.php');
				}
			}
			$options['whitelisted_admin_urls'] = implode(PHP_EOL, array_unique($urls));
			return $options;
		}

		function whitelist_pages_after_creation() {
			$this->whitelist_existing_urls(false, false);
		}

		function whitelist_existing_urls($redirect = true, $only_master_user = true) {
			global $wpdb;
			// Only administrators can whitelist urls
			if (!current_user_can('manage_options')) {
				return;
			}
			if ($only_master_user && !VG_Admin_To_Frontend_Obj()->is_master_user()) {
				return;
			}

			$existing_pages = $wpdb->get_col("SELECT post_content FROM $wpdb->posts WHERE post_content LIKE '%[vg_display_admin_page%' ");
			if (empty($existing_pages)) {
				return;
			}
			$pages = implode('<br>', $existing_pages);

			preg_match_all('/\[vg_display_admin_page page_url="([^"]+)"/', $pages, $matches);

			if (!empty($matches[1])) {
				$this->whitelist_urls($matches[1]);
			}

			if ($redirect) {
				$redirect_to = remove_query_arg('wpfa_auto_whitelist_urls');
				wp_redirect($redirect_to);
				exit();
			}
		}

		function whitelist_urls($urls) {
			$whitelisted_urls = array_map('trim', preg_split('/\r\n|\r|\n/', VG_Admin_To_Frontend_Obj()->get_settings('whitelisted_admin_urls', '')));
			$whitelisted_urls = array_unique(array_merge($whitelisted_urls, $urls));

			$prepared_urls = $this->modify_whitelisted_urls_before_saving(array(
				'whitelisted_admin_urls' => $whitelisted_urls
			));
			if (empty($prepared_urls['whitelisted_admin_urls'])) {
				$prepared_urls['whitelisted_admin_urls'] = array();
			}

			VG_Admin_To_Frontend_Obj()->update_option('whitelisted_admin_urls', implode(PHP_EOL, $prepared_urls['whitelisted_admin_urls']));
		}

		function is_global_dashboard() {
			$out = false;

			
			return $out;
		}

		/**
		 * If we have defined a login page and the inline login form is not used
		 * We check if the current page has our shortcode and redirect the page to the login page
		 * 
		 * @return null
		 */
		function maybe_redirect_to_login_page() {
			$login_page_url = VG_Admin_To_Frontend_Obj()->get_login_url();
			if (is_user_logged_in() || !is_singular() || empty($login_page_url)) {
				return;
			}

			if (!VG_Admin_To_Frontend_Obj()->is_wpfa_page() && !$this->is_global_dashboard()) {
				return;
			}
			$current_url = VG_Admin_To_Frontend_Obj()->get_current_url();
			if (strpos($current_url, $login_page_url) !== false) {
				return;
			}

			wp_redirect(esc_url(add_query_arg('wpfa_redirect_to_login', 1, $login_page_url)));
			exit();
		}

		function redirect_to_main_window_from_iframe() {
			$is_disabled = apply_filters('vg_admin_to_frontend/open_frontend_pages_in_main_window', VG_Admin_To_Frontend_Obj()->get_settings('disable_frontend_to_main_window', false));
			if ($is_disabled) {
				return;
			}
			if (wp_doing_ajax() || !empty($_POST)) {
				return;
			}
			$allowed_keywords = array_map('trim', explode(',', VG_Admin_To_Frontend_Obj()->get_settings('frontend_urls_allowed_in_iframe', '')));
			$allowed_keywords = array_merge($allowed_keywords, array('elementor', 'microthemer', 'trp-edit-translation', 'brizy-edit-iframe', 'pagebuilder-edit-iframe', 'oxygen'));
			?>
			<script>
				var adminBaseUrl = <?php echo json_encode(admin_url()); ?>;
				// If the iframe loaded a frontend page (not wp-admin), open it in the main window
				var keywordsAllowed = <?php echo json_encode($allowed_keywords); ?>;
				var keywordFound = false;
				try {
					if (window.parent.location.href.indexOf(adminBaseUrl) < 0 && window.parent.location.href !== window.location.href) {

						keywordsAllowed.forEach(function (keyword) {
							if (keyword && (window.location.href.indexOf(keyword) > -1 || window.parent.location.href.indexOf(keyword) > -1)) {
								keywordFound = true;
							}
						});
						// Redirect if it is an url
						if (!keywordFound && window.location.href.indexOf('http') > -1) {
							window.parent.location.href = window.location.href;
						}
					}
				} finally {

				}
			</script>
			<?php
		}

		/**
		 * Redirect whitelisted pages to the frontend dashboard only if they're 
		 * viewed outside the iframe (frontend dashboard)
		 * @return null
		 */
		function enforce_frontend_dashboard() {
			$is_blacklisted = $this->is_page_blacklisted();
			$skip_frontend_dashboard_enforcement = apply_filters('vg_admin_to_frontend/skip_frontend_dashboard_enforcement', $is_blacklisted || !is_user_logged_in() || !is_admin() || current_user_can(VG_Admin_To_Frontend_Obj()->master_capability()) || wp_doing_ajax() || wp_doing_cron(), $is_blacklisted);
			if ($skip_frontend_dashboard_enforcement) {
				return;
			}

			$whitelisted_capability = VG_Admin_To_Frontend_Obj()->get_settings('whitelisted_user_capability');
			if (current_user_can($whitelisted_capability)) {
				return;
			}

			$url = remove_query_arg(array('post'), VG_Admin_To_Frontend_Obj()->get_current_url());
			$url_path = VG_Admin_To_Frontend_Obj()->get_admin_url_without_base($url);

			

			$frontend_page_id = VG_Admin_To_Frontend_Obj()->get_page_id(esc_url(admin_url($url_path)), '');

			if ($frontend_page_id) {
				$redirect_to = get_permalink($frontend_page_id);
			} else {
				$redirect_to = VG_Admin_To_Frontend_Obj()->get_settings('redirect_to_frontend', !empty(VG_Admin_To_Frontend_Obj()->get_settings('enable_wpadmin_access_restrictions')) ? home_url() : null);
			}

		

			if (empty($redirect_to)) {
				return;
			}
			$redirect_to = add_query_arg(array_merge($_GET, array('wpfa_frontend_url' => 1)), $redirect_to);
			?>
			<script>
				// If it's not an iframe and it is an URL, redirect the parent window to the frontend url
				if (window === window.parent && window.location.href.indexOf('http') > -1) {
					window.parent.location.href = <?php echo json_encode(esc_url_raw($redirect_to)); ?>;
				}
			</script>
			<?php
		}

		function is_page_blacklisted() {
			$whitelisted = VG_Admin_To_Frontend_Obj()->get_settings('whitelisted_admin_urls');
			$whitelisted_capability = VG_Admin_To_Frontend_Obj()->get_settings('whitelisted_user_capability');
			$restrictions_are_enabled = VG_Admin_To_Frontend_Obj()->get_settings('enable_wpadmin_access_restrictions');

			if (!is_user_logged_in() || empty($restrictions_are_enabled) || empty($whitelisted) || empty($whitelisted_capability) || (!empty($whitelisted_capability) && current_user_can($whitelisted_capability)) || VG_Admin_To_Frontend_Obj()->is_master_user() || strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
				return apply_filters('vg_admin_to_frontend/is_page_blacklisted', false, $whitelisted, $whitelisted_capability, $restrictions_are_enabled);
			}

			// Don't apply the access restrictions if the user is administrator
			// and we're viewing the settings page, so the admin can change settings
			// and not be locked out.
			if (is_admin() && current_user_can('manage_options') && (!empty($_GET['page']) && $_GET['page'] === 'vg_admin_to_frontend')) {
				return apply_filters('vg_admin_to_frontend/is_page_blacklisted', false, $whitelisted, $whitelisted_capability, $restrictions_are_enabled);
			}

			if (wp_doing_ajax()) {
				// Allow ajax requests from users with low capabilities because it's secure by default
				// We apply the blacklist on ajax requests from users with administrator capabilities only
				// as an extra layer of security
				if (!current_user_can('manage_options')) {
					return apply_filters('vg_admin_to_frontend/is_page_blacklisted', false, $whitelisted, $whitelisted_capability, $restrictions_are_enabled);
				}

				// We allow ajax requests coming from the frontend
				$url = wp_get_referer();
				$admin_url = admin_url();
				if (strpos($url, $admin_url) === false) {
					return apply_filters('vg_admin_to_frontend/is_page_blacklisted', false, $whitelisted, $whitelisted_capability, $restrictions_are_enabled);
				}
			}
			if (empty($url)) {
				$url = VG_Admin_To_Frontend_Obj()->get_current_url();
			}
			$url_path = VG_Admin_To_Frontend_Obj()->prepare_loose_url($url);
			$whitelisted_urls = array_map('trim', preg_split('/\r\n|\r|\n/', $whitelisted));

			$allowed = false;
			if (!empty($url_path)) {
				foreach ($whitelisted_urls as $whitelisted_url) {
					if (strpos($whitelisted_url, '/' . $url_path) !== false) {
						$allowed = true;
						break;
					}
				}
			}

			if ($allowed) {
				return apply_filters('vg_admin_to_frontend/is_page_blacklisted', false, $whitelisted, $whitelisted_capability, $restrictions_are_enabled);
			}

			return apply_filters('vg_admin_to_frontend/is_page_blacklisted', true, $whitelisted, $whitelisted_capability, $restrictions_are_enabled);
		}

		function apply_page_blacklist() {
			$is_blacklisted = $this->is_page_blacklisted();
			if (!$is_blacklisted) {
				return;
			}
			if (wp_doing_ajax()) {
				die('0');
			}

			$redirect_to = VG_Admin_To_Frontend_Obj()->get_settings('redirect_to_frontend', home_url());
			wp_redirect(add_query_arg(array('wpfa_blacklisted_url' => 1), $redirect_to));
			exit();
		}

		function allow_iframes_on_origin() {
			$host = VG_Admin_To_Frontend_Obj()->get_settings('root_domain');
			if (empty($host)) {
				$info = parse_url(get_site_url(1));
				$host = $info['host'];
			}

			header("Content-Security-Policy: frame-ancestors 'self' *.$host");
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Access_Restrictions::$instance) {
				WPFA_Access_Restrictions::$instance = new WPFA_Access_Restrictions();
				WPFA_Access_Restrictions::$instance->init();
			}
			return WPFA_Access_Restrictions::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Access_Restrictions_Obj')) {

	function WPFA_Access_Restrictions_Obj() {
		return WPFA_Access_Restrictions::get_instance();
	}

}
WPFA_Access_Restrictions_Obj();
