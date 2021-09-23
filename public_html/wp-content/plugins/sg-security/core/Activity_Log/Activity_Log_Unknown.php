<?php
namespace SG_Security\Activity_Log;

use SG_Security\Helper\Helper;
/**
 * Class that manages the Activity log for unknown visits.
 */
class Activity_Log_Unknown extends Activity_Log_Helper {

	public $crawlers = array(
		'googlebot',
		'bingbot',
		'slurp',
		'duckduckbot',
		'baiduspider',
		'yandexbot',
		'facebot',
		'ia_archiver',
	);

	/**
	 * Get visitor type.
	 *
	 * @since 1.0.0
	 *
	 * @return string The type of visitor.
	 */
	public function get_visitor_type() {
		// Loop through the crawlers list.
		foreach ( $this->crawlers as $crawler ) {
			// Check if a user-agent is set.
			if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
				return 'Unknown';
			}

			// Check if the request is made from a crawler.
			if ( strstr( strtolower( $_SERVER['HTTP_USER_AGENT'] ), $crawler ) ) { // phpcs:ignore
				return $crawler;
			}
		}

		return 'Human';

	}

	/**
	 * Log page visit.
	 *
	 * @since  1.0.0
	 */
	public function log_visit() {
		if ( defined( 'WP_CLI' ) ) {
			exit;
		}

		// Bail if request is made with jquery.
		if ( ! empty( $_SERVER['X-Requested-With'] ) ) {
			return;
		}
		// Bail if it is a request for something else than html page.
		if ( isset( $_SERVER['HTTP_ACCEPT'] ) && false === strpos( $_SERVER['HTTP_ACCEPT'], 'text/html' ) ) { // phpcs:ignore
			return;
		}
		// Bail if request is made trough admin-ajax.
		if ( isset( $_SERVER['REQUEST_URI'] ) && false !== strpos( $_SERVER['REQUEST_URI'], 'admin-ajax.php' ) ) { // phpcs:ignore
			return;
		}
		// Do not log the visit if a user is logged in.
		if ( isset( $_SERVER['HTTP_COOKIE'] ) && false !== strpos( $_SERVER['HTTP_COOKIE'], 'wordpress_logged_in_' ) ) { // phpcs:ignore
			return;
		}

		// Get the curent user ip.
		$ip = Helper::get_current_user_ip();

		// Prepare the arguments for writing to db.
		$args = array(
			'ts'           => time(),
			'visitor_id'   => $this->get_visitor_by_ip( $ip ),
			'activity'     => $_SERVER['REQUEST_URI'], // phpcs:ignore
			'description'  => $_SERVER['REQUEST_URI'], // phpcs:ignore
			'ip'           => $ip,
			'code'         => http_response_code(),
			'object_id'    => 0,
			'type'         => 'unknown',
			'hostname'     => gethostbyaddr( $ip ), //phpcs:ignore
			'action'       => 'visit',
			'visitor_type' => $this->get_visitor_type(),
		);

		// Log the visit in the db.
		$this->insert( $args );

		exit;
	}
}
