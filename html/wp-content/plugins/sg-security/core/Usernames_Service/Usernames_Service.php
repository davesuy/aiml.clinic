<?php
namespace SG_Security\Usernames_Service;

/**
 * Handle usernames customization.
 */
class Usernames_Service {

	/**
	 * Add illegal usernames
	 *
	 * @since  1.0.0
	 *
	 * @param  array $usernames Default illegal usernames.
	 *
	 * @return array            Default + custom illegal usernames.
	 */
	public function add_illegal_usernames( $usernames ) {
		$illegal_usernames = apply_filters(
			'sg_security_illegal_usernames',
			array()
		);

		return array_merge( $illegal_usernames, array( 'admin' ) );
	}

	/**
	 * Chnage the default admin username.
	 *
	 * @since  1.0.0
	 *
	 * @return int|false The number of rows updated, or false on error.
	 */
	public function change_admin_username( $new_username ) {
		global $wpdb;

		$status = $wpdb->update( // phpcs:ignore
			$wpdb->users, // phpcs:ignore
			array( 'user_login' => $new_username ),
			array( 'user_login' => 'admin' )
		);

		return $status;
	}
}
