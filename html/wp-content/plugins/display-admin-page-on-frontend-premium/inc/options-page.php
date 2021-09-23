<?php
if (!class_exists('WPFA_Options')) {

	class WPFA_Options {

		static private $instance = false;
		var $settings = null;
		var $sections = array();

		private function __construct() {
			
		}

		function init() {

			add_action('init', array($this, 'late_init'));
		}

		function late_init() {
			$this->set_sections();
			if (is_multisite()) {
				$main_options = get_blog_option(1, VG_Admin_To_Frontend::$textname, array());
			} else {
				$main_options = array();
			}
			$args = array(
				'sections' => $this->sections,
				'opt_name' => VG_Admin_To_Frontend::$textname,
				'display_name' => __('Settings', VG_Admin_To_Frontend::$textname),
				'page_permissions' => is_multisite() ? 'manage_network' : 'manage_options',
				'enable_wpmu_mode' => (!empty($main_options['enable_wpmu_mode']) && is_multisite()),
				'sdk' => VG_Admin_To_Frontend_Obj()->vg_plugin_sdk,
			);
			$this->settings = new VGFP_SDK_Settings_Page($args);
			$global_mode_enabled = VG_Admin_To_Frontend_Obj()->get_settings('enable_wpmu_mode');
			if (empty($global_mode_enabled) || !is_multisite()) {
				add_action('admin_menu', array($this, 'register_menu_page'), 99);
			} else {
				add_action('network_admin_menu', array($this, 'register_menu_page'), 99);
			}
			add_action('vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/after_tab_links', array($this, 'render_troubleshooting_tab_link'));
			add_action('vg_plugin_sdk/settings/' . VG_Admin_To_Frontend::$textname . '/after_tabs_content', array($this, 'render_troubleshooting_tab_content'));
		}

		function render_troubleshooting_tab_content() {
			global $wpdb;

			$issues = array();


			$admin_url = admin_url();
			$home_url = home_url();
			if (strpos($admin_url, 'https://') !== false && strpos($home_url, 'https://') === false) {
				$issues['http_protocol_mismatch'] = sprintf(__('You are using https for wp-admin and http for the public website. Both need to use the same protocol (https) for security reasons. Please change the public URL to use https. <a href="%s" target="_blank" class="button">Fix it</a>', VG_Admin_To_Frontend::$textname), esc_url(admin_url('options-general.php')));
			}
			if (!get_option('permalink_structure')) {
				$issues['permalink_missing'] = sprintf(__('You need to enable pretty permalinks for our plugin to work. <a href="%s" target="_blank" class="button">Fix it</a>', VG_Admin_To_Frontend::$textname), esc_url(admin_url('options-permalink.php')));
			}
			if (is_multisite() ) {
				$issues['platform_plan'] = sprintf(__('You are using the Standard plan of WP Frontend Admin on a multisite network. We recommend that you upgrade to the Platform plan to enjoy all our multisite features, including global dashboard, beautiful dashboard templates, courses, and more. <a href="%s" target="_blank" class="button">Fix it</a>', VG_Admin_To_Frontend::$textname), 'https://wpfrontendadmin.com/buy-now/');
			}

			$issues = apply_filters('vg_admin_to_frontend/potential_issues', $issues);
			?>
			<style>
				.troubleshooting ol {
					padding-left: 20px;
					line-height: 33px;
				}
			</style>
			<div class="troubleshooting tab-content">
				<h3><?php _e('Potential issues', VG_Admin_To_Frontend::$textname); ?></h3>
				<p><?php _e('We detected the following items that you can resolve. These will fix the most common errors when using our plugin. If you dont know how to resolve them, <a href="https://wpfrontendadmin.com/contact/" target="_blank">you can contact us</a> and we will do it for you for free.', VG_Admin_To_Frontend::$textname); ?></p>
				<?php
				echo '<ol>';
				foreach ($issues as $issue) {
					echo '<li>' . wp_kses_post($issue) . '</li>';
				}
				echo '</ol>';
				if (empty($issues)) {
					echo '<p>' . __('No issues detected', VG_Admin_To_Frontend::$textname) . '</p>';
				}

				
				?>
				<h3 onclick="jQuery('.wpfa-support-data').toggle();" style="cursor: pointer;"><?php _e('Data useful for support', VG_Admin_To_Frontend::$textname); ?> &#x25BC;</h3>
				<div class="wpfa-support-data" style="display: none;">
					<b><?php _e('Pages containing our shortcode', VG_Admin_To_Frontend::$textname); ?></b>
					<?php
					$rows = $wpdb->get_results("SELECT ID,post_title,post_status, post_date FROM $wpdb->posts WHERE post_content LIKE '%[vg_display_admin_page%' AND post_status NOT IN ('inherit', 'auto-draft') ORDER BY post_date DESC", ARRAY_A);
					foreach ($rows as $row) {
						if (empty($row['post_title'])) {
							$row['post_title'] = '(empty title)';
						}
						echo '<p>' . esc_html($row['post_title']) . '. Date: ' . esc_html($row['post_date']) . '. Status: ' . esc_html($row['post_status']) . '. <a href="' . esc_url(admin_url('post.php?action=edit&post=' . $row['ID'])) . '" target="_blank">Edit</a> - <a href="' . esc_url(get_permalink($row['ID'])) . '" target="_blank">View</a></p>';
					}
					?>


					<b><?php _e('Text changes made on individual pages', VG_Admin_To_Frontend::$textname); ?></b>
					<?php
					$rows = $wpdb->get_results("SELECT meta_value,post_id FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts p ON p.ID = pm.post_id WHERE p.post_status = 'publish' AND pm.meta_key = 'vgfa_text_changes'", ARRAY_A);
					$text_changes = array();
					foreach ($rows as $row) {
						$text_edits = json_decode($row['meta_value'], true);
						if (!is_array($text_edits)) {
							continue;
						}
						foreach ($text_edits as $url => $edits) {
							if (empty($url)) {
								continue;
							}
							$url = 'Page ID: ' . (int) $row['post_id'] . ' - URL: ' . esc_html($url);
							if (!isset($text_changes[$url])) {
								$text_changes[$url] = array();
							}
							$text_changes[$url] = array_merge($text_changes[$url], $edits);
						}
					}

					foreach ($text_changes as $url => $edits) {
						echo '<p>' . $url . '</p>';
						foreach ($edits as $search => $replace) {
							echo '<p>Replace: ' . wp_kses_post($search) . '<br>With: ' . wp_kses_post($replace) . '</p>';
						}
					}
					
					?>
				</div>
			</div>
			<?php
		}

		function render_troubleshooting_tab_link() {
			?>
			<a href="#troubleshooting"><?php _e('Troubleshooting', VG_Admin_To_Frontend::$textname); ?></a>
			<?php
		}

		function get_settings_page_url() {

			$global_mode_enabled = VG_Admin_To_Frontend_Obj()->get_settings('enable_wpmu_mode');
			$out = (empty($global_mode_enabled) || !is_multisite()) ? admin_url('admin.php?page=vg_admin_to_frontend') : network_admin_url('admin.php?page=vg_admin_to_frontend');
			return $out;
		}

		function register_menu_page() {
			add_submenu_page(
					'wpatof_welcome_page', $this->settings->args['display_name'], $this->settings->args['display_name'], $this->settings->args['page_permissions'], $this->settings->args['opt_name'], array($this->settings, 'render_settings_page'));
		}

		function render_loading_animations__premium_only($field) {
			wp_enqueue_style('wp-frontend-admin-animations', plugins_url('/assets/vendor/spinkit.min.css', VG_Admin_To_Frontend::$file));
			?>
			<style>
				:root {
					--sk-size: 60px !important;
					--sk-color: #fff !important;
				}

				.animations-previews {
					background-color: orange;
					padding: 20px;
					overflow: auto;
					text-align: center;
				}

				.animations-previews .example {
					width: 25%;
					float: left;
					height: 100px;
					margin-bottom: 30px;
				}
				.animations-previews .example > div {
					margin: 0 auto;
				}
			</style>
			<p><?php _e('Previews:', VG_Admin_To_Frontend::$textname); ?></p>
			<div class="animations-previews">
				<div class="example">
					<p>Plane</p>
					<div class="sk-plane"></div>
				</div>

				<div class="example">
					<p>Chase</p>
					<div class="sk-chase">
						<div class="sk-chase-dot"></div>
						<div class="sk-chase-dot"></div>
						<div class="sk-chase-dot"></div>
						<div class="sk-chase-dot"></div>
						<div class="sk-chase-dot"></div>
						<div class="sk-chase-dot"></div>
					</div>
				</div>

				<div class="example">
					<p>Bounce</p>
					<div class="sk-bounce">
						<div class="sk-bounce-dot"></div>
						<div class="sk-bounce-dot"></div>
					</div>
				</div>

				<div class="example">
					<p>Wave</p>
					<div class="sk-wave">
						<div class="sk-wave-rect"></div>
						<div class="sk-wave-rect"></div>
						<div class="sk-wave-rect"></div>
						<div class="sk-wave-rect"></div>
						<div class="sk-wave-rect"></div>
					</div>
				</div>

				<div class="example">
					<p>Pulse</p>
					<div class="sk-pulse"></div>
				</div>

				<div class="example">
					<p>Flow</p>
					<div class="sk-flow">
						<div class="sk-flow-dot"></div>
						<div class="sk-flow-dot"></div>
						<div class="sk-flow-dot"></div>
					</div>
				</div>

				<div class="example">
					<p>Swing</p>
					<div class="sk-swing">
						<div class="sk-swing-dot"></div>
						<div class="sk-swing-dot"></div>
					</div>
				</div>

				<div class="example">
					<p>Circle</p>
					<div class="sk-circle">
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
						<div class="sk-circle-dot"></div>
					</div>
				</div>

				<div class="example">
					<p>Circle fade</p>
					<div class="sk-circle-fade">
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
						<div class="sk-circle-fade-dot"></div>
					</div>
				</div>

				<div class="example">
					<p>Grid</p>
					<div class="sk-grid">
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
						<div class="sk-grid-cube"></div>
					</div>
				</div>

				<div class="example">
					<p>Fold</p>
					<div class="sk-fold">
						<div class="sk-fold-cube"></div>
						<div class="sk-fold-cube"></div>
						<div class="sk-fold-cube"></div>
						<div class="sk-fold-cube"></div>
					</div>
				</div>

				<div class="example">
					<p>Wander</p>
					<div class="sk-wander">
						<div class="sk-wander-cube"></div>
						<div class="sk-wander-cube"></div>
						<div class="sk-wander-cube"></div>
					</div>
				</div>
			</div>
			<?php
		}

		public function set_sections() {
			global $wp_post_types;

			$roles = wp_roles();
			$capabilities = array();
			foreach ($roles->roles as $role) {
				if (!empty($role['capabilities'])) {
					$capabilities = array_merge($capabilities, array_keys($role['capabilities']));
				}
			}

			if (is_multisite()) {
				$capabilities = array_merge($capabilities, array(
					'create_sites',
					'delete_sites',
					'manage_network',
					'manage_sites',
					'manage_network_users',
					'manage_network_plugins',
					'manage_network_themes',
					'manage_network_options',
					'upgrade_network',
					'setup_network'
				));

				$sites = $this->get_network_sites();
			}
			// We tried to remove the capabilities that require second parameter, but it failed because they were already removed
			// I don't know why some users have those capabilities in the dropdown
//			foreach ($wp_post_types as $post_type_key => $post_type_object) {
//				$remove_capabilities = array($post_type_object->cap->edit_post, $post_type_object->cap->read_post, $post_type_object->cap->delete_post);
//				$capabilities = array_diff($capabilities, $remove_capabilities);
//			}
			sort($capabilities);

			$fields = array();
			if (is_multisite()) {
				
					$main_options = get_blog_option(1, VG_Admin_To_Frontend::$textname, array());
					if (is_network_admin()) {
						$fields[] = array(
							'id' => 'enable_wpmu_mode',
							'type' => 'switch',
							'title' => __('Enable global settings on Multisite?', VG_Admin_To_Frontend::$textname),
							'desc' => __('WP Multisite Detected. Now you manage the settings in the network panel and they apply to the entire network. Deactivate this option if you want to manage settings on each site.', VG_Admin_To_Frontend::$textname),
							'default' => false,
						);
					} else {
						if (get_current_blog_id() === 1) {
							$fields[] = array(
								'id' => 'enable_wpmu_mode',
								'type' => 'switch',
								'title' => __('Enable global settings on Multisite?', VG_Admin_To_Frontend::$textname),
								'desc' => __('WP Multisite Detected. Activate this option to manage the settings in the network panel and the settings will apply to the entire network of sites. Keep this option deactivated to use different settings on individual sites.', VG_Admin_To_Frontend::$textname),
								'default' => false,
							);
						} else {
							$fields[] = array(
								'id' => 'enable_wpmu_mode',
								'type' => 'info',
								'desc' => sprintf(__('WP Multisite Detected. You can manage the settings globally and apply them to all the sites. <a href="%s">Enable global mode on the main site</a>.', VG_Admin_To_Frontend::$textname), get_admin_url(1, 'admin.php?page=vg_admin_to_frontend')),
							);
						}
					}
				
			}
			$fields = array_merge($fields, array(
				array(
					'id' => 'add_post_edit_link',
					'type' => 'switch',
					'title' => __('Add "Edit" link after post content', VG_Admin_To_Frontend::$textname),
					'desc' => __('Enable this option if you want to allow your frontend users to edit posts and link to the frontend page when viewing a post. Super admins will see a link to the wp-admin dashboard.', VG_Admin_To_Frontend::$textname),
					'default' => false,
				),
				array(
					'id' => 'disable_quick_settings',
					'type' => 'switch',
					'title' => __('Disable the quick settings?', VG_Admin_To_Frontend::$textname),
					'desc' => __('Enable this option if you do not want to use the quick settings bar on the frontend, you can edit everything on the normal page editor.', VG_Admin_To_Frontend::$textname),
					'default' => false,
				),
				array(
					'id' => 'disable_all_admin_notices',
					'type' => 'switch',
					'title' => __('Disable the wp-admin notices when viewing on the frontend?', VG_Admin_To_Frontend::$textname),
					'desc' => __('Enable this option if you want to remove all the plugin notifications, update notifications, and annoying notifications on the frontend pages. Keep in mind that useful notifications will be removed as well', VG_Admin_To_Frontend::$textname),
					'default' => false,
				),
				array(
					'id' => 'redirect_after_publish_post',
					'type' => 'select',
					'title' => __('Redirect users to this page after publishing a post', VG_Admin_To_Frontend::$textname),
					'desc' => __('This applies to all the post types that are using the Classic Editor', VG_Admin_To_Frontend::$textname),
					'options' => array(
						'' => '--',
						'posts_list' => __('Redirect to the list of posts', VG_Admin_To_Frontend::$textname),
						'create_new' => __('Redirect to create new post', VG_Admin_To_Frontend::$textname),
					),
				),
				array(
					'id' => 'redirect_after_pending_review_post',
					'type' => 'select',
					'title' => __('Redirect users to this page after creating a post with pending review status?', VG_Admin_To_Frontend::$textname),
					'desc' => __('This applies to all the post types that are using the Classic Editor', VG_Admin_To_Frontend::$textname),
					'options' => array(
						'' => '--',
						'posts_list' => __('Redirect to the list of posts', VG_Admin_To_Frontend::$textname),
						'create_new' => __('Redirect to create new post', VG_Admin_To_Frontend::$textname),
					),
				),
				array(
					'id' => 'disable_permissions_help_message',
					'type' => 'switch',
					'title' => __('Disable the message indicating why a page didnt load?', VG_Admin_To_Frontend::$textname),
					'desc' => __('We show a message saying: "You need higher permissions" so administrators can see why a page doesnt load on the frontend.', VG_Admin_To_Frontend::$textname),
					'default' => false,
				),
			));


			
				$fields[] = array(
					'id' => 'default_user_role_add',
					'type' => 'select',
					'options' => array_combine(array_keys($roles->roles), array_keys($roles->roles)),
					'title' => __('Auto select this user role when we are creating users in the frontend', VG_Admin_To_Frontend::$textname),
				);
				$fields[] = array(
					'id' => 'hide_system_pages',
					'type' => 'switch',
					'title' => __('Hide pages containing our shortcode?', VG_Admin_To_Frontend::$textname),
					'desc' => __('If you create a dashboard for your users and you want them to edit pages, you can hide our dashboard pages to prevent them from editing the "system pages". If you activate this option, only the user who created the pages will be able to see them in the list and edit them.', VG_Admin_To_Frontend::$textname),
					'default' => false,
				);
				$compatible_default_editors = apply_filters('vg_frontend_admin/compatible_default_editors', array(
					'' => __('Regular WordPress editor', VG_Admin_To_Frontend::$textname),
				));
				if (count($compatible_default_editors) > 1) {
					$fields[] = array(
						'id' => 'default_editor',
						'type' => 'select',
						'options' => $compatible_default_editors,
						'title' => __('Default post/page editor', VG_Admin_To_Frontend::$textname),
						'desc' => __('When the users click on the edit link of any post or open the posts list, the edit link will open the selected editor directly. This applies to all the user roles except the site admin (super admin if multisite network).', VG_Admin_To_Frontend::$textname),
					);
				}
				$fields[] = array(
					'id' => 'restrict_post_types_by_author',
					'type' => 'text',
					'title' => __('Restrict post types to only display/edit posts created by the current user', VG_Admin_To_Frontend::$textname),
					'desc' => __('Enter the list of post types that should list and allow to edit posts created by the current user only. Enter post type keys separated by commas', VG_Admin_To_Frontend::$textname),
				);
				
			


			$this->sections[] = array(
				'icon' => 'el-icon-cogs',
				'title' => __('General', VG_Admin_To_Frontend::$textname),
				'fields' => $fields
			);
			$this->sections['access-restrictions'] = array(
				'title' => __('Access restrictions', VG_Admin_To_Frontend::$textname),
				'fields' => array(
					array(
						'id' => 'enable_wpadmin_access_restrictions',
						'type' => 'switch',
						'title' => __('Enable the wp-admin access restrictions?', VG_Admin_To_Frontend::$textname),
						'desc' => __('Enable this option if you want to make sure users can view specific admin pages in the frontend and restrict other admin pages.', VG_Admin_To_Frontend::$textname),
						'default' => false,
					),
					array(
						'id' => 'whitelisted_admin_urls',
						'type' => 'textarea',
						'title' => __('Access restriction: What wp-admin pages can be viewed on the frontend?', VG_Admin_To_Frontend::$textname),
						'desc' => sprintf(__('Enter a list of admin URLs that can be displayed in the frontend, one URL per line. All URLs not found in this list will be redirected to the homepage. We automatically add to this list the pages that you display on the frontend. Note, You still need to be careful with the user roles. The normal users should not be adminsitrators and they should not have advanced permissions like activate_plugins or manage_options capabilities. <a href="%s">Allow pages that contain our shortcode currently</a>', VG_Admin_To_Frontend::$textname), add_query_arg('wpfa_auto_whitelist_urls', 1)),
						'required' => array('enable_wpadmin_access_restrictions', 'equals', true)
					),
					array(
						'id' => 'whitelisted_user_capability',
						'type' => 'select',
						'title' => __('Access restriction: Who can access the wp-admin dashboard?', VG_Admin_To_Frontend::$textname),
						'desc' => __('You can select the user capability who can access the wp-admin dashboard and bypass all the access restrictions. For example, "manage_options" means users who can manage site options can access all the admin pages. You can deactivate the restrictions by clearing this option.', VG_Admin_To_Frontend::$textname),
						'options' => array_combine(array_unique($capabilities), array_unique($capabilities)),
						'default' => VG_Admin_To_Frontend_Obj()->master_capability(),
//					'required' => array('enable_wpadmin_access_restrictions', 'equals', true)
					),
					array(
						'id' => 'redirect_to_frontend',
						'type' => 'text',
						'validate' => 'url',
						'title' => __('Access restriction: Frontend dashboard URL', VG_Admin_To_Frontend::$textname),
						'desc' => __('Enter the URL of your frontend dashboard, this should be the start page of the dashboard. When users access a wp-admin page directly, we will automatically redirect to the equivalent frontend page (i.e. wp-admin > pages is redirected to the "pages" in the frontend, only if you created the frontend page previously), if the frontend page doesn\'t exist we redirect to this URL as the "default page". Leave empty to redirect to the homepage', VG_Admin_To_Frontend::$textname),
//					'required' => array('enable_wpadmin_access_restrictions', 'equals', true)
					),
				)
			);


			
				$this->sections['access-restrictions']['fields'][] = array(
					'id' => 'wrong_permissions_page_url',
					'type' => 'text',
					'validate' => 'url',
					'title' => __('Wrong Permissions Page URL (optional)', VG_Admin_To_Frontend::$textname),
					'desc' => __('By default, when someone opens an admin page in the frontend with the wrong user role, we show a message saying that they are not allowed to access that page. You can enter a URL of your pricing page or upgrade page and we will redirect users to that page when they try to do something not allowed by their role (membership plan).', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['access-restrictions']['fields'][] = array(
					'id' => 'wrong_permissions_message',
					'type' => 'text',
					'title' => __('Wrong Permissions Message (optional)', VG_Admin_To_Frontend::$textname),
					'desc' => __('By default, when someone opens an admin page in the frontend with the wrong user role, we show a message saying that they are not allowed to access that page. You can change the message here.', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['access-restrictions']['fields'][] = array(
					'id' => 'dashboard_users_role',
					'type' => 'text',
					'title' => __('These user roles will use the frontend dashboard', VG_Admin_To_Frontend::$textname),
					'desc' => __('We will redirect those users to the frontend dashboard anytime they log in or try to access the wp-admin dashboard.', VG_Admin_To_Frontend::$textname),
				);
				foreach ($roles->roles as $role_key => $role) {
					$this->sections['access-restrictions']['fields'][] = array(
						'id' => 'user_roles_visible_for_' . $role_key,
						'type' => 'text',
						'title' => sprintf(__('Users with role %s can see users with these roles (optional)', VG_Admin_To_Frontend::$textname), $role['name']),
						'desc' => __('By default, any user with permissions to view the users table can see all the users. Enter the list of user role keys separated by commas to only show those roles for users with this role.', VG_Admin_To_Frontend::$textname),
					);
				}
			
			$this->sections['apperance'] = array(
				'title' => __('Appearance', VG_Admin_To_Frontend::$textname),
				'fields' => array(
					array(
						'id' => 'admin_view_css',
						'type' => 'textarea',
						'mode' => 'css',
						'title' => __('Admin CSS', VG_Admin_To_Frontend::$textname),
						'desc' => __('This css will be used to customize the admin page when it´s displayed on the frontend. For example, you can hide admin elements or tweak design. You dont need to add style tags, just add the plain css.', VG_Admin_To_Frontend::$textname),
					),
					array(
						'id' => 'hide_admin_bar_frontend',
						'type' => 'switch',
						'title' => __('Hide admin bar on the frontend', VG_Admin_To_Frontend::$textname),
						'desc' => __('By default WordPress shows a black bar at the top of the page when a logged in user views a frontend page. The bar lets you access the wp-admin, log out, edit the current page, etc. If you enable this option we will hide that bar and you can use the shortcode: [vg_display_logout_link] to display the logout link.', VG_Admin_To_Frontend::$textname),
						'default' => false,
					),
				)
			);
			
				$this->sections['apperance']['fields'][] = array(
					'id' => 'loading_animation',
					'type' => 'select',
					'title' => __('Loading animation style', VG_Admin_To_Frontend::$textname),
					'default' => '',
					'options' => array(
						'none' => 'None',
						'plane' => 'Plane',
						'chase' => 'Chase',
						'bounce' => 'Bounce',
						'wave' => 'Wave',
						'pulse' => 'Pulse',
						'flow' => 'Flow',
						'swing' => 'Swing',
						'circle' => 'Circle',
						'circle-fade' => 'Circle fade',
						'grid' => 'Grid',
						'fold' => 'Fold',
						'wander' => 'Wander',
					),
					'callback_after_field' => array($this, 'render_loading_animations__premium_only')
				);
				$this->sections['apperance']['fields'][] = array(
					'id' => 'loading_animation_color',
					'type' => 'color',
					'title' => __('Loading animation color', VG_Admin_To_Frontend::$textname),
					'default' => '#000',
					'desc' => __('If your page background is dark, the animation should use a light color. If your background is white, the animation can be any dark color (dark blue, dark green, etc.)', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['apperance']['fields'][] = array(
					'id' => 'admin_view_js',
					'type' => 'textarea',
					'mode' => 'css',
					'title' => __('Admin JS', VG_Admin_To_Frontend::$textname),
					'desc' => __('This JS will be used to customize the admin page when it´s displayed on the frontend. For example, you can move elements and do advanced customizations. You dont need to add script tags, just add the plain js.', VG_Admin_To_Frontend::$textname),
				);
			
			$this->sections['login'] = array(
				'title' => __('Login', VG_Admin_To_Frontend::$textname),
				'fields' => array(
					array(
						'id' => 'login_page_url',
						'type' => 'text',
						'validate' => 'url',
						'title' => __('Login Page URL (optional)', VG_Admin_To_Frontend::$textname),
						'desc' => __('By default, when someone opens an admin page in the frontend without login, we show a login form in the same page. If you have a custom login page, you can enter the URL here and we will redirect users to your custom login page instead of showing our login form.', VG_Admin_To_Frontend::$textname),
					),
					array(
						'id' => 'login_message',
						'type' => 'editor',
						'title' => __('Login message', VG_Admin_To_Frontend::$textname),
						'default' => __('You need to login to view this page.', VG_Admin_To_Frontend::$textname),
						'desc' => __('This will be displayed when the current user is not logged in and tries to see an admin page through a shortcode on the frontend. We will display a login form after your message.', VG_Admin_To_Frontend::$textname),
					),
				)
			);
			
				$this->sections['login']['fields'][] = array(
					'id' => 'disable_logout_redirection',
					'type' => 'switch',
					'title' => __('Disable the logout redirection?', VG_Admin_To_Frontend::$textname),
					'desc' => __('We automatically redirect to the login page defined in the previous option or the homepage if login page is not set.', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['login']['fields'][] = array(
					'id' => 'demo_user',
					'type' => 'text',
					'title' => __('Advanced: Demo user', VG_Admin_To_Frontend::$textname),
					'desc' => __('If this site is only a demo site, you can enter a username and password and we will automatically login everybody using these demo credentials. Careful, you might get locked out if you enter the wrong credentials', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['login']['fields'][] = array(
					'id' => 'demo_password',
					'type' => 'text',
					'title' => __('Demo password', VG_Admin_To_Frontend::$textname),
				);
			
			$this->sections['solutions'] = array(
				'title' => __('Solutions to errors', VG_Admin_To_Frontend::$textname),
				'fields' => array(
					array(
						'id' => 'root_domain',
						'type' => 'text',
						'title' => __('Root domain of your website (optional)', VG_Admin_To_Frontend::$textname),
						'desc' => __('For example, enter site.com if your main site uses site.com, www.site.com, or any subdomain of site.com', VG_Admin_To_Frontend::$textname),
					),
					array(
						'id' => 'disable_stateful_navigation',
						'type' => 'switch',
						'title' => __('Disable the stateful navigation feature?', VG_Admin_To_Frontend::$textname),
						'desc' => __('We add a parameter to the URL like #wpfa:xxxxx to save the current state, and we show the same content when you reload the page or open in a new tab. Activate this to disable it in case you experience any issues when navigating or reloading the page', VG_Admin_To_Frontend::$textname),
						'default' => false,
					),
				)
			);
			
				$this->sections['solutions']['fields'][] = array(
					'id' => 'fullscreen_pages_keywords',
					'type' => 'text',
					'title' => __('Full screen pages', VG_Admin_To_Frontend::$textname),
					'desc' => __('We will display those pages as full screen, useful for form editors or page builders. For example, if the URL contains page=my-page-builder, you can enter page=my-page-builder. Enter the list of URL fragments separated by commas. We automatically show as full screen the pages: wp customizer, elementor editor, formidable forms editor, wpforms editor (keywords: action=elementor, page=formidable&fr, page=formidable-styles, page=wpforms-builder, customize.php)', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['solutions']['fields'][] = array(
					'id' => 'disable_fullscreen_pages_keywords',
					'type' => 'text',
					'title' => __('Disable full screen for these pages', VG_Admin_To_Frontend::$textname),
					'desc' => __('Some pages like the gutenberg editor, elementor editor, etc. appear as full screen pages by default. Enter the wp-admin URL keywords to disable the full screen for specific pages. For example, enter post-new.php,post.php to disable the full screen for the gutenberg editor (pages /wp-admin/post-new.php, /wp-admin/post.php)', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['solutions']['fields'][] = array(
					'id' => 'minimum_content_height',
					'type' => 'text',
					'validate' => 'numeric',
					'title' => __('Minimum content height', VG_Admin_To_Frontend::$textname),
					'desc' => __('Sometimes the admin content is very short and your page might look too small. Enter a number of pixels. I.e. 700', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['solutions']['fields'][] = array(
					'id' => 'frontend_urls_allowed_in_iframe',
					'type' => 'text',
					'title' => __('Which frontend URLs are allowed to load inside an iframe?', VG_Admin_To_Frontend::$textname),
					'desc' => __('We redirect frontend URLs from the iframe to the main window. But sometimes translation plugins or page builders need to load previews in iframes. You can use this option to allow those plugins to load pages inside iframes. Enter the keywords from the URL separated by commas.', VG_Admin_To_Frontend::$textname),
				);

				$this->sections['solutions']['fields'][] = array(
					'id' => 'extra_popup_selectors',
					'type' => 'text',
					'title' => __('Admin Popups CSS selectors', VG_Admin_To_Frontend::$textname),
					'desc' => __('Sometimes popups on admin sections (from wp-admin) dont open centered or are too tall, you can add the CSS selectors here and WP Frontend Admin will try to center the popups and adjust the size automatically', VG_Admin_To_Frontend::$textname),
				);
				$this->sections['solutions']['fields'][] = array(
					'id' => 'disable_frontend_to_main_window',
					'type' => 'switch',
					'title' => __('Disable the redirection of frontend pages to the main window', VG_Admin_To_Frontend::$textname),
					'desc' => __('Use this for debugging purposes in case of issues. If activating this fixes a problem, please contact us to fix it.', VG_Admin_To_Frontend::$textname),
					'default' => false,
				);
			
		}

		function get_network_sites() {
			$sites = get_sites();
			$out = array();
			foreach ($sites as $site) {
				$out[(int) $site->blog_id] = get_site_url($site->blog_id);
			}
			return $out;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Options::$instance) {
				WPFA_Options::$instance = new WPFA_Options();
				WPFA_Options::$instance->init();
			}
			return WPFA_Options::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Options_Obj')) {

	function WPFA_Options_Obj() {
		return WPFA_Options::get_instance();
	}

}
WPFA_Options_Obj();
