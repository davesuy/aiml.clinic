<?php
/**
 * Elementor views manager
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Smart_Filters_Block_Base' ) ) {

	/**
	 * Define Jet_Smart_Filters_Block_Base class
	 */
	abstract class Jet_Smart_Filters_Block_Base {

		protected $namespace  = 'jet-smart-filters/';

		public $block_manager = null;

		public $css_scheme    = null;

		public function __construct() {

			$attributes =$this->get_attributes();

			/**
			 * Set default blocks attributes to avoid errors
			 */
			$attributes['className'] = array(
				'type' => 'string',
				'default' => '',
			);

			if( class_exists( 'JET_SM\Gutenberg\Block_Manager' ) && class_exists( 'JET_SM\Gutenberg\Block_Manager' ) ){
				$this->set_css_scheme();
				$this->set_style_manager_instance();
				$this->add_style_manager_options();
			}

			register_block_type(
				$this->namespace . $this->get_name(),
				array(
					'attributes'      => $attributes,
					'render_callback' => array( $this, 'render_callback' ),
					'script'          => $this->get_script_depends(),
					'style'           => $this->get_style_depends(),
					'editor_script'   => $this->get_editor_script_depends(),
					'editor_style'    => $this->get_editor_style_depends(),
				)
			);
		}

		public function get_script_depends() {
			return '';
		}

		public function get_style_depends() {
			return '';
		}

		public function get_editor_script_depends() {
			return 'jet-smart-filters';
		}

		public function get_editor_style_depends() {
			return 'jet-smart-filters';
		}

		/**
		 * Return attributes array
		 *
		 * @return array
		 */
		public function get_attributes() {
			return array(
				// General
				'filter_id' => array(
					'type'    => 'number',
					'default' => 0,
				),
				'content_provider' => array(
					'type'    => 'string',
					'default' => 'not-selected',
				),
				'apply_type' => array(
					'type'    => 'string',
					'default' => 'ajax',
				),
				'apply_on' => array(
					'type'    => 'string',
					'default' => 'value',
				),
				'apply_button' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'hide_apply_button' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'apply_button_text' => array(
					'type'    => 'string',
					'default' => __( 'Apply filter', 'jet-smart-filters' ),
				),
				'apply_redirect' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'redirect_path' => array(
					'type'    => 'string',
					'default' => '',
				),
				'remove_filters_text' => array(
					'type'    => 'string',
					'default' => __( 'Remove filters', 'jet-smart-filters' ),
				),
				'show_label' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'filters_label' => array(
					'type'    => 'string',
					'default' => __( 'Active filters:', 'jet-smart-filters' ),
				),
				'typing_min_letters_count' => array(
					'type'    => 'number',
					'default' => 3,
				),
				'tags_label' => array(
					'type'    => 'string',
					'default' => __( 'Active tags:', 'jet-smart-filters' ),
				),
				'clear_item' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'clear_item_label' => array(
					'type'    => 'string',
					'default' => __( 'Clear', 'jet-smart-filters' ),
				),
				'rating_icon' => array(
					'type'    => 'string',
					'default' => 'fa fa-star',
				),
				'sorting_label' => array(
					'type'    => 'string',
					'default' => '',
				),
				'sorting_placeholder' => array(
					'type'    => 'string',
					'default' => __( 'Sort...', 'jet-smart-filters' ),
				),
				'sorting_list' => array(
					'type'    => 'array',
					'default' => array(
						array(
							'title'   => __( 'By title from lowest to highest', 'jet-smart-filters' ),
							'orderby' => 'title',
							'order'   => 'ASC'
						),
						array(
							'title'   => __( 'By title from highest to lowest', 'jet-smart-filters' ),
							'orderby' => 'title',
							'order'   => 'DESC'
						),
						array(
							'title'   => __( 'By date from lowest to highest', 'jet-smart-filters' ),
							'orderby' => 'date',
							'order'   => 'ASC'
						),
						array(
							'title'   => __( 'By date from highest to lowest', 'jet-smart-filters' ),
							'orderby' => 'date',
							'order'   => 'DESC'
						)
					),
					'items' => [
						'type' => 'object'
					]
				),
				// Indexer
				'apply_indexer' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'show_counter' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'show_items_rule' => array(
					'type'    => 'string',
					'default' => 'show',
				),
				'change_items_rule' => array(
					'type'    => 'string',
					'default' => 'always',
				),
				// Filter Options
				'show_items_label' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'filter_image_size' => array(
					'type'    => 'string',
					'default' => 'full',
				),
				// Pagination Controls
				'enable_prev_next' => array(
					'type'    => 'boolean',
					'default' => true,
				),
				'prev_text' => array(
					'type'    => 'string',
					'default' => __( 'Prev Text', 'jet-smart-filters' ),
				),
				'next_text' => array(
					'type'    => 'string',
					'default' => __( 'Next Text', 'jet-smart-filters' ),
				),
				'pages_center_offset' => array(
					'type'    => 'number',
					'default' => 0,
				),
				'pages_end_offset' => array(
					'type'    => 'number',
					'default' => 0,
				),
				'provider_top_offset' => array(
					'type'    => 'number',
					'default' => 0,
				),
				// Additional Settings
				'search_enabled' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'search_placeholder' => array(
					'type'    => 'string',
					'default' => __( 'Search...', 'jet-smart-filters' ),
				),
				'moreless_enabled' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'less_items_count' => array(
					'type'    => 'number',
					'default' => 5,
				),
				'more_text' => array(
					'type'    => 'string',
					'default' => __( 'More', 'jet-smart-filters' ),
				),
				'less_text' => array(
					'type'    => 'string',
					'default' => __( 'Less', 'jet-smart-filters' ),
				),
				'dropdown_enabled' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'dropdown_placeholder' => array(
					'type'    => 'string',
					'default' => __( 'Select some options', 'jet-smart-filters' ),
				),
				'scroll_enabled' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'scroll_height' => array(
					'type'    => 'number',
					'default' => 290,
				),
			);
		}

		/**
		 * Set style manager class instance
		 *
		 * @return boolean
		 */
		public function  set_style_manager_instance(){
			$name              = $this->namespace . $this->get_name();

			$this->block_manager = JET_SM\Gutenberg\Block_Manager::get_instance();
			$this->controls_manager = new JET_SM\Gutenberg\Controls_Manager( $name );
		}

		/**
		 * Add style block options
		 *
		 * @return boolean
		 */
		public function add_style_manager_options(){}

		/**
		 * Set css classes
		 *
		 * @return boolean
		 */
		public function set_css_scheme(){
			$this->css_scheme = [];
		}

		/**
		 * Is editor context
		 *
		 * @return boolean
		 */
		public function is_editor() {
			return isset( $_REQUEST['context'] ) && $_REQUEST['context'] === 'edit' ? true : false;
		}

		/**
		 * Return callback
		 *
		 * @return html
		 */
		public function render_callback( $settings = array() ) {

			jet_smart_filters()->set_filters_used();

			if ( empty( $settings['filter_id'] ) ) {
				return $this->is_editor() ? __( 'Please select a filter', 'jet-smart-filters' ) : false;
			}

			if ( empty( $settings['content_provider'] ) || $settings['content_provider'] === 'not-selected' ) {
				return $this->is_editor() ? __( 'Please select a provider', 'jet-smart-filters' ) : false;
			}

			if ( 'submit' === $settings['apply_on'] && in_array( $settings['apply_type'], ['ajax', 'mixed'] ) ) {
				$apply_type = $settings['apply_type'] . '-reload';
			} else {
				$apply_type = $settings['apply_type'];
			}

			$filter_id            = $settings['filter_id'];
			$base_class           = 'jet-smart-filters-' . $this->get_name();
			$provider             = $settings['content_provider'];
			$query_id             = 'default';
			$show_items_label     = $settings['show_items_label'];
			$show_decorator       = true;
			$filter_image_size    = $settings['filter_image_size'];
			$rating_icon          = '<i class="jet-rating-icon ' . $settings['rating_icon'] . '"></i>';
			$apply_indexer        = $settings['apply_indexer'];
			$indexer_class        = '';
			$show_counter         = false;
			$show_items_rule      = 'show';
			$change_items_rule    = $settings['change_items_rule'];
			// search
			$search_enabled       = ! empty( $settings['search_enabled'] ) ? filter_var( $settings['search_enabled'], FILTER_VALIDATE_BOOLEAN ) : false;
			$search_placeholder   = ! empty( $settings['search_placeholder'] ) && $search_enabled ? $settings['search_placeholder'] : false;
			// more/less
			$less_items_count     = ! empty( $settings['moreless_enabled'] ) && ! empty( $settings['less_items_count'] ) ? (int)$settings['less_items_count'] : false;
			$more_text            = ! empty( $settings['more_text'] ) ? $settings['more_text'] : false;
			$less_text            = ! empty( $settings['less_text'] ) ? $settings['less_text'] : false;
			// dropdown
			$dropdown_enabled     = ! empty( $settings['dropdown_enabled'] ) ? $settings['dropdown_enabled'] : false;
			$dropdown_placeholder = ! empty( $settings['dropdown_placeholder'] ) ? $settings['dropdown_placeholder'] : false;
			// scroll
			$scroll_height        = ! empty( $settings['scroll_enabled'] ) && ! empty( $settings['scroll_height'] ) ? (int)$settings['scroll_height'] : false;

			if ( $apply_indexer ) {
				$indexer_class   = 'jet-filter-indexed';
				$show_counter    = $settings['show_counter'] === true ? 'yes' : false;
				$show_items_rule = $settings['show_items_rule'];
			}

			ob_start();

			printf(
				'<div class="%1$s jet-filter %2$s" data-indexer-rule="%3$s" data-show-counter="%4$s" data-change-counter="%5$s">',
				apply_filters( 'jet-smart-filters/render_filter_template/base_class', $base_class, $filter_id ),
				$indexer_class,
				$show_items_rule,
				$show_counter,
				$change_items_rule
			);

			$filter_template_args =  array(
				'filter_id'        => $filter_id,
				'content_provider' => $provider,
				'apply_type'       => $apply_type,
				'query_id'         => $query_id,
				'rating_icon'      => $rating_icon,
				'display_options'  => array(
					'show_items_label'  => $show_items_label,
					'show_decorator'    => $show_decorator,
					'filter_image_size' => $filter_image_size,
					'show_counter'      => $show_counter,
				),
			);

			// search
			if ( $search_enabled ) $filter_template_args['search_enabled'] = $search_enabled;
			if ( $search_placeholder ) $filter_template_args['search_placeholder'] = htmlspecialchars( $search_placeholder );
			// more/less
			if ( $less_items_count ) $filter_template_args['less_items_count'] = $less_items_count;
			if ( $more_text ) $filter_template_args['more_text'] = htmlspecialchars( $more_text );
			if ( $less_text ) $filter_template_args['less_text'] = htmlspecialchars( $less_text );
			//dropdown
			if ( $dropdown_enabled ) $filter_template_args['dropdown_enabled'] = $dropdown_enabled;
			if ( $dropdown_placeholder ) $filter_template_args['dropdown_placeholder'] = $dropdown_placeholder;
			// scroll
			if ( $scroll_height ) $filter_template_args['scroll_height'] = $scroll_height;

			include jet_smart_filters()->get_template( 'common/filter-label.php' );

			jet_smart_filters()->filter_types->render_filter_template( $this->get_name(), $filter_template_args );

			echo '</div>';

			include jet_smart_filters()->get_template( 'common/apply-filters.php' );

			$filter_layout = ob_get_clean();

			return $filter_layout;

		}

		/**
		 * Return filter name
		 *
		 * @return String
		 */
		abstract public function get_name();

	}

}