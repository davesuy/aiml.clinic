<?php
namespace JET_APB\Admin\Settings;

use Jet_Dashboard\Base\Page_Module as Page_Module_Base;
use Jet_Dashboard\Dashboard as Dashboard;

use JET_APB\Plugin;
use JET_APB\Time_Slots;
use JET_APB\Admin\Helpers\Page_Config;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Appointment_Settings_Base extends Page_Module_Base {

	/**
	 * Returns module slug
	 *
	 * @return void
	 */
	public function get_page_slug() {}

	/**
	 * [get_page_name description]
	 * @return [type] [description]
	 */
	public function get_page_name() {}

	/**
	 * [get_subpage_slug description]
	 * @return [type] [description]
	 */
	public function get_parent_slug() {
		return 'settings-page';
	}

	/**
	 * [get_category description]
	 * @return [type] [description]
	 */
	public function get_category() {
		return 'jet-appointments-booking';
	}

	/**
	 * Define that is setup page
	 *
	 * @return boolean [description]
	 */
	public function is_setup_page() {
		return false;
	}

	/**
	 * [get_page_link description]
	 * @return [type] [description]
	 */
	public function get_page_link() {
		return Dashboard::get_instance()->get_dashboard_page_url( $this->get_parent_slug(), $this->get_page_slug() );
	}

	/**
	 * Return  page config object
	 *
	 * @return [type] [description]
	 */
	public function page_settings() {
		$settings = Plugin::instance()->settings->get_all();

		if ( empty( $settings['providers_cpt'] ) ) {
			$settings['providers_cpt'] = '';
		}

		$page_config_args = [
			'post_types' => \Jet_Engine_Tools::get_post_types_for_js( array(
				'value' => '',
				'label' => esc_html__( 'Select...', 'jet-appointments-booking' ),
			) ),
			'weekdays'   => array(
				'monday'    => esc_html__( 'Monday', 'jet-appointments-booking' ),
				'tuesday'   => esc_html__( 'Tuesday', 'jet-appointments-booking' ),
				'wednesday' => esc_html__( 'Wednesday', 'jet-appointments-booking' ),
				'thursday'  => esc_html__( 'Thursday', 'jet-appointments-booking' ),
				'friday'    => esc_html__( 'Friday', 'jet-appointments-booking' ),
				'saturday'  => esc_html__( 'Saturday', 'jet-appointments-booking' ),
				'sunday'    => esc_html__( 'Sunday', 'jet-appointments-booking' ),
			),
		];

		if( ! $this->is_setup_page() ){
			$page_config_args['settings'] = $settings;
		}

		$page_config = new Page_Config( 'jet-apb-settings', $page_config_args );

		return $page_config;
	}

	/**
	 * Enqueue module-specific assets
	 *
	 * @return void
	 */
	public function enqueue_module_assets() {
		wp_enqueue_script( 'moment' );
		wp_enqueue_script( 'vuejs-datepicker' );

		wp_enqueue_script(
			'jet-apb-admin-settings',
			JET_APB_URL . 'assets/js/admin/settings.js',
			array( 'cx-vue-ui', 'moment' ),
			JET_APB_VERSION,
			true
		);

		wp_set_script_translations(
			'jet-apb-admin-settings',
			'jet-appointments-booking',
			JET_APB_PATH . 'languages'
		);

		wp_localize_script(
			'jet-apb-admin-settings',
			'JetAPBConfig',
			apply_filters( 'jet-appointments/admin/settings-page/localized-config', $this->page_settings()->get( 'config' ) )
		);

		wp_enqueue_style( 'jet-apb-working-hours' );
	}

	/**
	 * License page config
	 *
	 * @param  array  $config  [description]
	 * @param  string $subpage [description]
	 * @return [type]          [description]
	 */
	public function page_config( $config = array(), $page = false, $subpage = false ) {
		$config['pageModule'] = $this->get_parent_slug();
		$config['subPageModule'] = $this->get_page_slug();

		return $config;
	}
}