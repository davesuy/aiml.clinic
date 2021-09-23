<?php

class GPPA_Object_Type_User extends GPPA_Object_Type {

	private $meta_query_counter = 0;

	public function __construct($id) {
		parent::__construct($id);

		add_action( 'gppa_pre_object_type_query_user', array( $this, 'add_filter_hooks' ) );
	}


	public function get_label() {
		return esc_html__( 'User', 'gp-populate-anything' );
	}

	public function get_groups() {
		return array(
			'meta' => array(
				'label' => esc_html__( 'User Meta', 'gp-populate-anything' ),
			),
		);
	}

	public function get_default_templates() {
		return array(
			'value' => 'ID',
			'label' => 'display_name',
		);
	}

	public function add_filter_hooks() {
		add_filter('gppa_object_type_user_filter', array( $this, 'process_filter_default'), 10, 4 );
		add_filter('gppa_object_type_user_filter_roles', array( $this, 'process_filter_roles'), 10, 4 );
		add_filter('gppa_object_type_user_filter_group_meta', array( $this, 'process_filter_meta'), 10, 4 );
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

		$query_builder_args['where'][ $filter_group_index ][] = $this->build_where_clause( $wpdb->users, rgar( $property, 'value' ), $filter['operator'], $filter_value );

		return $query_builder_args;

	}

	public function process_filter_roles( $query_builder_args, $args ) {

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

		$meta_value = $this->get_sql_value( 'contains', $filter_value );

		$blog_id = get_current_blog_id();
		$operator = rgar( $filter, 'operator' ) === 'isnot' ? 'NOT LIKE' : 'LIKE';

		$where = $wpdb->prepare( "( {$wpdb->usermeta}.meta_key = %s AND {$wpdb->usermeta}.meta_value {$operator} %s )", $wpdb->get_blog_prefix( $blog_id ) . 'capabilities', $meta_value );

		$query_builder_args['where'][ $filter_group_index ][] = $where;
		$query_builder_args['joins']['usermeta'] = "LEFT JOIN {$wpdb->usermeta} ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id )";

		return $query_builder_args;

	}

	public function process_filter_meta( $query_builder_args, $args ) {

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

		$meta_specification = $this->get_value_specification( $filter_value );
		$meta_operator      = $this->get_sql_operator( $filter['operator'] );
		$meta_value         = $this->get_sql_value( $filter['operator'], $filter_value );

		$this->meta_query_counter++;
		$as_table = 'mq' . $this->meta_query_counter;

		$query_builder_args['where'][ $filter_group_index ][] = $wpdb->prepare( "( {$as_table}.meta_key = %s AND {$as_table}.meta_value {$meta_operator} {$meta_specification} )", rgar( $property, 'value' ), $meta_value );
		$query_builder_args['joins'][$as_table] = "LEFT JOIN {$wpdb->usermeta} AS {$as_table} ON ( {$wpdb->users}.ID = {$as_table}.user_id )";

		return $query_builder_args;

	}

	public function get_properties( $primary_property = null ) {

		global $wpdb;

		return array_merge( array(
			'display_name' => array(
				'label'      => esc_html__( 'Display Name', 'gp-populate-anything' ),
				'value'    => 'display_name',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->users, 'display_name' ),
				'orderby'  => true,
			),
			'ID'           => array(
				'label'      => esc_html__( 'User ID', 'gp-populate-anything' ),
				'value'    => 'ID',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->users, 'ID' ),
				'orderby'  => true,
			),
			'user_login'   => array(
				'label'      => esc_html__( 'Username', 'gp-populate-anything' ),
				'value'    => 'user_login',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->users, 'user_login' ),
				'orderby'  => true,
			),
			'user_email'   => array(
				'label'      => esc_html__( 'User Email', 'gp-populate-anything' ),
				'value'    => 'user_email',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->users, 'user_email' ),
				'orderby'  => true,
			),
			'user_url'     => array(
				'label'      => esc_html__( 'User URL', 'gp-populate-anything' ),
				'value'    => 'user_url',
				'callable' => array( $this, 'get_col_rows' ),
				'args'     => array( $wpdb->users, 'user_url' ),
				'orderby'  => true,
			),
			'roles'         => array(
				'label'    => esc_html__( 'Role', 'gp-populate-anything' ),
				'value'    => 'roles',
				'callable' => array( $this, 'get_user_roles' ),
				'operators' => array(
					'is',
					'isnot',
				),
			),
		), $this->get_user_meta_properties() );

	}

	public function get_object_prop_value( $object, $prop ) {

		$prop  = preg_replace( '/^meta_/', '', $prop );
		$value = $object->{$prop};

		switch( $prop ) {
			case 'roles':
				$value = implode( ', ', $value );
				break;
		}

		return $value;

	}

	public function get_user_meta_properties() {

		global $wpdb;

		$user_meta_properties = array();

		foreach ( $this->get_col_rows( $wpdb->usermeta, 'meta_key' ) as $user_meta_key ) {
			$user_meta_properties[ 'meta_' . $user_meta_key ] = array(
				'label'    => $user_meta_key,
				'value'    => $user_meta_key,
				'meta'     => true,
				'callable' => array( $this, 'get_meta_values' ),
				'args'     => array( $user_meta_key, $wpdb->usermeta ),
				'group'    => 'meta',
			);
		}

		return $user_meta_properties;

	}

	public function get_user_roles() {

		$output = array();

		foreach ( get_editable_roles() as $role_name => $role_info ) {
			$output[ $role_name ] = $role_info['name'];
		}

		return $output;

	}

	public function default_query_args ( $args, $field ) {

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
			'select'   => "{$wpdb->users}.* FROM {$wpdb->users}",
			'where'    => array(),
			'joins'    => array(),
			'group_by' => "{$wpdb->users}.ID",
			'order_by' => $order && $orderby ? "{$wpdb->users}.{$orderby} {$order}" : '',
		);

	}

	public function query( $args, $field ) {

		global $wpdb;

		$query_args = $this->process_filter_groups( $args, $this->default_query_args( $args, $field ) );

		$query = $this->build_mysql_query( $query_args );
		$users = $wpdb->get_results($query);

		foreach ( $users as $key => $user ) {
			$users[ $key ] = new WP_User( $user );
		}

		/* Reset meta query counter */
		$this->meta_query_counter = 0;

		return $users;

	}

}
