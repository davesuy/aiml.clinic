<?php
namespace JET_APB;

/**
 * Calendar related data
 */
class Calendar {

	/**
	 * Get date slots
	 *
	 * @return [type] [description]
	 */
	public function get_date_slots( $service = 0, $provider = 0, $date = 0, $time = 0 ) {

		if ( ! $service || ! $date ) {
			return false;
		}

		timer_start();

		$weekday       = strtolower( date( 'l', $date ) );
		$slots         = array();
		$buffer_before = Plugin::instance()->settings->get( 'default_buffer_before' );
		$buffer_after  = Plugin::instance()->settings->get( 'default_buffer_after' );
		$duration      = Plugin::instance()->settings->get( 'default_slot' );
		$working_hours = Plugin::instance()->settings->get( 'working_hours' );

		if ( ! empty( $service ) ) {
			$service_duration = $this->get_custom_schedule( $service, 'default_slot'/*, '_service_duration'*/ );
			$duration         = null !== $service_duration && -1 !== $service_duration ? $service_duration : $duration ;

			$service_buffer_before = $this->get_custom_schedule( $service, 'buffer_before', '_buffer_before' );
			$buffer_before         = null !== $service_buffer_before && -1 !== $service_buffer_before ? $service_buffer_before : $buffer_before ;

			$service_buffer_after = $this->get_custom_schedule( $service, 'buffer_after', '_buffer_after' );
			$buffer_after         = null !== $service_buffer_after && -1 !== $service_buffer_after ? $service_buffer_after : $buffer_after ;

			$custom_schedule = $this->get_custom_schedule( $service, 'working_hours' );
			$working_hours   = null !== $custom_schedule ? $custom_schedule : $working_hours ;
		}

		if ( ! empty( $provider ) ) {
			$provider_duration = $this->get_custom_schedule( $provider, 'default_slot' );
			$duration          = null !== $provider_duration && -1 !== $provider_duration ? $provider_duration : $duration ;

			$provider_buffer_before = $this->get_custom_schedule( $provider, 'buffer_before' );
			$buffer_before          = null !== $provider_buffer_before && -1 !== $provider_buffer_before ? $provider_buffer_before : $buffer_before ;

			$provider_buffer_after = $this->get_custom_schedule( $provider, 'buffer_after' );
			$buffer_after          = null !== $provider_buffer_after && -1 !== $provider_buffer_after ? $provider_buffer_after : $buffer_after ;

			$custom_schedule = $this->get_custom_schedule( $provider, 'working_hours' );
			$working_hours   = null !== $custom_schedule ? $custom_schedule : $working_hours ;
		}

		$day_schedule  = ! empty( $working_hours[ $weekday ] ) ? $working_hours[ $weekday ] : array();
		Time_Slots::set_starting_point( $date );

		if ( 0 < $time ) {
			Time_Slots::set_timenow( $time );
		}

		if ( 1 < count( $day_schedule ) ) {

			usort( $day_schedule, function( $a, $b ) {

				$a_from = strtotime( $a['from'] );
				$b_from = strtotime( $b['from'] );

				if ( $a_from === $b_from ) {
					return 0;
				}

				return ( $a_from < $b_from ) ? -1 : 1;

			} );

		}

		foreach ( $day_schedule as $day_part ) {

			$slots = $slots + Time_Slots::generate_intervals( array(
				'from'          => $day_part['from'],
				'to'            => $day_part['to'],
				'duration'      => $duration,
				'buffer_before' => $buffer_before,
				'buffer_after'  => $buffer_after,
				'from_now'      => true,
			) );
		}

		$query_args = array(
			'date'    => $date,
			'status'  => Plugin::instance()->statuses->valid_statuses(),
		);

		if ( 'service' === Plugin::instance()->settings->get( 'check_by' ) ) {
			$query_args['service'] = $service;
		}

		if ( $provider ) {
			$query_args['provider'] = $provider;
		}

		$manage_capacity = Plugin::instance()->settings->get( 'manage_capacity' );
		$service_count   = 1;

		if ( $manage_capacity ) {
			$excluded      = Plugin::instance()->db->appointments->query_with_capacity( $query_args );
			$service_count = Plugin::instance()->tools->get_service_count( $service );
		} else {
			$excluded = Plugin::instance()->db->appointments->query( $query_args );
		}

		if ( ! empty( $excluded ) ) {
			foreach ( $excluded as $appointment ) {

				$excl_slot     = absint( $appointment['slot'] );
				$excl_slot_end = absint( $appointment['slot_end'] );
				$slot_count    = ! empty( $appointment['slot_count'] ) ? absint( $appointment['slot_count'] ) : 1;

				if ( ! $excl_slot ) {
					continue;
				}

				if ( $manage_capacity ) {

					if ( isset( $slots[ $excl_slot ] ) && $slot_count >= $service_count ) {
						unset( $slots[ $excl_slot ] );
					}

				} elseif ( isset( $slots[ $excl_slot ] ) ) {
					unset( $slots[ $excl_slot ] );
				}

				foreach ( $slots as $slot_start => $slot_data ) {

					if ( $slot_data['from'] <= $excl_slot && $excl_slot < $slot_data['to'] ) {

						if ( $manage_capacity && $slot_count >= $service_count ) {
							unset( $slots[ $slot_start ] );
						} elseif ( $manage_capacity ) {
							$slots[ $slot_start ]['slot_count'] = $slot_count;
						} elseif ( ! $manage_capacity ) {
							unset( $slots[ $slot_start ] );
						}

					} elseif ( $slot_data['from'] < $excl_slot_end && $excl_slot_end <= $slot_data['to'] ) {

						if ( $manage_capacity && $slot_count >= $service_count ) {
							unset( $slots[ $slot_start ] );
						} elseif ( $manage_capacity ) {
							$slots[ $slot_start ]['slot_count'] = $slot_count;
						} elseif ( ! $manage_capacity ) {
							unset( $slots[ $slot_start ] );
						}

					}

				}

			}
		}

		if ( empty( $slots ) ) {
			Plugin::instance()->db->excluded_dates->insert( array(
				'service'  => $service,
				'provider' => $provider,
				'date'     => $date,
			) );
		}

		return $slots;

	}

	/**
	 * Returns names of excluded week days
	 *
	 * @return [type] [description]
	 */
	public function get_available_week_days( $service = null, $provider = null ) {
		$working_hours = Plugin::instance()->settings->get( 'working_hours' );
		$result        = array();

		if ( ! empty( $service ) ) {
			$custom_schedule_days = $this->get_custom_schedule( $service, 'working_hours' );
			$working_hours = ! $custom_schedule_days ? $working_hours : $custom_schedule_days ;
		}

		if ( ! empty( $provider ) ) {
			$custom_schedule_days = $this->get_custom_schedule( $provider, 'working_hours' );
			$working_hours = ! $custom_schedule_days ? $working_hours : $custom_schedule_days ;
		}

		foreach ( $working_hours as $week_day => $schedule ) {
			if ( ! empty( $schedule ) ) {
				$result[] = $week_day;
			}
		}

		return $result;
	}

	/**
	 * Returns week days list
	 *
	 * @return [type] [description]
	 */
	public function get_week_days() {
		return array(
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
		);
	}

	/**
	 * Returns excluded dates - official days off and booked dates
	 *
	 * @return [type] [description]
	 */
	public function get_off_dates( $service = null, $provider = null ) {
		$result     = array();
		$days_off   = Plugin::instance()->settings->get( 'days_off' );
		$query_args = array(
			'date>=' => strtotime( 'today midnight' ),
		);

		if ( ! empty( $service ) ) {
			if( 'service' === Plugin::instance()->settings->get( 'check_by' ) ){
				$query_args['service'] = $service;
			}

			$custom_schedule_days = $this->get_custom_schedule( $service, 'days_off' );
			$days_off = ! $custom_schedule_days ? $days_off : $custom_schedule_days ;
		}

		if ( ! empty( $provider ) ) {
			$query_args['provider'] = $provider;

			$custom_schedule_days = $this->get_custom_schedule( $provider, 'days_off' );
			$days_off = ! $custom_schedule_days ? $days_off : $custom_schedule_days ;
		}

		if ( ! empty( $days_off ) ) {
			foreach ( $days_off as $day ) {
				$result[] = [
					'start' => strtotime( $day['start'] ),
					'end' => strtotime( $day['end'] ),
				];
			}
		}

		$excluded = Plugin::instance()->db->excluded_dates->query( $query_args );

		if ( ! empty( $excluded ) ) {
			foreach ( $excluded as $date ) {
				if( ! isset( $date['start'] ) ){
					$date_period = [
						'start' => absint( $date['date'] ),
						'end' => absint( $date['date'] ),
					];

					if ( ! in_array( $date_period, $result ) ){
						$result[] = $date_period;
					}

				}else{
					$result[] = absint( $date['date'] );
				}
			}
		}

		return $result;
	}

	public function get_works_dates( $service = null, $provider = null ) {
		$result       = array();
		$working_days = Plugin::instance()->settings->get( 'working_days' );

		if ( ! empty( $service ) ) {
			$custom_schedule_days = $this->get_custom_schedule( $service, 'working_days' );
			$working_days = ! $custom_schedule_days ? $working_days : $custom_schedule_days ;
		}

		if ( ! empty( $provider ) ) {
			$custom_schedule_days = $this->get_custom_schedule( $provider, 'working_days' );
			$working_days = ! $custom_schedule_days ? $working_days : $custom_schedule_days ;
		}

		if ( ! empty( $working_days ) ) {
			foreach ( $working_days as $day ) {
				$result[] = [
					'start' => strtotime( $day['start'] ),
					'end'   => strtotime( $day['end'] ),
				];
			}
		}

		return $result;
	}

	public function get_custom_schedule( $post_id, $meta_key, $old_meta_key = false ){
		$result    = null;
		$post_meta = get_post_meta( $post_id, 'jet_apb_post_meta', true );

		if( ! isset( $post_meta[ 'custom_schedule' ] ) || ! $post_meta[ 'custom_schedule' ][ 'use_custom_schedule' ] ){
			return $result;
		}

		if ( isset( $post_meta[ 'custom_schedule' ][ $meta_key ] ) ){
			$date = $post_meta[ 'custom_schedule' ][ $meta_key ];
			$result = ( '' === $date || null === $date ) ? $result : $date ;
		}

		if( null === $result && $old_meta_key ){
			$result = get_post_meta( $post_id, $old_meta_key, true );
		}

		return '' === $result || null === $result ? null : $result ;
	}
}

