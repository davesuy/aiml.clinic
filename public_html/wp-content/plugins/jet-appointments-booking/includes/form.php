<?php
namespace JET_APB;

/**
 * Form controls and notifications class
 */
class Form {

	/**
	 * Check if date field is already rendered
	 *
	 * @var boolean
	 */
	public $date_done = false;

	/**
	 * Check if provider field is already rendered
	 *
	 * @var boolean
	 */
	public $provider_done = false;

	/**
	 * Check if provider field is already rendered
	 *
	 * @var boolean
	 */
	public $appointment_data = false;

	/**
	 * Constructor for the class
	 */
	public function __construct() {
		new Form_Widget();

		add_filter(
			'jet-engine/forms/booking/field-types',
			array( $this, 'register_form_fields' )
		);

		add_action(
			'jet-engine/forms/booking/field-template/appointment_date',
			array( $this, 'date_field_template' ),
			10, 3
		);

		add_action(
			'jet-engine/forms/booking/field-template/appointment_provider',
			array( $this, 'provider_field_template' ),
			10, 3
		);

		add_filter(
			'jet-engine/forms/booking/notification-types',
			array( $this, 'register_notification' )
		);

		add_action(
			'jet-engine/forms/edit-field/before',
			array( $this, 'edit_fields' )
		);

		add_action(
			'jet-engine/forms/booking/notifications/fields-after',
			array( $this, 'notification_fields' )
		);

		add_filter(
			'jet-engine/forms/booking/notification/insert_appointment',
			array( $this, 'handle_notification' ),
			1, 2
		);

		add_filter(
			'jet-engine/forms/gateways/notifications-before',
			array( $this, 'before_form_gateway' ), 1, 2
		);

		add_action(
			'jet-engine/forms/gateways/on-payment-success',
			array( $this, 'on_gateway_success' ), 10, 3
		);

		add_action(
			'jet-engine/forms/notifications/control_description',
			array( $this, 'appointments_macros' ), 10
		);

		add_filter(
			'jet-engine/forms/booking/email/message_content',
			array( $this, 'parse_appointments_macros' ),
			2, 10
		);

		add_action(
			'elementor/preview/enqueue_scripts',
			array( $this, 'calendar_assets' )
		);

		add_action(
			'jet-engine/forms/booking/after-start-form',
			[ $this, 'add_hidden_inputs' ]
		);

		add_action(
			'jet-apb/form/notification/success',
			[ $this, 'appointments_form_success' ]
		);

	}

	public function appointments_form_success($value){
		$this->appointment_data = $value;
	}

	/**
	 * The function added macros to the form editor.
	 */
	public function appointments_macros(){
		?>
		<div>
			<strong><?php esc_html_e( 'Appointment Macros:', 'jet-appointments-booking' ) ?></strong>
		</div>
		<div>
			- <strong>%service_title%</strong> - <?php esc_html_e( 'Name of the appointment service.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%provider_title%</strong> - <?php esc_html_e( 'Name of the appointment provider.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%service_link%</strong> - <?php esc_html_e( 'Link of the appointment service.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%provider_link%</strong> - <?php esc_html_e( 'Link of the appointment provider.', 'jet-appointments-booking' ) ?>
		</div>
		<div>
			- <strong>%appointment_start%</strong> - <?php printf( esc_html__( 'Displays the date and time the appointment started. You can set the format for the date. %1$sRead more%2$s about the time format. For example: %3$s%%appointment_start|format_date(F j, Y g:i)%%%4$s', 'jet-appointments-booking' ), '<a href="https://wordpress.org/support/article/formatting-date-and-time" >', '</a>', '<strong>', '</strong>' ) ?>
		</div>
		<div>
			- <strong>%appointment_end%</strong> - <?php esc_html_e( 'Displays the date and time the appointment end. Also accepts date format.', 'jet-appointments-booking' ) ?>
		</div>
		<?php
	}

	/**
	 * The function added macros to the form editor.
	 */
	public function add_hidden_inputs($class_instant){
		$appointment_field = false;

		foreach ($class_instant->fields as $field) {
			if( 'appointment_date' === $field['settings']['type'] ){
				$appointment_field = $field['settings'];
				break;
			}
		}

		if( ! $appointment_field ){
			return;
		}

		$input_data = [];
		$service_id = $this->get_service_field_data( $appointment_field );
		$input_data['service_id'] = $service_id["id"];

		if( Plugin::instance()->settings->get( 'providers_cpt' ) ){
			$provider_id = $this->get_provider_field_data( $appointment_field );
			$input_data['provider_id'] = $provider_id["id"];
		}

		foreach ( $input_data as $key => $value ) {
			if( $value ){
				printf(
					'<input class="jet-form__field hidden-field" type="hidden" name="_jet_engine_booking_%1$s" value="%2$s" data-field-name="_jet_engine_booking_%1$s">',
					$key,
					$value
				);
			}
		}
	}
	/**
	 * The function processes macros before sending an email.
	 *
	 * @param  [type] $message_content [description]
	 * @param  [type] $class_instant   [description]
	 * @return [type]                  [description]
	 */
	public function parse_appointments_macros( $message_content, $class_instant ) {
		$message_content = preg_replace_callback( '/\%(([a-zA-Z0-9_-]+)(\|([a-zA-Z0-9\(\)\.\,\:\/\s_-]+))*)\%/', function( $match ) use ( $class_instant ) {
			switch ( $match[2] ) {
				case 'service_title':
					$ID = $this->appointment_data['service'];

					return get_the_title( $ID );

				case 'service_link':
					$ID = $this->appointment_data['service'];

					return get_permalink( $ID );

				case 'provider_title':
					$ID = $this->appointment_data['provider'];

					return get_the_title( $ID );

				case 'provider_link':
					$ID = $this->appointment_data['provider'];

					return get_permalink( $ID );

				case 'appointment_start':
				case 'appointment_end':
					$format = ( $match[3] ) ? $match[3] : 'format_date(F j, Y g:i)' ;
					$value  = 'appointment_end' === $match[2] ? $this->appointment_data[ 'slot_end' ] : $this->appointment_data[ 'slot' ] ;
					$slot   = jet_engine()->listings->filters->apply_filters( $value, $format );

				return $slot;

				default:
					return $match[0];

			}
		}, $message_content );

		return $message_content;
	}

	/**
	 * The function returns the field names from the form.
	 * @param  [type] $fields       [description]
	 * @param  [type] $field_type   [description]
	 * @param  [type] $setting_name [description]
	 * @return [type]               [description]
	 */
	public function get_field_value( $fields, $field_type, $setting_name ){
		$fields_type = array_column( $fields, 'name', 'type' );
		$field_value    = '';

		if( isset( $fields_type[ $field_type ] ) ){
			$field_name = $fields_type[ $field_type ];
			$field_value    = isset( $fields[ $field_name ][ $setting_name ] ) ? $fields[ $field_name ][ $setting_name ] : $field_value ;
		}

		return $field_value;
	}

	/**
	 * Set bookng appointment notification before gateway
	 *
	 * @param  [type] $keep [description]
	 * @param  [type] $all  [description]
	 * @return [type]       [description]
	 */
	public function before_form_gateway( $keep, $all ) {

		foreach ( $all as $index => $notification ) {
			if ( 'insert_appointment' === $notification['type'] && ! in_array( $index, $keep ) ) {
				$keep[] = $index;
			}
		}

		return $keep;

	}

	/**
	 * Finalize booking on internal JetEngine form gateway success
	 *
	 * @return [type] [description]
	 */
	public function on_gateway_success( $form_id, $settings, $form_data ) {

		$appointment_id = isset( $form_data['appointment_id'] ) ? $form_data['appointment_id'] : false;

		if ( ! $appointment_id ) {
			return;
		}

		$appointment = Plugin::instance()->db->get_appointment_by( 'ID', $appointment_id );

		if ( ! $appointment ) {
			return;
		}

		Plugin::instance()->db->appointments->update(
			array( 'status' => 'completed', ),
			array( 'ID' => $appointment_id, )
		);

		Plugin::instance()->db->maybe_exclude_appointment_date( $appointment );

	}

	/**
	 * Handle apartment booking notification
	 *
	 * @return [type] [description]
	 */
	public function handle_notification( $args, $notifications ) {

		$service_field  = ! empty( $args['appointment_service_field'] ) ? $args['appointment_service_field'] : false;
		$provider_field = ! empty( $args['appointment_provider_field'] ) ? $args['appointment_provider_field'] : false;
		$date_field     = ! empty( $args['appointment_date_field'] ) ? $args['appointment_date_field'] : false;
		$email_field    = ! empty( $args['appointment_email_field'] ) ? $args['appointment_email_field'] : false;
		$data           = $notifications->data;

		if ( ! $service_field || ! $date_field || ! $email_field ) {
			$notifications->log[] = false;
			return false;
		}

		if ( '_manual_input' === $service_field ) {
			$service_id = ! empty( $args['appointment_service_id'] ) ? absint( $args['appointment_service_id'] ) : 0;
		} else {
			$service_id = ! empty( $data[ $service_field ] ) ? $data[ $service_field ] : false;
		}

		$date        = ! empty( $data[ $date_field ] ) ? $data[ $date_field ] : false;
		$email       = ! empty( $data[ $email_field ] ) ? $data[ $email_field ] : false;
		$provider_id = false;

		if ( ! $service_id || ! $date || ! $email || ! is_email( $email ) ) {
			$notifications->log[] = false;
			return false;
		}

		if ( $provider_field ) {

			if ( '_manual_input' === $provider_field ) {
				$provider_id = ! empty( $args['appointment_provider_id'] ) ? absint( $args['appointment_provider_id'] ) : 0;
			} else {
				$provider_id = ! empty( $data[ $provider_field ] ) ? $data[ $provider_field ] : false;
			}

			if ( ! $provider_id ) {
				$notifications->log[] = false;
				return false;
			}

		}

		$date = explode( '|', $date );
		$day  = $date[0];
		$slot = $date[1];

		$human_read_date = sprintf(
			'%1$s, %2$s',
			date_i18n( get_option( 'date_format' ), $day ),
			date_i18n( get_option( 'time_format' ), $slot )
		);

		$notifications->handler->form_data[ $date_field ] = $human_read_date;
		$notifications->data[ $date_field ]               = $human_read_date;

		$duration = Plugin::instance()->settings->get( 'default_slot' );

		if ( $service_id ) {
			$meta     = get_post_meta( $service_id, 'jet_apb_post_meta', true );
			$duration = isset( $meta[ 'custom_schedule' ] ) && $meta[ 'custom_schedule' ][ 'use_custom_schedule' ] ? $meta[ 'custom_schedule' ][ 'default_slot' ] : $duration ;
		}

		if ( $provider_id ) {
			$meta     = get_post_meta( $provider_id, 'jet_apb_post_meta', true );
			$duration = isset( $meta[ 'custom_schedule' ] ) && $meta[ 'custom_schedule' ][ 'use_custom_schedule' ] ? $meta[ 'custom_schedule' ][ 'default_slot' ] : $duration ;
		}

		$duration = absint( $duration );
		$slot_end = $slot + $duration;

		$new_appointment = array(
			'service'    => $service_id,
			'provider'   => $provider_id,
			'user_email' => sanitize_email( $email ),
			'date'       => $day,
			'slot'       => $slot,
			'slot_end'   => $slot_end,
		);

		$db_columns = Plugin::instance()->settings->get( 'db_columns' );

		if ( ! empty( $db_columns ) ) {
			foreach ( $db_columns as $column ) {

				$arg        = 'appointment_custom_field_' . $column;
				$field_name = ! empty( $args[ $arg ] ) ? $args[ $arg ] : false;

				$new_appointment[ $column ] = ! empty( $data[ $field_name ] ) ? esc_attr( $data[ $field_name ] ) : '';
			}
		}

		if ( Plugin::instance()->wc->get_status() && Plugin::instance()->wc->get_product_id() ) {
			$new_appointment['status'] = 'on-hold';
		} elseif ( $notifications->handler->has_gateway() ) {

			$new_appointment['status']   = 'on-hold';

			if ( ! empty( $notifications->handler->form_data['inserted_post_id'] ) ) {
				$new_appointment['order_id'] = $notifications->handler->form_data['inserted_post_id'];
			}

		}

		if ( ! Plugin::instance()->db->appointment_available( $new_appointment ) ) {
			$notifications->log[] = $notifications->set_specific_status( 'Appointment time already taken' );
			return false;
		}

		$appointment_id = Plugin::instance()->db->add_appointment( $new_appointment );

		$notifications->handler->form_data['appointment_id'] = $appointment_id;
		$notifications->data['appointment_id']               = $appointment_id;

		do_action( 'jet-apb/form/notification/success', array(
			'ID'         => $appointment_id,
			'service'    => $service_id,
			'provider'   => $provider_id,
			'slot'       => $slot,
			'slot_end'   => $slot_end,
			'date'       => $day,
		), $notifications, $args );

		$notifications->log[] = true;

	}

	/**
	 * Register new notification type
	 *
	 * @return [type] [description]
	 */
	public function register_notification( $notifications ) {
		$notifications['insert_appointment'] = __( 'Insert appointment' );
		return $notifications;
	}

	/**
	 * Render additional edit fields
	 *
	 * @return [type] [description]
	 */
	public function edit_fields() {
		include JET_APB_PATH . 'templates/admin/form/edit-fields.php';
	}

	/**
	 * Render additional notification fields
	 *
	 * @return [type] [description]
	 */
	public function notification_fields() {

		$additional_db_columns = Plugin::instance()->settings->get( 'db_columns' );
		$wc_integration        = Plugin::instance()->settings->get( 'wc_integration' );

		if( $wc_integration ){
			$checkout_fields = Plugin::instance()->wc->get_checkout_fields();
		}

		include JET_APB_PATH . 'templates/admin/form/notification-fields.php';
	}

	/**
	 * Register new dates fields
	 *
	 * @return [type] [description]
	 */
	public function register_form_fields( $fields ) {

		$fields['appointment_date']     = __( 'Appointment date', 'jet-appointments-booking' );
		$fields['appointment_provider'] = __( 'Appointment provider', 'jet-appointments-booking' );

		return $fields;

	}

	public function enqueue_deps( $listing_id ) {

		if ( ! $listing_id ) {
			return;
		}

		$document      = \Elementor\Plugin::$instance->documents->get( $listing_id );
		$elements_data = $document->get_elements_raw_data();

		$this->enqueue_elements_deps( $elements_data );

	}

	public function enqueue_elements_deps( $elements_data ) {

		foreach ( $elements_data as $element_data ) {

			if ( 'widget' === $element_data['elType'] ) {

				$widget = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element_data );

				$widget_script_depends = $widget->get_script_depends();
				$widget_style_depends  = $widget->get_style_depends();

				if ( ! empty( $widget_script_depends ) ) {
					foreach ( $widget_script_depends as $script_handler ) {
						wp_enqueue_script( $script_handler );
					}
				}

				if ( ! empty( $widget_style_depends ) ) {
					foreach ( $widget_style_depends as $style_handler ) {
						wp_enqueue_style( $style_handler );
					}
				}

			} else {

				$element  = \Elementor\Plugin::$instance->elements_manager->create_element_instance( $element_data );
				$children = $element->get_children();

				foreach ( $children as $key => $child ) {
					$children_data[ $key ] = $child->get_raw_data();
					$this->enqueue_elements_deps( $children_data );
				}
			}
		}

	}

	/**
	 * Render provider field tempalte
	 *
	 * @return [type] [description]
	 */
	public function provider_field_template( $template, $args, $builder ) {

		if ( ! $this->provider_done ) {

			add_action( 'wp_footer', function() {

				ob_start();
				include JET_APB_PATH . 'assets/js/public/providers-init.js';
				$init_script = ob_get_clean();

				printf( '<script>%s</script>', $init_script );

			}, 99 );

			if ( wp_doing_ajax() ) {

				$init_script = "var JetAPBisAjax = true;\n";

				ob_start();
				include JET_APB_PATH . 'assets/js/public/providers-init.js';
				$init_script .= ob_get_clean();

				printf( '<script>%s</script>', $init_script );

			}

			$this->provider_done = true;
		}

		$service_data = $this->get_service_field_data( $args );
		$placeholder  = ! empty( $args['default'] ) ? $args['default'] : __( 'Select...', 'jet-appointments-booking' );

		$dataset = array(
			'service'     => $service_data['form_field'],
			'api'         => Plugin::instance()->rest_api->get_urls(),
			'placeholder' => $placeholder,
		);

		$is_required = $builder->get_required_val( $args );
		$args_str    = 'name="' . $args['name'] . '"';

		if ( $is_required ) {
			$args_str .= ' required';
		}

		$providers_list = '';
		$providers      = array();

		if ( ! empty( $service_data['id'] ) ) {
			$providers = Plugin::instance()->tools->get_providers_for_service( $service_data['id'] );

			if ( ! empty( $providers ) ) {
				$providers_list .= '<option value="">' . $placeholder . '</option>';
				foreach ( $providers as $provider ) {
					$providers_list .= '<option value="' . $provider->ID . '">' . $provider->post_title . '</option>';
				}
			}

		}

		if ( ! empty( $args['switch_on_change'] ) ) {
			$args_str .= ' data-switch="1"';
		}

		$custom_template = ! empty( $args['appointment_provider_custom_template'] ) ? $args['appointment_provider_custom_template'] : false;
		$custom_template_id = ! empty( $args['appointment_provider_custom_template_id'] ) ? $args['appointment_provider_custom_template_id'] : false;

		if ( $custom_template && $custom_template_id ) {

			$options                         = '';
			$args['custom_item_template_id'] = $custom_template_id;
			$default                         = ! empty( $args['default'] ) ? $args['default'] : false;
			$checked                         = '';

			$this->enqueue_deps( $custom_template_id );
			jet_engine()->frontend->set_listing( $custom_template_id );
			$css_file = new \Elementor\Core\Files\CSS\Post( $custom_template_id );
			$css_file->enqueue();

			foreach ( $providers as $provider ) {
				$custom_template = $builder->get_custom_template( $provider->ID, $args );
				$data_switch     = null;

				if ( $default ) {
					$checked = checked( $default, $provider->ID, false );
				}

				ob_start();
				?>
				<div class="jet-form__field-wrap radio-wrap checkradio-wrap">
					<?php if ( $custom_template ) {
						echo $custom_template;
					} ?>
					<label class="jet-form__field-label">
						<input
							type="radio"
							class="jet-form__field radio-field checkradio-field"
							value="<?php echo $provider->ID; ?>"
							<?php echo $checked; ?>
							<?php echo $args_str; ?>
							<?php echo $data_switch; ?>
						>
					</label>
				</div>
				<?php

				$options .= ob_get_clean();

			}

			wp_reset_postdata();
			wp_reset_query();

			$dataset['custom_template'] = $custom_template_id;
			$dataset['args_str']        = $args_str;

			$loader = '<div class="appointment-provider__loader appointment-provider__loader-hidden"><svg width="38" height="38" viewBox="0 0 38 38" xmlns="http://www.w3.org/2000/svg" stroke="rgba( 0, 0, 0, .3 )"><g fill="none" fill-rule="evenodd"><g transform="translate(1 1)" stroke-width="2"><circle stroke-opacity=".5" cx="18" cy="18" r="18"/><path d="M36 18c0-9.94-8.06-18-18-18"><animateTransform attributeName="transform" type="rotate" from="0 18 18" to="360 18 18" dur="1s" repeatCount="indefinite"/></path></g></g></svg></div>';

			return sprintf(
				'<div class="appointment-provider jet-form__fields-group checkradio-wrap" data-args="%1$s"><div class="appointment-provider__content">%2$s</div>%3$s</div>',
				htmlspecialchars( json_encode( $dataset ) ),
				$options,
				$loader
			);

		} else {
			return sprintf(
				'<select class="appointment-provider jet-form__field select-field" %2$s data-args="%1$s">%3$s</select>',
				htmlspecialchars( json_encode( $dataset ) ),
				$args_str,
				$providers_list
			);
		}

	}

	public function calendar_assets() {

		ob_start();
		include JET_APB_PATH . 'assets/js/public/appointments-init.js';
		$init_script = ob_get_clean();

		$handle = 'vanilla-calendar';

		wp_enqueue_script(
			$handle,
			JET_APB_URL . 'assets/js/public/vanilla-calendar.js',
			array( 'jquery' ),
			JET_APB_VERSION,
			true
		);

		wp_add_inline_script( $handle, $init_script );

		$custom_labels = false;
		$use_custom_labels = Plugin::instance()->settings->get( 'use_custom_labels' );

		$data = array(
			'api'                 => Plugin::instance()->rest_api->get_urls(),
			'css'                 => JET_APB_URL . 'assets/css/vanilla-calendar.css',
			'week_days'           => Plugin::instance()->calendar->get_week_days(),
			'start_of_week'       => get_option( 'start_of_week', 1 ),
			'available_week_days' => Plugin::instance()->calendar->get_available_week_days(),
		);

		if ( $use_custom_labels ) {

			$custom_labels = Plugin::instance()->settings->get( 'custom_labels' );

			$days = array(
				'Sun' => 'Sun',
				'Mon' => 'Mon',
				'Tue' => 'Tue',
				'Wed' => 'Wed',
				'Thu' => 'Thu',
				'Fri' => 'Fri',
				'Sat' => 'Sat',
			);

			$months = array(
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
			);

			foreach ( $days as $day => $day_label ) {
				$days[ $day ] = ! empty( $custom_labels[ $day ] )? $custom_labels[ $day ] : $day_label;
			}

			foreach ( $months as $month => $month_label ) {
				$months[ $month ] = ! empty( $custom_labels[ $month ] )? $custom_labels[ $month ] : $month_label;
			}

			$data['months'] = array_values( $months );
			$data['shortWeekday'] = array_values( $days );

		}

		wp_localize_script( $handle, 'JetAPBData', $data );

		if ( wp_doing_ajax() ) {

			$localized_data = 'var JetAPBData = ' . wp_json_encode( $data ) . ';';
			$src            = add_query_arg(
				array( 'ver' => JET_APB_VERSION ),
				JET_APB_URL . 'assets/js/public/vanilla-calendar.js'
			);

			printf( "<script>\n%s\n</script>\n", $localized_data );
			printf( "<script src='%s'></script>\n", $src );
			printf( "<script>\n%s\n</script>\n", $init_script );

		}

	}

	/**
	 * Return lift of excluded services
	 * @return [type] [description]
	 */
	public function get_allowed_services() {

		$provider_cpt = Plugin::instance()->settings->get( 'providers_cpt' );

		if ( ! $provider_cpt ) {
			return false;
		}

		if ( ! is_singular( $provider_cpt ) ) {
			return false;
		}

		return Plugin::instance()->tools->get_services_for_provider( get_the_ID() );

	}

	/**
	 * Render field template
	 *
	 * @return [type] [description]
	 */
	public function date_field_template( $template, $args, $builder ) {

		if ( ! $this->date_done ) {
			$this->calendar_assets();
			$this->date_done = true;
		}

		$service_data  = $this->get_service_field_data( $args );
		$provider_data = $this->get_provider_field_data( $args );

		$required = false;
		if ( ! empty( $args['required'] ) && ( 'required' === $args['required'] || true === $args['required'] ) ) {
			$required = true;
		}

		$dataset = array(
			'excludedDates'     => Plugin::instance()->calendar->get_off_dates( $service_data['id'], $provider_data['id'] ),
			'worksDates'        => Plugin::instance()->calendar->get_works_dates( $service_data['id'], $provider_data['id'] ),
			'availableWeekDays' => Plugin::instance()->calendar->get_available_week_days( $service_data['id'], $provider_data['id'] ),
			'service'           => $service_data['form_field'],
			'providerIsset'     => $provider_data['is_set'],
			'provider'          => $provider_data['form_field'],
			'inputName'         => $args['name'],
			'isRequired'        => $required,
			'allowedServices'   => $this->get_allowed_services(),
		);

		return sprintf(
			'<div class="appointment-calendar jet-apb-calendar" data-args="%1$s"></div>',
			htmlspecialchars( json_encode( $dataset ) )
		);

	}

	/**
	 * Returns service field data
	 *
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_service_field_data( $args = array() ) {

		$service_id    = false;
		$service       = false;
		$service_field = ! empty( $args['appointment_service_field'] ) ? $args['appointment_service_field'] : 'current_post_id';

		if ( 'current_post_id' === $service_field ) {
			$service_id = get_the_ID();
			$service    = array(
				'id' => $service_id,
			);
		} elseif ( 'manual_input' === $service_field ) {
			$service_id = ! empty( $args['appointment_service_id'] ) ? absint( $args['appointment_service_id'] ) : false;

			if ( $service_id ) {
				$service = array(
					'id' => $service_id,
				);
			}
		} else {

			$field = ! empty( $args['appointment_form_field'] ) ? $args['appointment_form_field'] : false;

			if ( $field ) {
				$service = array(
					'field' => $field,0
				);
			} else {
				$service = false;
			}

		}

		return array(
			'id'         => $service_id,
			'form_field' => $service,
		);

	}

	/**
	 * Return parseed provider data from arguments
	 *
	 * @param  array  $args [description]
	 * @return [type]       [description]
	 */
	public function get_provider_field_data( $args = array() ) {

		$provider_cpt   = Plugin::instance()->settings->get( 'providers_cpt' );
		$provider_id    = false;
		$provider       = false;
		$provider_field = ! empty( $args['appointment_provider_field'] ) ? $args['appointment_provider_field'] : '';

		if ( ! $provider_cpt ) {
			return array(
				'is_set'     => false,
				'id'         => false,
				'form_field' => false,
			);
		}

		if ( 'current_post_id' === $provider_field ) {
			$provider_id = get_the_ID();
			$provider    = array(
				'id' => $provider_id,
			);
		} elseif ( 'manual_input' === $provider_field ) {
			$provider_id = ! empty( $args['appointment_provider_id'] ) ? absint( $args['appointment_provider_id'] ) : false;
			if ( $provider_id ) {
				$provider = array(
					'id' => $provider_id,
				);
			}
		} else {

			$field = ! empty( $args['appointment_provider_form_field'] ) ? $args['appointment_provider_form_field'] : false;
;
			if ( $field ) {
				$provider = array(
					'field' => $field,
				);
			} else {
				$provider = false;
			}

		}

		if ( $provider || $provider_id ) {
			$is_set = true;
		} else {
			$is_set = false;
		}

		return array(
			'is_set'     => $is_set,
			'id'         => $provider_id,
			'form_field' => $provider,
		);

	}

}