<?php
if (!class_exists('WPFA_Advanced')) {

	class WPFA_Advanced {

		static private $instance = false;

		private function __construct() {
			
		}

		function hide_system_pages__premium_only($wp_query) {
			global $wpdb;

			if (empty(VG_Admin_To_Frontend_Obj()->get_settings('hide_system_pages')) || !is_admin() || is_network_admin() || $wp_query->query['post_type'] !== 'page' || !$wp_query->is_main_query() || VG_Admin_To_Frontend_Obj()->is_master_user()) {
				return $wp_query;
			}

			$system_page_ids = array_unique(array_map('intval', array_merge($wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_content LIKE '%[vg_display_admin_page%' "), $wpdb->get_col("SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'is_wpfa_page' AND meta_value = 1"))));

			$pages_to_hide = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE ID IN (" . implode(',', $system_page_ids) . ") AND post_author != " . (int) get_current_user_id());

			if (!empty($pages_to_hide)) {
				$wp_query->query_vars['post__not_in'] = ( empty($wp_query->query_vars['post__not_in'])) ? $pages_to_hide : array_merge($wp_query->query_vars['post__not_in'], $pages_to_hide);
			}
			return $wp_query;
		}

		function init() {
			add_filter('pre_get_posts', array($this, 'hide_system_pages__premium_only'));
			add_action('admin_footer', array($this, 'default_user_role__premium_only'));
			add_filter('login_redirect', array($this, 'maybe_redirect_to_the_dashboard__premium_only'), 10, 3);
		}

		function default_user_role__premium_only() {
			if (empty(VG_Admin_To_Frontend_Obj()->get_settings('default_user_role_add')) || VG_Admin_To_Frontend_Obj()->is_master_user() || is_network_admin()) {
				return;
			}
			?>
			<script>
				jQuery(window).on('load', function () {
					var defaultRole = <?php echo json_encode(sanitize_text_field(VG_Admin_To_Frontend_Obj()->get_settings('default_user_role_add'))); ?>;
					if (defaultRole && jQuery('#createuser').length) {
						var $role = jQuery('#createuser select#role');
						$role.val(defaultRole);
						if ($role.val() === defaultRole) {
							$role.parents('tr').hide();
						}
					}
				});
			</script>
			<?php
		}

		function maybe_redirect_to_the_dashboard__premium_only($url, $requested_redirect_to, $user) {
			$frontend_dashboard_url = VG_Admin_To_Frontend_Obj()->get_settings('redirect_to_frontend');
			if ($frontend_dashboard_url && $this->is_frontend_dashboard_user($user)) {
				$url = $frontend_dashboard_url;
			}
			return $url;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Advanced::$instance) {
				WPFA_Advanced::$instance = new WPFA_Advanced();
				WPFA_Advanced::$instance->init();
			}
			return WPFA_Advanced::$instance;
		}

		function is_frontend_dashboard_user($user) {
			$out = false;
			if (is_object($user) && !is_wp_error($user)) {
				$user_id = $user->ID;
			} elseif (is_int($user)) {
				$user_id = $user;
			} else {
				return $out;
			}

			if (VG_Admin_To_Frontend_Obj()->is_master_user()) {
				return $out;
			}

			if (VG_Admin_To_Frontend_Obj()->get_settings('dashboard_users_role')) {
				$roles = array_map('trim', explode(',', VG_Admin_To_Frontend_Obj()->get_settings('dashboard_users_role')));
				if (!empty($roles) && VG_Admin_To_Frontend_Obj()->user_has_any_role($roles, $user)) {
					$out = true;
				}
			} elseif (!VG_Admin_To_Frontend_Obj()->is_master_user()) {
				$out = true;
			}

			// If this is a multisite network and user is only administrator of blog 1, allow wp-admin
			if (is_multisite()) {
				$user_belongs_to_blogs = get_blogs_of_user($user_id);
				$first_blog = end($user_belongs_to_blogs);
				if (count($user_belongs_to_blogs) === 1 && $first_blog->userblog_id === 1 && VG_Admin_To_Frontend_Obj()->user_has_any_role(array('administrator'), $user)) {
					$out = false;
				}
			}
			return $out;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Advanced_Obj')) {

	function WPFA_Advanced_Obj() {
		return WPFA_Advanced::get_instance();
	}

}
WPFA_Advanced_Obj();
