<?php
namespace SG_Security\Htaccess_Service;

use SG_Security;

/**
 * Class managing the header related htaccess rules.
 */
class Headers_Service extends Abstract_Htaccess_Service {

	/**
	 * The path to the htaccess template.
	 *
	 * @var string
	 */
	public $template = 'xss-headers.tpl';

	/**
	 * Regular expressions to check if a rules is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @access public
	 *
	 * @var array Regular expressions to check if a rules is enabled.
	 */
	public $rules = array(
		'enabled'     => '/\#\s+SGS XSS Header Service/si',
		'disabled'    => '/\#\s+SGS\s+XSS\s+Header\s+Service(.+?)\#\s+SGS\s+XSS\s+Header\s+Service\s+END(\n)?/ims',
		'disable_all' => '/\#\s+SGS\s+XSS\s+Header\s+Service\s+<IfModule mod_headers\.c>()\s+<\/IfModule>\s+\#\s+SGS\s+XSS\s+Header\s+Service\s+END(\n)?/ims',
	);

	/**
	 * Get the filepath to the htaccess.
	 *
	 * @since  1.0.0
	 *
	 * @return string Path to the htaccess.
	 */
	public function get_filepath() {
		return $this->wp_filesystem->abspath() . '.htaccess';
	}

	/**
	 * Enable the XSS protection rules.
	 *
	 * @since  1.0.0
	 *
	 * @param  boolean $type Whether to enable or disable the rules.
	 */
	public function toggle_rules( $type = 1 ) {
		$this->set_htaccess_path();
		( 1 === $type ) ? $this->enable() : $this->disable();
	}
}
