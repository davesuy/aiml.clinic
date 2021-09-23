<?php
namespace SG_Security\Rest;

use SG_Security\Logout_Service\Logout_Service;
use SG_Security\Plugins_Service\Plugins_Service;
use SG_Security\Password_Service\Password_Service;

/**
 * Rest Helper class that manages all of the post hack actions.
 */
class Rest_Helper_Post_Hack_Actions extends Rest_Helper {

	/**
	 * The constructor.
	 */
	public function __construct() {
		$this->logout_service   = new Logout_Service();
		$this->plugins_service  = new Plugins_Service();
		$this->password_service = new Password_Service();
	}

	/**
	 * Reinstalls all free plugins.
	 *
	 * @since  1.0.0
	 */
	public function resinstall_plugins() {
		$result = $this->plugins_service->reinstall_plugins();
		// Reinstall plugins.
		self::send_json(
			$this->get_response_message( $result, 'reinstall_plugins' ),
			$result
		);
	}

	/**
	 * Force passwords reset.
	 *
	 * @since  1.0.0
	 */
	public function force_password_reset() {
		$result = intval( $this->logout_service->change_salts() );

		$this->password_service->invalidate_passwords();
		// Reinstall plugins.
		self::send_json(
			$this->get_response_message( $result, 'force_password_reset' ),
			$result
		);
	}

	/**
	 * Logs out all users
	 *
	 * @since  1.0.0
	 */
	public function logout_users() {
		$result = intval( $this->logout_service->change_salts() );
		// Reinstall plugins.
		self::send_json(
			$this->get_response_message( $result, 'logout_users' ),
			$result
		);
	}
}
