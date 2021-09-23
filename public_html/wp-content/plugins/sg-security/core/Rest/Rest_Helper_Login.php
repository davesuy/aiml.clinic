<?php
namespace SG_Security\Rest;

use SG_Security\Rest\Rest_Helper_Options;
use SG_Security\Sg_2fa\Sg_2fa;
use SG_Security\Login_Service\Login_Service;
use SG_Security\Usernames_Service\Usernames_Service;
use SG_Security\Message_Service\Message_Service;

/**
 * Rest Helper class that manages the login security.
 */
class Rest_Helper_Login extends Rest_Helper {

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->rest_helper_options = new Rest_Helper_Options();
		$this->sg_2fa              = new Sg_2fa();
		$this->login_service       = new Login_Service();
		$this->usernames_service   = new Usernames_Service();
	}

	/**
	 * Update the login access.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $request Request data.
	 */
	public function login_access( $request ) {
		$data = json_decode( $request->get_body(), true );

		update_option( 'sg_login_access', $data );
		self::send_json(
			'Login access updated!',
			1,
			array(
				'login_access' => $data,
			)
		);
	}

	/**
	 * Set the 2fa.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $request Request data.
	 */
	public function sg2fa( $request ) {
		$this->rest_helper_options->change_option_from_rest( $request, 'sg2fa' );
	}

	/**
	 * Disable the admin username.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $request Request data.
	 */
	public function disable_admin_username( $request ) {
		$new_username = $this->validate_and_get_option_value( $request, 'admin_name', false );
		$value        = $this->validate_and_get_option_value( $request, 'disable_usernames', false );

		if ( ! empty( $new_username ) ) {
			$this->usernames_service->change_admin_username( $new_username );
		}

		$result = $this->change_option( 'disable_usernames', $value );

		// Set the response message.
		self::send_json(
			Message_Service::get_response_message( $result, 'disable_usernames', $value ),
			$result,
			array(
				'disable_usernames' => $value,
				'admin_exists'      => 0,
			)
		);
	}

	/**
	 * Limit the number of unsuccessful login attempts.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $request Request data.
	 */
	public function limit_login_attempts( $request ) {
		$attempts = intval( $this->validate_and_get_option_value( $request, 'login_attempts' ) );

		update_option( 'sg_security_login_attempts', $attempts );

		delete_option( 'sg_security_unsuccessful_login' );

		self::send_json(
			'Login attempts limited!',
			1,
			$this->login_service->get_login_attempts_data( intval( get_option( 'sg_security_login_attempts', 0 ) ) )
		);
	}
}
