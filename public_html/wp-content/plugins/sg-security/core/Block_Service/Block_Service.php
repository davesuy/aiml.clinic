<?php
namespace SG_Security\Block_Service;

use SG_Security\Helper\Helper;

/**
 * Class that manages User's block related actions.
 */
class Block_Service {
	/**
	 * Block user if IP has a block flag set in the visitors table.
	 *
	 * @since  1.0.0
	 */
	public function block_user_by_ip() {
		global $wpdb;
		// Check if we have the ip blocked in the database.
		$result = $wpdb->get_col( // phpcs:ignore
			'SELECT `ip` FROM `' . $wpdb->sgs_visitors . '`
				WHERE `block` = 1
				AND `user_id` = 0
			;'
		);

		// Continue if ip is not blocked.
		if ( is_null( $result ) ) {
			return;
		}

		if ( ! in_array( Helper::get_current_user_ip(), $result ) ) {
			return;
		}

		// Stop the access to the website.
		wp_die( esc_html__( 'Your access to this site has been restricted by the administrator of this website.', 'sg-security' ) );
	}

	/**
	 * Change the user level to subscriber to limit capabilities.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $id The visitor ID we want to limit.
	 *
	 * @return array $response
	 */
	public function change_user_role( $id ) {
		// Get the user id.
		$user_id = $this->get_user_id_by_id( $id );

		// Bail if the user is truing to self block.
		if ( wp_get_current_user()->data->ID === $user_id ) {
			return array(
				'message' => __( 'This will restrict your account to a Subscriber role and will lock you out of the Admin Menu', 'sg-security' ),
				'result' => 0,
			);
		}

		// Get the user.
		$user = new \WP_User( $user_id );

		// Bail if we do not find the user.
		if ( empty( $user->ID ) ) {
			return array(
				'message' => __( 'User not found.', 'sg-security' ),
				'result' => 0,
			);
		}

		// Set the user to subscriber role.
		$user->set_role( 'subscriber' );

		global $wpdb;

		// Update the record in the database.
		$result = $wpdb->update( //phpcs:ignore
			$wpdb->sgs_visitors,
			array(
				'block'      => 1,
				'blocked_on' => time(),
			),
			array( 'id' => $id )
		);

		return array(
			'message' => __( 'User Blocked!.', 'sg-security' ),
			'result' => 0,
		);
	}

	/**
	 * Block IP.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $id The id reccord from the database.
	 *
	 * @return array     Response message.
	 */
	public function block_ip( $id, $type ) {
		// Get the IP from the database.
		$ip = $this->get_ip_by_id( $id );

		// Bail if we cannot find the record.
		if ( empty( $ip ) ) {
			return array(
				'message' => __( 'IP not found.', 'sg-security' ),
				'result' => 0,
			);
		}

		// Bail if the user is trying to self block.
		if (
			Helper::get_current_user_ip() === $request_id &&
			1 === intval( $type )
		) {
			return array(
				'message' => __( 'You cannot block the IP you are currently using!', 'sg-security' ),
				'result' => 0,
			);
		}

		global $wpdb;

		// Update the record in the database.
		$result = $wpdb->update( //phpcs:ignore
			$wpdb->sgs_visitors,
			array(
				'block'      => $type,
				'blocked_on' => time(),
			),
			array( 'id' => $id )
		);

		// Send an error if the update is unsuccessful.
		if ( false === $result ) {
			return array(
				'message' => intval( $type ) === 1 ? __( 'Could not block the IP.', 'sg-security' ) : __( 'Could not unblock the IP.', 'sg-security' ),
				'result' => 0,
			);
		}

		// IP blocked.
		return array(
			'message' => intval( $type ) === 1 ? __( 'IP Blocked.', 'sg-security' ) : __( 'IP Unblocked.', 'sg-security' ),
			'result' => 1,
		);
	}

	/**
	 * Get visitor ip by visitor id.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $id The id in the visitors table.
	 *
	 * @return string  The IP.
	 */
	public function get_ip_by_id( $id ) {
		global $wpdb;
		$maybe_ip = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				'SELECT `ip` FROM `' . $wpdb->sgs_visitors . '`
					WHERE `id` = %s
					AND `user_id` = 0
				;',
				$id
			)
		);

		if ( empty( $maybe_ip->ip ) ) {
			return false;
		}

		return $maybe_ip->ip;
	}

	/**
	 * Get user_id by visitor id.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $id The id in the visitors table.
	 *
	 * @return string  The user id.
	 */
	public function get_user_id_by_id( $id ) {
		global $wpdb;
		$maybe_id = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				'SELECT `user_id` FROM `' . $wpdb->sgs_visitors . '`
					WHERE `id` = %s
				;',
				$id
			)
		);

		if ( empty( $maybe_id->user_id ) ) {
			return false;
		}

		return $maybe_id->user_id;
	}

	/**
	 * Get visitor status by visitor id.
	 *
	 * @since  1.0.0
	 *
	 * @param  int $id The id in the visitors table.
	 *
	 * @return string  The user id.
	 */
	public function get_visitor_status( $id ) {
		global $wpdb;
		$maybe_id = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				'SELECT `block` FROM `' . $wpdb->sgs_visitors . '`
					WHERE `id` = %s
				;',
				$id
			)
		);

		if ( ! isset( $maybe_id->block ) ) {
			return array(
				'result' => 0,
			);
		}

		// IP blocked.
		return array(
			'result' => 1,
			'data' => array(
				'block'  => intval( $maybe_id->block ),
			),
		);
	}
}
