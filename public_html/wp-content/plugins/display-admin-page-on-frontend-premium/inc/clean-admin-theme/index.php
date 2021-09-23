<?php
if (!class_exists('WPFA_Clean_Admin_Theme')) {

	class WPFA_Clean_Admin_Theme {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
				add_filter('redux/options/' . VG_Admin_To_Frontend::$textname . '/sections', array($this, 'add_global_options'));
				add_filter('vg_frontend_admin/settings', array($this, 'render_css'), 10, 2);
		}

		function render_css($out, $key) {

			// Dont load the admin theme if they haven't selected a main color manually
			// We skip filters because this runs in the filter, so it would trigger an infinite loop
			$main_color = VG_Admin_To_Frontend_Obj()->get_settings('main_color', '', true);
			if (empty($main_color)) {
				return $out;
			}

			if ($key !== 'admin_view_css') {
				return $out;
			}
			$out .= file_get_contents(__DIR__ . '/styles.css');
			ob_start();
			$this->dynamic_css();
			$dynamic_css = ob_get_clean();
			$out .= $dynamic_css;
			return str_replace(array('<style>', '</style>', "\t"), '', $out);
		}

		function add_global_options($sections) {
			$sections['apperance']['fields'][] = array(
				'id' => 'main_color',
				'type' => 'color',
				'title' => __('Clean Admin Look : Primary color for admin content', VG_Admin_To_Frontend::$textname),
				'desc' => __('Select a color and we will change the design of the admin content to look much better and elegant. Leave it empty and the admin content will use the standard look. If you are using a plugin that loos bad when this option is activated, contact us and we will fix it.', VG_Admin_To_Frontend::$textname),
				'validate' => 'color',
			);
			return $sections;
		}

		function dynamic_css() {
			$main_color = VG_Admin_To_Frontend_Obj()->get_settings('main_color', '', true);
			?><style>
				.wp-core-ui .button, .wp-core-ui .button-secondary,
				.wrap .page-title-action,
				.wp-core-ui .button-secondary:hover, .wp-core-ui .button.hover, .wp-core-ui .button:hover,
				.wp-core-ui .button-secondary:active, .wp-core-ui .button:active{
					color: <?php echo sanitize_text_field($main_color); ?>;
					border-color: <?php echo sanitize_text_field($main_color); ?>;
				}
				a,
				.wp-core-ui .button-link,
				a:active, a:hover {
					color: <?php echo sanitize_text_field($main_color); ?>;
				}
				a:active, a:hover,
				.wp-core-ui .button-secondary:hover, .wp-core-ui .button.hover, .wp-core-ui .button:hover,
				.wp-core-ui .button-secondary:active, .wp-core-ui .button:active{
					opacity: 0.9;
				}
				#adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, #adminmenu li.wp-has-current-submenu .wp-submenu .wp-submenu-head, .folded #adminmenu li.current.menu-top, #adminmenu .wp-has-current-submenu .wp-submenu .wp-submenu-head, #adminmenu .wp-menu-arrow, #adminmenu .wp-menu-arrow div, #adminmenu li.current a.menu-top, #adminmenu li.wp-has-current-submenu a.wp-has-current-submenu, .folded #adminmenu li.current.menu-top, .folded #adminmenu li.wp-has-current-submenu, #adminmenu a:hover, #adminmenu li.menu-top:hover, #adminmenu li.opensub > a.menu-top, #adminmenu li > a.menu-top:focus, .woocommerce-message .button-primary, p.woocommerce-actions .button-primary, .jp-connect-full__button-container .dops-button.is-primary, .dops-button.is-primary,
				.woocommerce-BlankState a.button-primary, .woocommerce-BlankState button.button-primary, .woocommerce-message a.button-primary, .woocommerce-message button.button-primary, .wp-core-ui .button-primary,
				.components-button.is-primary,
				.wp-core-ui .button-primary.active,
				.wp-core-ui .button-primary:focus,
				.wp-core-ui .button-primary:hover {
					background: <?php echo sanitize_text_field($main_color); ?>;
					box-shadow: none;
					text-shadow: none;
				}

				#adminmenu div.wp-menu-image:before {
					color: <?php echo sanitize_text_field($main_color); ?>;
					font-weight: 500;
				}

				.jp-connect-full__button-container .dops-button.is-primary,
				.woocommerce-BlankState a.button-primary, .woocommerce-BlankState button.button-primary, .woocommerce-message a.button-primary, .woocommerce-message button.button-primary,
				.components-button.is-primary,
				.dops-button.is-primary,
				.wp-core-ui .button-primary,
				.wp-core-ui .button-primary.active,
				.wp-core-ui .button-primary:focus,
				.wp-core-ui .button-primary:hover {
					font-weight: 500;
					color: white;
					border: 1px solid <?php echo sanitize_text_field($main_color); ?>;
				}
				div.woocommerce-message {
					border-left-color: <?php echo sanitize_text_field($main_color); ?> !important;
				}
			</style><?php
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Clean_Admin_Theme::$instance) {
				WPFA_Clean_Admin_Theme::$instance = new WPFA_Clean_Admin_Theme();
				WPFA_Clean_Admin_Theme::$instance->init();
			}
			return WPFA_Clean_Admin_Theme::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Clean_Admin_Theme_Obj')) {

	function WPFA_Clean_Admin_Theme_Obj() {
		return WPFA_Clean_Admin_Theme::get_instance();
	}

}
WPFA_Clean_Admin_Theme_Obj();
