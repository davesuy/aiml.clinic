<?php
namespace JET_APB\Rest_API;

use JET_APB\Plugin;
use JET_APB\Time_Slots;

class Endpoint_Service_Providers extends \Jet_Engine_Base_API_Endpoint {

	/**
	 * Returns route name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'appointment-service-providers';
	}

	/**
	 * API callback
	 *
	 * @return void
	 */
	public function callback( $request ) {

		$params          = $request->get_params();
		$service         = ! empty( $params['service'] ) ? absint( $params['service'] ) : 0;
		$custom_template = ! empty( $params['custom_template'] ) ? absint( $params['custom_template'] ) : 0;
		$args_str        = ! empty( $params['args_str'] ) ? $params['args_str'] : '';
		$is_ajax         = ! empty( $params['is_ajax'] ) ? $params['is_ajax'] : false;

		if ( ! $service ) {
			return rest_ensure_response( array(
				'success' => false,
			) );
		}

		$providers = Plugin::instance()->tools->get_providers_for_service( $service );

		if ( ! $custom_template ) {
			return rest_ensure_response( array(
				'success' => true,
				'data'    => $providers,
			) );
		} else {

			if ( ! class_exists( '\Jet_Engine_Booking_Forms_Builder' ) ) {
				require_once jet_engine()->modules->modules_path( 'forms/builder.php' );
			}

			$builder = new \Jet_Engine_Booking_Forms_Builder();
			$checked = null;
			$result  = array();

			if ( $is_ajax && $custom_template ) {
				ob_start();
				$css_file = new \Elementor\Core\Files\CSS\Post( $custom_template );
				$css_file->print_css();
				$result[] = ob_get_clean();
			}

			foreach ( $providers as $provider ) {

				$args = array(
					'field_options_from'      => 'posts',
					'custom_item_template_id' => $custom_template,
				);

				ob_start();

				$template    = $builder->get_custom_template( $provider->ID, $args );
				$data_switch = null;

				?>
				<div class="jet-form__field-wrap radio-wrap checkradio-wrap">
					<?php if ( $template ) {
						echo $template;
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

				$result[] = ob_get_clean();

			}


			return rest_ensure_response( array(
				'success' => true,
				'data'    => implode( '', $result ),
			) );

		}



	}

	/**
	 * Returns endpoint request method - GET/POST/PUT/DELTE
	 *
	 * @return string
	 */
	public function get_method() {
		return 'GET';
	}

	/**
	 * Returns arguments config
	 *
	 * @return array
	 */
	public function get_args() {
		return array(
			'service' => array(
				'default'  => '',
				'required' => true,
			),
			'custom_template' => array(
				'default'  => '',
				'required' => false,
			),
			'args_str' => array(
				'default'  => '',
				'required' => false,
			),
		);
	}

}
