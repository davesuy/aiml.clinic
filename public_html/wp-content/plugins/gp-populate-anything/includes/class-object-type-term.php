<?php

class GPPA_Object_Type_Term extends GPPA_Object_Type {

	public function __construct($id) {
		parent::__construct($id);

		add_action( 'gppa_pre_object_type_query_term', array( $this, 'add_filter_hooks' ) );
	}

	public function get_label() {
		return esc_html__( 'Taxonomy Term', 'gp-populate-anything' );
	}

	public function get_properties( $primary_property = null ) {

		global $wpdb;

		return array_merge( array(
			'taxonomy'   => array(
				'label'    => esc_html__( 'Taxonomy', 'gp-populate-anything' ),
				'value'    => 'taxonomy',
				'callable' => array( $this, 'get_taxonomies' ),
				'orderby'  => true,
			),
			'name'       => array(
				'label'    => esc_html__( 'Name', 'gp-populate-anything' ),
				'value'    => 'name',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->terms, 'name' ),
				'orderby'  => true,
			),
			'slug'       => array(
				'label'    => esc_html__( 'Slug', 'gp-populate-anything' ),
				'value'    => 'slug',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->terms, 'slug' ),
				'orderby'  => true,
			),
			'object_id'  => array(
				'label'    => esc_html__( 'Object ID', 'gp-populate-anything' ),
				'value'    => 'object_id',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->term_relationships, 'object_id' ),
				'orderby'  => false,
			),
			'term_id' => array(
				'label'    => esc_html__( 'Term ID', 'gp-populate-anything' ),
				'value'    => 'term_id',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->terms, 'term_id' ),
				'orderby'  => true,
			),
			'parent' => array(
				'label'    => esc_html__( 'Parent Term', 'gp-populate-anything' ),
				'value'    => 'parent',
				'callable' => array( $this, 'get_terms' ),
				'orderby'  => true,
			),
		) );

	}

	public function add_filter_hooks() {
		add_filter('gppa_object_type_term_filter', array( $this, 'process_filter_default'), 10, 4 );
		add_filter('gppa_object_type_term_filter_parent', array( $this, 'process_filter_with_term_taxonomy'), 10, 4 );
		add_filter('gppa_object_type_term_filter_taxonomy', array( $this, 'process_filter_with_term_taxonomy'), 10, 4 );
		add_filter('gppa_object_type_term_filter_object_id', array( $this, 'process_filter_object_id'), 10, 4 );
	}

	public function process_filter_default( $query_builder_args, $args ) {

		global $wpdb;

		/**
		 * @var $filter_value
		 * @var $filter
		 * @var $filter_group
		 * @var $filter_group_index
		 * @var $property
		 * @var $property_id
		 */
		extract($args);

		$query_builder_args['where'][ $filter_group_index ][] = $this->build_where_clause( $wpdb->terms, rgar( $property, 'value' ), $filter['operator'], $filter_value );

		return $query_builder_args;

	}

	public function process_filter_with_term_taxonomy( $query_builder_args, $args ) {

		global $wpdb;

		/**
		 * @var $filter_value
		 * @var $filter
		 * @var $filter_group
		 * @var $filter_group_index
		 * @var $property
		 * @var $property_id
		 */
		extract($args);

		$query_builder_args['where'][ $filter_group_index ][] = $this->build_where_clause( $wpdb->term_taxonomy, rgar( $property, 'value' ), $filter['operator'], $filter_value );

		return $query_builder_args;

	}

	public function process_filter_object_id( $query_builder_args, $args ) {

		global $wpdb;

		/**
		 * @var $filter_value
		 * @var $filter
		 * @var $filter_group
		 * @var $filter_group_index
		 * @var $property
		 * @var $property_id
		 */
		extract($args);

		$query_builder_args['where'][ $filter_group_index ][] = $this->build_where_clause( $wpdb->term_relationships, 'object_id', $filter['operator'], $filter_value );
		$query_builder_args['joins'][] = "LEFT JOIN {$wpdb->term_relationships} ON ( {$wpdb->term_taxonomy}.term_taxonomy_id = {$wpdb->term_relationships}.term_taxonomy_id )";

		return $query_builder_args;

	}

	public function get_terms() {
		global $wpdb;

		$result = wp_list_pluck( $wpdb->get_results( "SELECT DISTINCT term_id, name FROM $wpdb->terms" ), 'name', 'term_id' );

		natcasesort( $result );

		return $result;
	}

	public function get_taxonomies() {

		$taxonomies = array();

		foreach ( get_taxonomies( null, 'objects' ) as $taxonomy ) {
			$taxonomies[ $taxonomy->name ] = $taxonomy->labels->singular_name;
		}

		return $taxonomies;

	}

	public function default_query_args( $args, $field ) {

		global $wpdb;

		/**
		 * @var $primary_property_value string
		 * @var $field_values array
		 * @var $filter_groups array
		 * @var $ordering array
		 */
		extract( $args );

		$orderby = rgar( $ordering, 'orderby' );
		$order = rgar( $ordering, 'order', 'ASC' );

		return array(
			'select'   => "{$wpdb->terms}.*, {$wpdb->term_taxonomy}.* FROM {$wpdb->terms}",
			'where'    => array(),
			'joins'    => array(
				"LEFT JOIN {$wpdb->term_taxonomy} ON ( {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id )",
			),
			'group_by' => "{$wpdb->terms}.term_id",
			'order_by' => $order && $orderby ? "{$orderby} {$order}" : '',
		);

	}

	public function query( $args, $field ) {

		global $wpdb;

		$query_args = $this->process_filter_groups( $args, $this->default_query_args( $args, $field ) );

		$query = $this->build_mysql_query( $query_args );
		$terms = $wpdb->get_results($query);

		foreach ( $terms as $key => $term ) {
			$terms[ $key ] = new WP_Term( $term );
		}

		return $terms;

	}

}
