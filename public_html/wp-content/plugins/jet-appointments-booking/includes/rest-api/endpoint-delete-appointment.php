<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Endpoint_Delete_Appointment extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'delete-appointment';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {
		$params         = $request->get_params();
		$appointment_IDs = ! empty( $params['items'] ) ? $params['items'] : false ;

		if ( ! $appointment_IDs ) {
			return rest_ensure_response( array(
				'success' => false,
				'data'    => __( 'Appointment ID is not found in request', 'jet appointments-booking' ),
			) );
		}

		foreach ( $appointment_IDs as &$ID ){
			$ID = absint( $ID );
			Plugin::instance()->db->delete_appointment( $ID );
		}

		return rest_ensure_response( array(
			'success' => true,
		) );
	}

	/**
	 * Check user access to current end-popint
	 *
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Returns endpoint request method - GET/POST/PUT/DELETE
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

	/**
	 * Get query param. Regex with query parameters
	 *
	 * @return string
	 */
	/*public function get_query_params() {
		return '(?P<id>[\d]+)';
	}*/

}