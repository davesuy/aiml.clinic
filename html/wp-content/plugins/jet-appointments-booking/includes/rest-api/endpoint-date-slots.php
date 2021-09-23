<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Endpoint_Date_Slots extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointment-date-slots';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {
		$params   = $request->get_params();
		$service  = ! empty( $params['service'] ) ? absint( $params['service'] ) : 0;
		$provider = ! empty( $params['provider'] ) ? absint( $params['provider'] ) : 0;
		$date     = ! empty( $params['date'] ) ? absint( $params['date'] ) : 0;
		$time     = ! empty( $params['timestamp'] ) ? absint( $params['timestamp'] ) : 0;
		$admin    = ! empty( $params['admin'] ) ? filter_var( $params['admin'], FILTER_VALIDATE_BOOLEAN ) : false;
		$result   = __( 'No available slots', 'jet-appointments-booking' );

		if ( ! $service || ! $date ) {
			return rest_ensure_response( array(
				'success' => false,
			) );
		}

		$slots   = Plugin::instance()->calendar->get_date_slots( $service, $provider, $date, $time );

		if( $admin ){
			$result = ! empty( $slots ) ? $slots : $result ;

			return rest_ensure_response( array(
				'success' => true,
				'noAvailable' => empty( $slots ),
				'data'    => $result,
			) );
		}

		ob_start();

		if ( ! empty( $slots ) ) {
			$price   = get_post_meta( $service, '_app_price', true );
			$dataset = array( 'data-price="' . $price . '"' );
			$format = Plugin::instance()->settings->get( 'slot_time_format' );
			Time_Slots::generate_slots_html( $slots, $format, $dataset, $service );
		} else {
			echo $result;
		}

		$result = ob_get_clean();
		$result .= '<div class="jet-apb-calendar-slots__close">&times;</div>';

		return rest_ensure_response( array(
			'success' => true,
			'data'    => $result,
		) );
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
			'date' => array(
				'default'  => '',
				'required' => true,
			),
			'service' => array(
				'default'  => '',
				'required' => true,
			),
			'provider' => array(
				'default'  => '',
				'required' => false,
			),
			'timestamp' => array(
				'default'  => '',
				'required' => false,
			),
		);
	}

}