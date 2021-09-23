<?php

if (!function_exists('wpfa_gravity_forms_compat')) {

	add_action('init', 'wpfa_gravity_forms_compat');

	function wpfa_gravity_forms_compat() {
		if (!class_exists('GFForms')) {
			return;
		}

		// Disable noconflict mode because it prevents our JS from loading
		update_option('gform_enable_noconflict', false);
	}

}