<?php

if (!class_exists('WPFA_Brizy')) {

	class WPFA_Brizy {

		static private $instance = false;

		private function __construct() {
			
		}

		function init() {
			if (!defined('BRIZY_VERSION')) {
				return;
			}
			
				add_filter('brizy_editor_config', array($this, 'filter_editor_config'));
				add_filter('brizy_editor_page_context', array($this, 'filter_brizy_editor_page_context'));
				add_filter('vg_frontend_admin/compatible_default_editors', array($this, 'add_compatible_default_editor'));
				add_action('get_edit_post_link', array($this, 'modify_edit_link'), 100, 2);
			
		}

		function modify_edit_link($link, $post_id) {
			$default_editor = VG_Admin_To_Frontend_Obj()->get_settings('default_editor', '');
			$supported_post_type = Brizy_Editor::checkIfPostTypeIsSupported($post_id, false);
			if (!empty($_GET['brizy-edit']) || !$supported_post_type || $default_editor !== 'brizy') {
				return $link;
			}
			$link = esc_url(get_permalink($post_id) . '?brizy-edit');
			return $link;
		}

		function add_compatible_default_editor($editors) {
			$editors['brizy'] = 'Brizy';
			return $editors;
		}

		function filter_editor_config($config) {
			if (!empty($_GET['wpfa_referrer'])) {
				$config['urls']['backToDashboard'] = esc_url(base64_decode($_GET['wpfa_referrer']));
			}
			return $config;
		}

		function filter_brizy_editor_page_context($config) {
			if (!empty($_GET['wpfa_referrer'])) {
				$config['iframe_url'] = add_query_arg('wpfa_referrer', $_GET['wpfa_referrer'], $config['iframe_url']);
			}
			return $config;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WPFA_Brizy::$instance) {
				WPFA_Brizy::$instance = new WPFA_Brizy();
				WPFA_Brizy::$instance->init();
			}
			return WPFA_Brizy::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('WPFA_Brizy_Obj')) {

	function WPFA_Brizy_Obj() {
		return WPFA_Brizy::get_instance();
	}

}
WPFA_Brizy_Obj();
