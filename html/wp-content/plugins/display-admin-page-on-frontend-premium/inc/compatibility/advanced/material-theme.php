<?php
if (!class_exists('WPFA_Material_Theme')) {

	class WPFA_Material_Theme {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			if (!class_exists('MaterialWP')) {
				return;
			}
			add_filter('vg_frontend_admin/settings', array($this, 'render_css'), 10, 2);
			add_filter('vg_frontend_admin/settings', array($this, 'render_js'), 10, 2);
		}

		function render_js($out, $key) {
			if ($key !== 'admin_view_js') {
				return $out;
			}
			ob_start();
			?>
			setTimeout(function(){
			if( jQuery('#wpbody-content > .wrap').length > 1 ){
			jQuery('#wpbody-content > .wrap').first().remove();
			}
			}, 1000);
			<?php
			return $out . ob_get_clean();
		}

		function render_css($out, $key) {
			if ($key !== 'admin_view_css') {
				return $out;
			}
			ob_start();
			?>
			body[class*="material"]:not(.wp-customizer):not(.vc_editor) #wpbody-content {
			margin: 0;
			}
			@media only screen and (max-width: 1223px) and (min-width: 783px){
			body[class*="material"]:not(.wp-customizer) #wpbody-content, 
			body[class*="material"] #screen-meta-links {
			width: 100%!important;
			}
			}
			body[class*="material"] div#parallax-main-block {
			display: none;
			}
			<?php
			return $out . ob_get_clean();
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Material_Theme::$instance) {
				WPFA_Material_Theme::$instance = new WPFA_Material_Theme();
				WPFA_Material_Theme::$instance->init();
			}
			return WPFA_Material_Theme::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Material_Theme_Obj')) {

	function WPFA_Material_Theme_Obj() {
		return WPFA_Material_Theme::get_instance();
	}

}
add_action('init', 'WPFA_Material_Theme_Obj');
