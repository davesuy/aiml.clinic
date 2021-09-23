<?php
namespace JET_APB\Admin;

use JET_APB\Plugin;

/**
 * Settings manager
 */
class Settings {

	/**
	 * Default settings array
	 *
	 * @var array
	 */
	private $defaults = [
			'is_set'        => false,
			'services_cpt'  => '',
			'providers_cpt' => '',
			'working_hours' => [
				'monday'    => [],
				'tuesday'   => [],
				'wednesday' => [],
				'thursday'  => [],
				'friday'    => [],
				'saturday'  => [],
				'sunday'    => [],
			],
			'working_days'   => [],
			'days_off'       => [],
			'db_columns'     => [],
			'wc_integration' => false,
			'wc_product_id'  => false,
			'hide_setup'     => false,
			'default_buffer_before' => 0,
			'default_buffer_after'  => 0,
			'default_slot'          => 1800,
			'check_by' => 'global',
			'manage_capacity' => false,
			'show_capacity_counter' => false,
			'use_custom_labels' => false,
			'slot_time_format' => 'H:i',
			'custom_labels' => [
				'Sun' => 'Sun',
				'Mon' => 'Mon',
				'Tue' => 'Tue',
				'Wed' => 'Wed',
				'Thu' => 'Thu',
				'Fri' => 'Fri',
				'Sat' => 'Sat',
				'January' => 'January',
				'February' => 'February',
				'March' => 'March',
				'April' => 'April',
				'May' => 'May',
				'June' => 'June',
				'July' => 'July',
				'August' => 'August',
				'September' => 'September',
				'October' => 'October',
				'November' => 'November',
				'December' => 'December',
			],
		];

	/**
	 * Settings DB key
	 *
	 * @var string
	 */
	private $key = 'jet-apb-settings';

	/**
	 * Stored settings cache
	 *
	 * @var null
	 */
	public $settings = null;

	/**
	 * [__construct description]
	 * @param array $pages [description]
	 */
	public function __construct() {
		add_action( 'wp_ajax_jet_apb_save_settings', array( $this, 'ajax_save_settings' ) );
		add_action( 'wp_ajax_jet_apb_clear_excluded', array( $this, 'reset_excluded_dates' ) );
	}

	/**
	 * Reset excluded dates data
	 *
	 * @return [type] [description]
	 */
	public function reset_excluded_dates() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => 'Access denied',
			) );
		}

		Plugin::instance()->db->excluded_dates->clear();

	}

	/**
	 * Save settings by ajax request
	 *
	 * @return [type] [description]
	 */
	public function ajax_save_settings() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array(
				'message' => 'Access denied',
			) );
		}

		$settings          = ! empty( $_REQUEST['settings'] ) ? wp_parse_args( $_REQUEST['settings'], $this->defaults ) : [];
		$update_db_columns = ! empty( $_REQUEST['update_db_columns'] ) ? $_REQUEST['update_db_columns'] : false;
		$update_db_columns = filter_var( $update_db_columns, FILTER_VALIDATE_BOOLEAN );

		if ( empty( $settings ) ) {
			wp_send_json_error( array(
				'message' => 'Empty data',
			) );
		}
		foreach ( $settings as $setting => $value ) {
			if ( $this->setting_registered( $setting ) ) {


				switch ( $setting ) {

					case 'working_hours':
						$value = $this->sanitize_working_hours( $value );
						break;

					case 'working_days':
					case 'days_off':

						if ( ! is_array( $value ) ) {
							$value = false;
						}

						break;

					case 'wc_integration':
					case 'hide_setup':
					case 'manage_capacity':
					case 'show_capacity_counter':
					case 'use_custom_labels':
						$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
						break;

					case 'db_columns':

						$old_columns = $this->get( 'db_columns' );

						if ( $update_db_columns ) {
							$value = $this->process_columns_diff( $value, $old_columns );
						} else {
							$value = $old_columns;
						}

						break;

				}

				$this->update( $setting, $value, false );

			}
		}

		$this->write();

		wp_send_json_success( array(
			'message' => __( 'Settings saved!', 'jet-appointments-booking' ),
		) );

	}

	/**
	 * The function processes data before localization. Added in version 1.2.0. Remove in version 1.5.0.
	 * @param  [type] $settings [description]
	 * @return [type]           [description]
	 */
	public function passe_settings( $settings ) {

		if ( empty( $settings ) ) {
			return $settings;
		}

		if ( isset( $settings['days_off'] ) && ! empty( $settings['days_off'] ) ) {
			$new_days_off = [];

			foreach ( $settings['days_off'] as $value ) {

				if( ! isset( $value["date"] ) ){
					$new_days_off[] = $value;
					continue;
				}

				$start          = $value["date"];
				$startTimeStamp = strtotime( $start );
				$new_days_off[] = [
					'start'          => $start,
					'startTimeStamp' => $startTimeStamp,
					'end'            => $start,
					'endTimeStamp'   => $startTimeStamp,
					'name'           => $value["name"],
					'type'           => 'days_off',
				];

			}

			$settings['days_off'] = $new_days_off;
		}

		return $settings;
	}

	/**
	 * Process columns difference and returns santizzed new columns list
	 * @param  [type] $new_columns [description]
	 * @param  [type] $old_columns [description]
	 * @return [type]              [description]
	 */
	public function process_columns_diff( $new_columns, $old_columns ) {

		$new_columns = $this->sanitize_columns( $new_columns );

		$to_delete = array_diff( $old_columns, $new_columns );
		$to_add    = array_diff( $new_columns, $old_columns );

		if ( ! empty( $to_delete ) ) {
			Plugin::instance()->db->appointments->delete_table_columns( $to_delete );
		}

		if ( ! empty( $to_add ) ) {
			Plugin::instance()->db->appointments->insert_table_columns( $to_add );
		}

		return $new_columns;

	}

	/**
	 * Sanitize SQL table columns list names
	 *
	 * @param  array  $columns [description]
	 * @return [type]          [description]
	 */
	public function sanitize_columns( $columns = [] ) {

		if ( empty( $columns ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( array_filter( $columns ) as $column ) {
			$sanitized[] = $this->sanitize_column( $column );
		}

		return $sanitized;

	}

	/**
	 * Sanitize single DB column
	 * @param  [type] $column [description]
	 * @return [type]         [description]
	 */
	public function sanitize_column( $column ) {
		return sanitize_key( str_replace( ' ', '_', $column ) );
	}

	/**
	 * Sanitize updated working hours
	 *
	 * @param  [type] $value [description]
	 * @return [type]        [description]
	 */
	public function sanitize_working_hours( $input ) {
		$defaults  = $this->defaults['working_hours'];
		$sanitized = [];

		foreach ( $defaults as $key => $default_value ) {
			$sanitized[ $key ] = ! empty( $input[ $key ] ) ? $input[ $key ] : $default_value;
		}

		return $sanitized;
	}

	/**
	 * Return all settings and setup settings cache
	 *
	 * @return [type] [description]
	 */
	public function get_all() {

		if ( null === $this->settings ) {
			$this->settings = get_option( $this->key, [] );
			$this->settings = wp_parse_args( $this->settings, $this->defaults );
			$this->settings = $this->passe_settings( $this->settings );

			if ( empty( $this->settings['custom_labels'] ) ) {
				$this->settings['custom_labels'] = $this->defaults['custom_labels'];
			}

		}

		return $this->settings;
	}

	/**
	 * Get setting by name
	 *
	 * @param  [type] $setting [description]
	 * @return [type]          [description]
	 */
	public function get( $setting ) {
		$settings = $this->get_all();

		if ( isset( $settings[ $setting ] ) ) {
			return $settings[ $setting ];
		} else {
			return isset( $this->defaults[ $setting ] ) ? $this->defaults[ $setting ] : null;
		}

	}

	/**
	 * Update setting in cahce and database
	 *
	 * @param  [type]  $setting [description]
	 * @param  boolean $write   [description]
	 * @return [type]           [description]
	 */
	public function update( $setting = null, $value = null, $write = true ) {

		$this->get_all();

		/**
		 * Modify options before write into DB
		 */
		do_action( 'jet-apb/settings/before-update', $this->settings, $setting, $value );

		$this->settings[ $setting ] = $value;

		if ( $write ) {
			$this->write();
		}

	}

	/**
	 * Write settings cache
	 * @return [type] [description]
	 */
	public function write() {

		/**
		 * Modify options before write into DB
		 */
		do_action( 'jet-apb/settings/before-write', $this );

		update_option( $this->key, $this->settings, false );
	}

	/**
	 * Check if passed settings is registered in defaults
	 *
	 * @return [type] [description]
	 */
	public function setting_registered( $setting = null ) {
		return ( $setting && isset( $this->defaults[ $setting ] ) );
	}

}
