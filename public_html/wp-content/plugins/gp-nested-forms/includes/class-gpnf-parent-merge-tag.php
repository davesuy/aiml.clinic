<?php

class GPNF_Parent_Merge_Tag {

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {

		add_action( 'gpnf_load_nested_form_hooks', array( $this, 'load_hooks' ) );

	}

	public function load_hooks() {

		add_filter( 'gform_field_input', array( $this, 'select_value_data_attr' ), 11, 5 );

	}

	public function parse_parent_merge_tag( $text, $form, $entry, $url_encode, $esc_html, $nl2br, $format ) {
		preg_match_all( "/\{\%GPNF:Parent:(.*?)\%\}/", $text, $parent_matches, PREG_SET_ORDER );

		if ( ! empty( $parent_matches ) ) {
			$parent_form_id = rgar( $entry, 'gpnf_entry_parent_form' );
			$parent_form    = GFAPI::get_form( $parent_form_id );
			$parent_entry   = GFAPI::get_entry( rgar( $entry, 'gpnf_entry_parent' ) );

			foreach ( $parent_matches as $match ) {
				$full_tag = $match[0];
				$modifier = $match[1];

				$stubbed_text_format = preg_match( '/\d+\.\d+/', $modifier ) ? "{BogusLabel:%s}" : '{%s}';
				$stubbed_text        = sprintf( $stubbed_text_format, $modifier );

				$value = GFCommon::replace_variables( $stubbed_text, $parent_form, $parent_entry, $url_encode, $esc_html, $nl2br, $format );
				$text  = str_replace( $full_tag, $url_encode ? urlencode( $value ) : $value, $text );
			}
		}

		return $text;
	}

	public function select_value_data_attr( $input_html, $field, $value, $entry_id, $form_id ) {

		if ( $field->is_entry_detail() ) {
			return $input_html;
		}

		$has_parent_merge_tag = false;

		if ( is_array( $field->inputs ) ) {
			foreach ( $field->inputs as $input ) {
				if ( stripos( rgar( $input, 'defaultValue' ), '{Parent' ) !== false ) {
					$has_parent_merge_tag = true;
					break;
				}
			}
		} else if ( stripos( rgar( $field, 'defaultValue' ), '{Parent' ) !== false ) {
			$has_parent_merge_tag = true;
		}

		if ( ! $has_parent_merge_tag ) {
			return $input_html;
		}

		remove_filter( 'gform_field_input', array( $this, 'select_value_data_attr' ), 11 );
		$input_html = GFCommon::get_field_input( $field, $value, $entry_id, $form_id, GFAPI::get_form( $form_id ) );
		add_filter( 'gform_field_input', array( $this, 'select_value_data_attr' ), 11, 5 );

		$select_pattern = '/<(?:select|textarea|input)(.*?)id=[\'"]input_((\d+_?)+)[\'"]/m';

		preg_match_all( $select_pattern, $input_html, $matches, PREG_SET_ORDER, 0 );

		foreach ( $matches as $match ) {
			if ( empty( $match[2] ) ) {
				continue;
			}

			$input_id = join( '.', array_slice( explode( '_', $match[2] ), 1 ) );

			/**
			 * Reason for this conditional:
			 * GFFormsModel::get_default_value() does not work with all fields (time field specifically).
			 */
			if ( is_array( $field->inputs ) ) {
				$input         = RGFormsModel::get_input( $field, $input_id );
				$default_value = rgar( $input, 'defaultValue' );
			} else {
				$default_value = rgar( $field, 'defaultValue' );
			}

			if ( stripos( $default_value, '{Parent' ) === false ) {
				continue;
			}

			$search  = $match[0];
			$replace = $match[0] . ' data-gpnf-value="' . esc_attr( $default_value ) . '"';

			/* If there's a submitted value, add additional attribute to
			prevent the value from changing back to the parent. */
			$submitted_value = rgpost( 'input_' . $input_id );

			if ( $submitted_value && stripos( $submitted_value, '{Parent' ) === false ) {
				$replace = $match[0] . ' data-gpnf-changed="true"';
			}

			$input_html = str_replace( $search, $replace, $input_html );
		}

		return $input_html;
	}

}

function gpnf_parent_merge_tag() {
	return GPNF_Parent_Merge_Tag::get_instance();
}
