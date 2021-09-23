<?php

if (!class_exists('WPFA_Elementor')) {

	class WPFA_Elementor {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			if (!defined('ELEMENTOR_VERSION')) {
				return;
			}

			add_action('admin_init', array($this, 'migrate_deprecated_option'));
			add_action('get_edit_post_link', array($this, 'modify_edit_link'), 100, 2);
			add_action('wp_enqueue_scripts', array($this, 'enqueue_templates_css'), 99);
			add_filter('vg_frontend_admin/compatible_default_editors', array($this, 'add_compatible_default_editor'));
			add_filter('elementor/document/urls/edit', array($this, 'modify_elementor_edit_url'));
			add_filter('wp_frontend_admin/text_edits_for_current_page', array($this, 'prevent_text_replacement_errors'));
		}

		function prevent_text_replacement_errors($text_edits) {
			if (isset($text_edits['Elementor'])) {
				// We must replace only full words, "Elementor" breaks Elementor because it changes ElementorConfig
				$text_edits['/\\\bElementor\\\b/'] = $text_edits['Elementor'];
				unset($text_edits['Elementor']);
			}
			return $text_edits;
		}

		function modify_elementor_edit_url($url) {
			if (!empty($_GET['vgfa_source'])) {
				$url = add_query_arg('vgfa_source', (int) $_GET['vgfa_source'], $url);
			}
			return $url;
		}

		function migrate_deprecated_option() {
			$is_elementor_default_editor = (bool) VG_Admin_To_Frontend_Obj()->get_settings('elementor_default_editor', '');
			$default_editor = VG_Admin_To_Frontend_Obj()->get_settings('default_editor', '');
			if ($is_elementor_default_editor && !$default_editor) {
				VG_Admin_To_Frontend_Obj()->update_option('default_editor', 'elementor');
			}
		}

		function add_compatible_default_editor($editors) {
			$editors['elementor'] = 'Elementor';
			return $editors;
		}

		function enqueue_templates_css() {
			if (!is_singular()) {
				return;
			}

			if (dapof_fs()->is_plan('platform', true)) {
				$post_id = get_queried_object_id();
				$elementor_data = get_post_meta($post_id, '_elementor_data', true);
				if ($elementor_data && strpos($elementor_data, 'wpfa-') !== false) {
					wp_enqueue_style('wp-frontend-admin-elementor-styles', plugins_url('/assets/css/elementor-templates.css', VG_Admin_To_Frontend::$file));
				}
			}
		}

		/**
		 * 
		 * @param type $link
		 * @param type $post_id
		 * @return type
		 */
		function modify_edit_link($link, $post_id) {
			$default_editor = VG_Admin_To_Frontend_Obj()->get_settings('default_editor', '');
			if (!empty($_GET['elementor-preview']) || !post_type_supports(get_post_type($post_id), 'elementor') || $default_editor !== 'elementor' || (!empty($_GET['action']) && $_GET['action'] === 'elementor')) {
				return $link;
			}
			$url_parameters = array(
				'post' => $post_id,
				'action' => 'elementor'
			);
			$link = esc_url(add_query_arg($url_parameters, $link));
			return $link;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Elementor::$instance) {
				WPFA_Elementor::$instance = new WPFA_Elementor();
				WPFA_Elementor::$instance->init();
			}
			return WPFA_Elementor::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Elementor_Obj')) {

	function WPFA_Elementor_Obj() {
		return WPFA_Elementor::get_instance();
	}

}
add_action('init', 'WPFA_Elementor_Obj');
