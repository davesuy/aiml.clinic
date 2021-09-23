<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Endpoint_Add_Appointment extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointment-add-appointment';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {
		$params          = $request->get_params();
		$new_appointment = [];

		if ( ! $params['service'] || ! $params['date'] || ! $params['user_email'] || ! is_email( $params['user_email'] ) ) {
			return rest_ensure_response( array(
				'success' => false,
				'data'    => esc_html__( 'The appointment could not be added.', 'jet-appointments-booking' ),
			) );
		}

		$db_columns = Plugin::instance()->db->appointments->get_column_list();

		if ( ! empty( $db_columns ) ) {
			foreach ( $db_columns as $column ) {
				$exclude_columns = [
					'_locale',
					'date_timestamp',
					'slot_timestamp',
					'slot_end_timestamp',
				];

				if( array_key_exists( $column, $exclude_columns ) ){
					continue;
				}

				$value = in_array( $column, [ 'date', 'slot', 'slot_end' ] ) ? $params[ $column . '_timestamp' ] : $params[ $column ] ;
				$new_appointment[ $column ] = ! empty( $value ) ? esc_attr( $value ) : '';
			}
		}

		$new_appointment['order_id'] = NULL;

		if ( ! Plugin::instance()->db->appointment_available( $new_appointment ) ) {
			return rest_ensure_response( array(
				'success' => true,
				'data'    => esc_html__( 'Appointment time already taken', 'jet-appointments-booking' ),
			) );
		}

		$appointment_id = Plugin::instance()->db->add_appointment( $new_appointment );

		return rest_ensure_response( array(
			'success' => true,
			'data'    => sprintf( esc_html__( 'Success! New appointment ID:%s has been added', 'jet-appointments-booking' ), $appointment_id ),
		) );
	}

	public function permission_callback( $request ) {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Returns endpoint request method - GET/POST/PUT/DELTE
	 *
	 * @return string
	 */
	public function get_method() {
		return 'POST';
	}

	/**
	 * Returns arguments config
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			'ID' => array(
				'default'  => 1,
				'required' => false,
			),
			'user_id' => array(
				'default'  => 1,
				'required' => false,
			),
			'service' => array(
				'default'  => '',
				'required' => true,
			),
			'provider' => array(
				'default'  => '',
				'required' => false,
			),
			'date' => array(
				'default'  => '',
				'required' => false,
			),
			'slot' => array(
				'default'  => '',
				'required' => false,
			),
			'slot_end' => array(
				'default'  => '',
				'required' => false,
			),
			'user_email' => array(
				'default'  => '',
				'required' => false,
			),
		);
	}

}