<?php

if (!function_exists('vgfa_fix_wpforms_mobile_version')) {
	add_filter('vg_frontend_admin/settings', 'vgfa_fix_wpforms_mobile_version', 10, 3);

	function vgfa_fix_wpforms_mobile_version($out, $key, $default) {
		if ($key === 'admin_view_css' && defined('WPFORMS_VERSION') && !empty($_GET['page']) && $_GET['page'] === 'wpforms-entries') {
			$out = '
@media screen and (max-width: 782px){
	.wp-list-table tr:not(.inline-edit-row):not(.no-items) td[class*="wpforms_field"]:nth-child(-n+4) {
		display: inline-block !important;

	}
	.wp-list-table tr:not(.inline-edit-row):not(.no-items) td:not(.check-column) {
		left: -43px;
	}
}
' . $out;
		}

		return $out;
	}

}