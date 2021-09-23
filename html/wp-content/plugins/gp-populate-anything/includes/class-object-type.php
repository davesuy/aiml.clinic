<?php

abstract class GPPA_Object_Type {

	public $id;

	abstract public function query( $args, $field );

	abstract public function get_label();

	abstract public function get_properties( $primary_property_value = null );

	public function __construct($id) {
		$this->id = $id;

		add_filter( 'gppa_replace_filter_value_variables_' . $this->id, array( $this, 'replace_gf_field_value' ), 10, 2 );
		add_filter( 'gppa_replace_filter_value_variables_' . $this->id, array( $this, 'replace_special_values' ), 10 );
	}

	public function get_primary_property() {
		return null;
	}

	public function get_groups() {
		return array();
	}

	public function get_default_templates() {
		return array();
	}

	public function default_query_args( $args, $field ) {
		return array();
	}

	public function to_simple_array() {

		$output = array(
			'id'         => $this->id,
			'label'      => $this->get_label(),
			'properties' => $this->get_properties(),
			'groups'     => $this->get_groups(),
			'templates'  => $this->get_default_templates(),
		);

		if ( $this->get_primary_property() ) {
			$output['primary-property'] = $this->get_primary_property();
		}

		return $output;

	}

	public function replace_gf_field_value( $value, $field_values ) {

		if ( preg_match_all( '/{\w+:gf_field_(\d+)}/', $value, $field_matches ) ) {
			if ( count( $field_matches ) && ! empty( $field_matches[0] ) ) {
				foreach ( $field_matches[0] as $index => $match ) {
					$field_id = $field_matches[1][$index];
					$replaced_value = $this->replace_gf_field_value( "gf_field:{$field_id}", $field_values );
					$value = str_replace( $match, $replaced_value, $value );
				}

				return $value;
			}
		}

		if ( strpos( $value, 'gf_field' ) !== 0 ) {
			return $value;
		}

		if ( ! $field_values ) {
			return null;
		}

		$value_exploded = explode( ':', $value );
		$value          = rgar( $field_values, $value_exploded[1], null );

		return $value === '' ? null : $value;

	}

	public function replace_special_values( $value ) {

		if ( strpos( $value, 'special_value:' ) !== 0 ) {
			return $value;
		}

		$special_value       = str_replace( 'special_value:', '', $value );
		$special_value_parts = explode( ':', $special_value );

		switch ( $special_value_parts[0] ) {
			case 'current_user':
				$user = wp_get_current_user();

				if ( $user && $user->ID > 0 ) {
					return $user->{$special_value_parts[1]};
				}

				break;
			case 'current_post':
				$post    = get_post();
				$referer = rgar( $_SERVER, 'HTTP_REFERER' );

				if ( ! $post && $referer && $referer_post_id = url_to_postid( $referer ) ) {
					$post = get_post( $referer_post_id );
				}

				if ( $post ) {
					return $post->{$special_value_parts[1]};
				}

				break;
		}

		/* No current post or user, return impossible ID */
		return apply_filters( 'gppa_special_value_no_result', -1, $value, $special_value );

	}

	public function get_object_prop_value( $object, $prop ) {

		if ( ! isset ( $object->{$prop} ) ) {
			return null;
		}

		return $object->{$prop};

	}

	public function get_col_rows( $table, $col ) {

		global $wpdb;

		$query = apply_filters( 'gppa_object_type_col_rows_query', "SELECT DISTINCT $col FROM $table LIMIT 1000", $col, $table, $this );
		$result = $wpdb->get_col( $query );

		return is_array( $result ) ? $this->filter_values( $result ) : array();

	}

	public function get_meta_values( $meta_key, $table ) {

		global $wpdb;

		$query  = $wpdb->prepare( "SELECT DISTINCT meta_value FROM $table WHERE meta_key = '%s'", $meta_key );
		$result = $wpdb->get_col( $query );

		return is_array( $result ) ? $this->filter_values( $result ) : array();

	}

	public function process_filter_groups( $args, $processed_filter_groups = array() ) {

		/**
		 * @var $primary_property_value string
		 * @var $field_values array
		 * @var $filter_groups array
		 * @var $ordering array
		 */
		extract( $args );

		$properties = $this->get_properties( $primary_property_value );

		gf_do_action( array( 'gppa_pre_object_type_query', $this->id ), $processed_filter_groups, $args );

		if ( ! is_array( $filter_groups ) ) {
			return $processed_filter_groups;
		}

		foreach ( $filter_groups as $filter_group_index => $filter_group ) {
			foreach ( $filter_group as $filter ) {
				$filter_value = gp_populate_anything()->extract_custom_value( $filter['value'] );
				$filter_value = GFCommon::replace_variables_prepopulate( $filter_value, false, false, true );
				$filter_value = apply_filters( 'gppa_replace_filter_value_variables_' . $this->id, $filter_value, $field_values, $primary_property_value, $filter, $ordering );

				if ( ! $filter_value || ! $filter['property'] ) {
					continue;
				}

				$property = rgar( $properties, $filter['property'] );

				if ( ! $property ) {
					continue;
				}

				$wp_filter_name = 'gppa_object_type_' . $this->id . '_filter_' . $filter['property'];

				if ( ! has_filter( $wp_filter_name ) && $group = rgar( $property, 'group' ) ) {
					$wp_filter_name = 'gppa_object_type_' . $this->id . '_filter_group_' . $group;
				}

				if ( ! has_filter( $wp_filter_name ) ) {
					$wp_filter_name = 'gppa_object_type_' . $this->id . '_filter';
				}

				$processed_filter_groups = apply_filters( $wp_filter_name, $processed_filter_groups, array(
					'filter_value'           => $filter_value,
					'filter'                 => $filter,
					'filter_group'           => $filter_group,
					'filter_group_index'     => $filter_group_index,
					'primary_property_value' => $primary_property_value,
					'property'               => $property,
					'property_id'            => $filter['property'],
				) );
			}
		}

		$processed_filter_groups = apply_filters( 'gppa_object_type_query', $processed_filter_groups, $args );
		$processed_filter_groups = apply_filters( 'gppa_object_type_query_' . $this->id, $processed_filter_groups, $args );

		return $processed_filter_groups;

	}

	public function build_mysql_query( $query_args ) {

		$query = array();

		$query[] = esc_sql( "SELECT {$query_args['select']}" );

		if ( ! empty( $query_args['joins'] ) ) {
			foreach ( $query_args['joins'] as $join_name => $join ) {
				$query[] = $join;
			}
		}

		if ( ! empty( $query_args['where'] ) ) {
			$where_clauses = array();

			foreach ( $query_args['where'] as $where_or_grouping => $where_or_grouping_clauses ) {
				$where_clauses[] = '(' . implode( ' AND ', $where_or_grouping_clauses ) . ')';
			}

			$query[] = "WHERE \n" . implode( "\n OR ", $where_clauses );
		}

		if ( ! empty( $query_args['group_by'] ) ) {
			$query[] = esc_sql( "GROUP BY {$query_args['group_by']}" );
		}

		if ( ! empty( $query_args['order_by'] ) ) {
			$query[] = esc_sql( "ORDER BY {$query_args['order_by']}" );
		}

		$query[] = esc_sql( 'LIMIT ' . apply_filters( 'gppa_query_limit', 501, $this ) );

		return implode( "\n", $query );

	}

	public function get_value_specification( $value ) {

		$specification = '%s';

		/* Cast numeric strings to the appropriate type for operators such as > and < */
		if ( is_numeric($value) ) {
			$value = ($value == (int) $value) ? (int) $value : (float) $value;
		}

		if ( is_int($value) ) {
			$specification = '%d';
		} else if ( is_float($value) ) {
			$specification = '%f';
		}

		return $specification;

	}

	public function get_sql_value( $operator, $value ) {

		global $wpdb;

		switch ( $operator ) {
			case 'starts_with':
				return $wpdb->esc_like( $value ) . '%';

			case 'ends_with':
				return '%' . $wpdb->esc_like( $value );

			case 'contains':
				return '%' . $wpdb->esc_like( $value ) . '%';

			default:
				return $value;
		}

	}

	public function get_sql_operator( $operator ) {

		switch ( $operator ) {
			case 'starts_with':
				return 'LIKE';

			case 'ends_with':
				return 'LIKE';

			case 'contains':
				return 'LIKE';

			case 'is':
				return '=';

			case 'isnot':
				return '!=';

			default:
				return $operator;
		}

	}

	public function build_where_clause( $table, $column, $operator, $value ) {

		global $wpdb;

		$specification = $this->get_value_specification( $value );
		$sql_operator  = $this->get_sql_operator( $operator );
		$value         = $this->get_sql_value( $operator, $value );

		return $wpdb->prepare( "{$table}.{$column} {$sql_operator} {$specification}", $value );

	}

	/*
	 * array_filter - Remove serialized values
	 * array_filter - Remove falsey values
	 * array_unique - Ran to make sequential for json_encode
	 */
	public function filter_values ( $values ) {

		$values = array_values( array_unique( array_filter( array_filter( $values, array(
			__class__,
			'is_not_serialized'
		) ) ) ) );

		natcasesort( $values );

		/* Run array values again so it's an ordered indexed array again */
		return array_values( $values );

	}

	public static function is_not_serialized( $value ) {
		return ! is_serialized( $value );
	}

}
