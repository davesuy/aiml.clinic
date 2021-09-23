<?php
namespace SG_Security\Message_Service;

use SG_Security\Options_Service\Options_Service;

/**
 * Message service class.
 */
class Message_Service {

	/**
	 * Prepare response message for react app.
	 *
	 * @since  1.0.0
	 *
	 * @param  bool   $status The result of operation.
	 * @param  string $key    The option key.
	 * @param  bool   $type   True for enable, false for disable option.
	 *
	 * @return string       The response message.
	 */
	public static function get_response_message( $result, $option, $type = '' ) {
		// Array containing message responses.
		$messages = array(
			'simple' => array(
				'force_password_reset' => array(
					__( 'Failed to force password reset.', 'sg-security' ),
					__( 'All users will be asked to reset their passwords on next login.', 'sg-security' ),
				),
				'reinstall_plugins' => array(
					__( 'Failed to reinstall plugins.', 'sg-security' ),
					__( 'Plugins reinstalled.', 'sg-security' ),
				),
				'logout_users' => array(
					__( 'Failed to log out all users.', 'sg-security' ),
					__( 'All users are logged out.', 'sg-security' ),
				),
				'delete_readme' => array(
					__( 'Failed to delete Readme.html.', 'sg-security' ),
					__( 'Readme.html deleted.', 'sg-security' ),
				),
			),
			'complex' => array(
				'lock_system_folders' => array(
					array(
						__( 'Failed to disable System Folders protection.', 'sg-security' ),
						__( 'System Folders protection disabled.', 'sg-security' ),
					),
					array(
						__( 'Failed to enable System Folders protection.', 'sg-security' ),
						__( 'System Folders protection enabled.', 'sg-security' ),
					),
				),
				'disable_file_edit' => array(
					array(
						__( 'Failed to enable Themes & Plugins Editor.', 'sg-security' ),
						__( 'Themes & Plugins Editor enabled.', 'sg-security' ),
					),
					array(
						__( 'Failed to Disable Themes & Plugins Editor.', 'sg-security' ),
						__( 'Themes & Plugins Editor disabled.', 'sg-security' ),
					),
				),
				'wp_remove_version' => array(
					array(
						__( 'Failed to display WordPress version.', 'sg-security' ),
						__( 'WordPress version displayed.', 'sg-security' ),
					),
					array(
						__( 'Failed to hide WordPress version.', 'sg-security' ),
						__( 'WordPress version hidden.', 'sg-security' ),
					),
				),
				'disable_xml_rpc' => array(
					array(
						__( 'Failed to disable XML-RPC.', 'sg-security' ),
						__( 'XML-RPC enabled.', 'sg-security' ),
					),
					array(
						__( 'Failed to enable XML-RPC.', 'sg-security' ),
						__( 'XML-RPC disabled.', 'sg-security' ),
					),
				),
				'disable_feed' => array(
					array(
						__( 'Failed to enable RSS/ATOM feeds.', 'sg-security' ),
						__( 'RSS/ATOM feeds enabled.', 'sg-security' ),
					),
					array(
						__( 'Failed to Disable RSS/ATOM feeds', 'sg-security' ),
						__( 'RSS/ATOM feeds disabled.', 'sg-security' ),
					),
				),
				'xss_protection' => array(
					array(
						__( 'Failed to disable Advanced XSS Protection', 'sg-security' ),
						__( 'Advanced XSS Protection disabled', 'sg-security' ),
					),
					array(
						__( 'Failed to enable Advanced XSS Protection', 'sg-security' ),
						__( 'Advanced XSS Protection enabled', 'sg-security' ),
					),
				),
				'sg2fa' => array(
					array(
						__( 'Failed to disable Two-factor Authentication for Admin Users', 'sg-security' ),
						__( 'Two-factor Authentication for Admin Users disabled', 'sg-security' ),
					),
					array(
						__( 'Failed to enable Two-factor Authentication for Admin Users', 'sg-security' ),
						__( 'Two-factor Authentication for Admin Users enabled', 'sg-security' ),
					),
				),
				'disable_usernames' => array(
					array(
						__( 'Failed to enable “admin” Username', 'sg-security' ),
						__( '“admin” Username enabled', 'sg-security' ),
					),
					array(
						__( 'Failed to Disable the “admin” Username', 'sg-security' ),
						__( '“admin” Username disabled', 'sg-security' ),
					),
				),
			),
		);

		if ( '' !== $type ) {
			return $messages['complex'][ $option ][ $type ][ intval( $result ) ];
		}

		return $messages['simple'][ $option ][ intval( $result ) ];
	}
}