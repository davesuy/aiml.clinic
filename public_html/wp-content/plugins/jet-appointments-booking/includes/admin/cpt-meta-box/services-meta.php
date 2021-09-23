<?php
/**
 * Uses JetEngine meta component to process meta
 */
namespace JET_APB\Admin\Cpt_Meta_Box;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Services_Meta extends Base_Vue_Meta_Box {

	/**
	 * Default settings array
	 *
	 * @var array
	 */
	protected $defaults;

	/**
	 * Class constructor
	 */
	public function __construct() {
		parent::__construct( Plugin::instance()->settings->get( 'services_cpt' ) );

		// Needed for backward compatibility.
		$service_duration = isset( $_GET['post'] ) ? get_post_meta( $_GET['post'], '_service_duration', true ) : 1800 ;
		$buffer_before    = isset( $_GET['post'] ) ? get_post_meta( $_GET['post'], '_buffer_before', true ) : 0 ;
		$buffer_after     = isset( $_GET['post'] ) ? get_post_meta( $_GET['post'], '_buffer_after', true ) : 0 ;

		$this->defaults = [
			'custom_schedule' =>[
				'use_custom_schedule' => false,
				'default_slot'        => $service_duration,
				'buffer_before'       => $buffer_before,
				'buffer_after'        => $buffer_after,
				'working_hours'       =>[
					'monday'    => [
						[ 'from' => '08:00', 'to' => '17:00' ]
					],
					'tuesday'   => [
						[ 'from' => '08:00', 'to' => '17:00' ]
					],
					'wednesday' => [
						[ 'from' => '08:00', 'to' => '17:00' ]
					],
					'thursday'  => [
						[ 'from' => '08:00', 'to' => '17:00' ]
					],
					'friday'    => [
						[ 'from' => '08:00', 'to' => '17:00' ]
					],
					'saturday'  => [],
					'sunday'    => [],
				],
				'days_off'            => [],
				'working_days'        => [],
				'weekdays'   => [
					'monday'    => esc_html__( 'Monday', 'jet-appointments-booking' ),
					'tuesday'   => esc_html__( 'Tuesday', 'jet-appointments-booking' ),
					'wednesday' => esc_html__( 'Wednesday', 'jet-appointments-booking' ),
					'thursday'  => esc_html__( 'Thursday', 'jet-appointments-booking' ),
					'friday'    => esc_html__( 'Friday', 'jet-appointments-booking' ),
					'saturday'  => esc_html__( 'Saturday', 'jet-appointments-booking' ),
					'sunday'    => esc_html__( 'Sunday', 'jet-appointments-booking' ),
				],
			]
		];

		add_action( 'jet-engine/meta-boxes/register-instances', array( $this, 'register_meta_box' ) );
	}

	/**
	 * Regsiter services specific metabox on all services registration
	 *
	 * @param  [type] $meta_boxes_manager [description]
	 * @return [type]                     [description]
	 */
	public function register_meta_box( $meta_boxes_manager ) {
		$services_cpt = Plugin::instance()->settings->get( 'services_cpt' );

		if ( ! $services_cpt ) {
			return;
		}

		$object_name = $services_cpt . '_jet_apb';

		$meta_boxes_manager->register_custom_group(
			$object_name,
			esc_html__( 'Appointments Settings', 'jet-appointments-booking' )
		);

		$meta_fields = array(
			array(
				'type'             => 'text',
				'name'             => '_app_price',
				'title'            => esc_html__( 'Price per slot', 'jet-appointments-booking' ),
			),
		);

		$manage_capacity = Plugin::instance()->settings->get( 'manage_capacity' );

		if ( $manage_capacity ) {
			$meta_fields[] = array(
				'type'        => 'text',
				'input_type'  => 'number',
				'default_val' => 1,
				'name'        => '_app_capacity',
				'title'       => esc_html__( 'Capacity', 'jet-appointments-booking' ),
			);
		}

		$meta_boxes_manager->register_metabox(
			$services_cpt,
			$meta_fields,
			esc_html__( 'Appointments Settings', 'jet-appointments-booking' ),
			$object_name
		);
	}

	/**
	 * Return default value.
	 *
	 * @return [array] [description]
	 */
	public function meta_box_default_value() {
		return $this->defaults;
	}

	/**
	 * Add a meta box to post.
	 */
	public function add_meta_box(){

		if ( ! $this->is_cpt_page() ) {
			return;
		}

		add_meta_box(
			'schedule_meta_box',
			esc_html__( 'Custom Schedule', 'jet-appointments-booking' ),
			[ $this, 'custom_schedule_meta_box_callback' ],
			[ $this->current_screen_slug ],
			'normal',
			'low'
		);
	}

	/**
	 * Require metabox html.
	 */
	public function custom_schedule_meta_box_callback(){
		require_once( JET_APB_PATH .'templates/admin/custom-schedule-meta-box.php' );
	}

	/**
	 * Page components templates
	 *
	 * @return [type] [description]
	 */
	public function vue_templates() {
		return [
			[
				'dir'  => 'jet-apb-settings',
				'file' => 'settings-working-hours',
			]
		];
	}

	/**
	 * Parsed data before written to the database and after get from the database.
	 *
	 * @return [array] [description]
	 */
	public function parse_settings( $settings ){
		$new_settings = $settings['custom_schedule'];

		foreach ( $new_settings as $setting => $value ) {

			switch ( $setting ) {
				case 'working_hours':
					$new_settings[ $setting ] = $this->sanitize_working_hours( $value );
					break;

				case 'working_days':
				case 'days_off':
					if ( ! is_array( $value ) ) {
						$new_settings[ $setting ] = false;
					}
					break;

				case 'use_custom_schedule':
					$new_settings[ $setting ] = filter_var( $value, FILTER_VALIDATE_BOOLEAN );

					break;

				case 'time_slots':
					$new_settings[ $setting ] = false;

				break;

				case 'default_slot':
				case 'buffer_before':
				case 'buffer_after':
					$new_settings[ $setting ] = intval( $value );

					break;

				default:
					$new_settings[ $setting ] = $value;
				break;
			}
		}

		$placeholder = [
			'value' => '-1',
			'label' =>  esc_html__( 'Select Time', 'jet-appointments-booking' )
		];

		$settings[ 'custom_schedule' ] = $new_settings;

		return $settings;
	}

	/**
	 * Include scripts and styles
	 */
	public function assets() {
		if ( ! $this->is_cpt_page() ) {
			return;
		}

		//Enqueue script
		wp_enqueue_script( 'cx-vue' );
		wp_enqueue_script( 'momentjs' );
		wp_enqueue_script( 'vuejs-datepicker' );

		//Enqueue style
		wp_enqueue_style( 'jet-apb-working-hours' );
		wp_enqueue_style( 'jet-appointments-booking-admin' );
	}
}