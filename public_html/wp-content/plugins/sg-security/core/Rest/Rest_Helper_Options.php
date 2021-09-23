<?php
namespace SG_Security\Rest;

use SG_Security\Options_Service\Options_Service;
use SG_Security\Message_Service\Message_Service;
use SG_Security\Login_Service\Login_Service;

/**
 * Rest Helper class that manages all of the options.
 */
class Rest_Helper_Options extends Rest_Helper {

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->options_service = new Options_Service();
		$this->login_service   = new Login_Service();
	}

	/**
	 * Checks if the option key exists.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $request Request data.
	 * @param  string $option  The option name.
	 */
	public function change_option_from_rest( $request, $option ) {
		$value  = $this->validate_and_get_option_value( $request, $option );
		$result = $this->change_option( $option, $value );

		// Set the response message.
		self::send_json(
			Message_Service::get_response_message( $result, $option, $value ),
			$result,
			array(
				$option => $value,
			)
		);
	}

	/**
	 * Provide all plugin options.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $request Request data.
	 */
	public function fetch_options( $request ) {
		// Get the option key.
		$data         = json_decode( $request->get_body(), true );
		$admin_exists = get_user_by( 'login', 'admin' );

		$pages = array(
			'security' => array(
				'lock_system_folders' => intval( get_option( 'sg_security_lock_system_folders', 0 ) ),
				'disable_file_edit'   => intval( get_option( 'sg_security_disable_file_edit', 0 ) ),
				'wp_remove_version'   => intval( get_option( 'sg_security_wp_remove_version', 0 ) ),
				'disable_xml_rpc'     => intval( get_option( 'sg_security_disable_xml_rpc', 0 ) ),
				'disable_feed'        => intval( get_option( 'sg_security_disable_feed', 0 ) ),
				'xss_protection'      => intval( get_option( 'sg_security_xss_protection', 0 ) ),
			),
			'login'    => array(
				'sg2fa'             => intval( get_option( 'sg_security_sg2fa', 0 ) ),
				'disable_usernames' => intval( get_option( 'sg_security_disable_usernames', 0 ) ),
				'admin_exists'      => false === $admin_exists ? 0 : 1,
				'login_access'      => get_option( 'sg_login_access', array() ),
				'login_attempts'    => $this->login_service->get_login_attempts_data( intval( get_option( 'sg_security_login_attempts', 0 ) ) ),
			),
		);

		// Send the error message if page does not exist.
		if ( ! array_key_exists( $data['page'], $pages ) ) {
			self::send_json( '', 0 );
		}

		// Send the response to react app.
		self::send_json( '', 1, $pages[ $data['page'] ] );
	}
}
