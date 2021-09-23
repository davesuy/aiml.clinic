<?php
namespace SG_Security\Sg_2fa;

use PHPGangsta_GoogleAuthenticator;
use SG_Security;

/**
 * Class that manages 2FA related services.
 */
class Sg_2fa {
	/**
	 * Roles that should be forced to use 2FA.
	 *
	 * @var array
	 */
	public $roles = array(
		'editor',
		'administrator',
	);

	/**
	 * The constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->google_authenticator = new PHPGangsta_GoogleAuthenticator();
	}

	/**
	 * Generate QR code for specific user.
	 *
	 * @param  object $user The WP_USER object.
	 *
	 * @since  1.0.0
	 */
	public function generate_qr_code( $user ) {
		// Build the title for the authenticator.
		$title = get_bloginfo( 'name' ) . ' (' . $user->user_email . ')';

		// Get the user secret code.
		$secret = get_user_meta( $user->ID, 'sg_security_2fa_secret', true ); // phpcs:ignore

		return $this->google_authenticator->getQRCodeGoogleUrl( $title, $secret );

	}

	/**
	 * Verify the authenticaion code.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $code    One time code from the authenticator app.
	 * @param  int    $user_id The user ID.
	 *
	 * @return bool         True if the code is valid, false otherwise.
	 */
	public function check_authentication_code( $code, $user_id ) {
		// Get the user secret.
		$secret = get_user_meta( $user_id, 'sg_security_2fa_secret', true ); // phpcs:ignore

		// Verify the code.
		return $this->google_authenticator->verifyCode( $secret, $code, 2 );
	}

	/**
	 * Generate users secret codes.
	 *
	 * @since  1.0.0
	 *
	 * @return bool true on success, false otherwise.
	 */
	public function generate_users_secret() {
		$users = get_users(
			array(
				'role__in' => $this->roles,
			)
		);

		if ( empty( $users ) ) {
			return true;
		}

		foreach ( $users as $user ) {
			$this->generate_user_secret( $user->data->ID );
		}

		return true;
	}

	/**
	 * Handle 2FA option change.
	 *
	 * @since  1.0.0
	 *
	 * @param  mixed $old_value Old option value.
	 * @param  mixed $new_value New option value.
	 */
	public function handle_option_change( $old_value, $new_value ) {
		if ( 1 == $new_value ) {
			$this->generate_users_secret();
		}
	}

	/**
	 * Generate the user secret.
	 *
	 * @since  1.0.0
	 *
	 * @param  object $user_id WordPress user ID.
	 *
	 * @return bool         True on success, false on failure.
	 */
	public function generate_user_secret( $user_id ) {
		// Get the user by the user id.
		$user = get_userdata( $user_id );

		if ( empty( array_intersect( $this->roles, $user->roles ) ) ) {
			return $user_id;
		}

		// Check if the user has secret code.
		$secret = get_user_meta( $user_id, 'sg_security_2fa_secret', true ); // phpcs:ignore

		// Bail if the user already has a secret code.
		if ( ! empty( $secret ) ) {
			return $user_id;
		}

		// Add the user secret meta.
		return update_user_meta( // phpcs:ignore
			$user_id,
			'sg_security_2fa_secret',
			$this->google_authenticator->createSecret() // Generate the secret code.
		);
	}

	/**
	 * Validate 2FA login.
	 *
	 * @since  1.0.0
	 */
	public function validate_2fa_login() {
		// Bail if the action is not for authentication.
		if (
			! isset( $_GET['action'] ) ||
			'sgs2fa' !== $_GET['action']
		) {
			return;
		}

		// Bail if the nonce is missing.
		if ( ! isset( $_POST['sg-2fa-nonce'] ) ) {
			$this->load_form( wp_unslash( $_POST['sg-user-id'] ), esc_html__( 'Nonce field is missing!', 'sg-security' ) ); // phpcs:ignore
		}

		// Validate the nonce.
		if ( false === wp_verify_nonce( $_POST['sg-2fa-nonce'], 'sgs2fa_login' ) ) { // phpcs:ignore
			// Redirect to the 2FA form.
			$this->load_form( wp_unslash( $_POST['sg-user-id'] ), esc_html__( 'Invalid nonce!', 'sg-security' ) ); // phpcs:ignore
		}

		$result = $this->check_authentication_code( wp_unslash( $_POST['sgc2facode'] ), wp_unslash( $_POST['sg-user-id'] ) ); // phpcs:ignore

		// Check the result of the authtication.
		if ( false === $result ) {
			$this->load_form( wp_unslash( $_POST['sg-user-id'] ), esc_html__( 'Invalid verification code!', 'sg-security' ) ); // phpcs:ignore
		}

		// Set the auth cookie.
		wp_set_auth_cookie( wp_unslash( $_POST['sg-user-id'] ), intval( wp_unslash( $_POST['rememberme'] ) ) ); // phpcs:ignore

		// Update the user meta if this is the inital 2FA setup.
		if ( isset( $_POST['sgs-2fa-setup'] ) ) {
			update_user_meta( $_POST['sg-user-id'], 'sg_security_2fa_configured', 1 ); // phpcs:ignore
		}

		global $interim_login;
		$interim_login = ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( $_REQUEST['interim-login'], FILTER_VALIDATE_BOOLEAN ) : false; // phpcs:ignore

		if ( $interim_login ) {
			$interim_login = 'success'; // WPCS: override ok.
			login_header( '', '<p class="message">' . __( 'You have logged in successfully.', 'sg-security' ) . '</p>' );
			?>
			</div>
			<?php do_action( 'login_footer' ); ?>
			</body></html>
			<?php
			exit;
		}

		// Get the redirect url.
		$redirect_url = ! empty( $_POST['redirect_to'] ) ? $_POST['redirect_to'] : get_admin_url(); // phpcs:ignore

		// Retirect to the reset url.
		wp_safe_redirect( esc_url_raw( wp_unslash( $redirect_url ) ) );
	}

	/**
	 * Dispaly the two factor authentication form.
	 *
	 * @param  object|int $user    User object or user ID.
	 * @param  string     $error   An error to display, if have such.
	 * @param  int        $hide_qr Whether to show the qr code.
	 *
	 * @since  1.0.0
	 */
	public function load_form( $user, $error = '', $hide_qr = 1 ) {
		// Get the user object if the user ID is provided.
		if ( ! is_object( $user ) ) {
			$user = get_user_by( 'ID', $user );
		}

		// Include the login header if the function doesn't exists.
		if ( ! function_exists( 'login_header' ) ) {
			include_once ABSPATH . 'wp-login.php';
		}

		// Include the template.php if the function doesn't exists.
		if ( ! function_exists( 'submit_button' ) ) {
			require_once ABSPATH . '/wp-admin/includes/template.php';
		}

		login_header();

		$qr_url        = 1 !== $hide_qr ? $this->generate_qr_code( $user ) : false;
		$interim_login = ( isset( $_REQUEST['interim-login'] ) ) ? filter_var( wp_unslash( $_REQUEST['interim-login'] ), FILTER_VALIDATE_BOOLEAN ) : false;
		$rememberme    = ( ! empty( $_REQUEST['rememberme'] ) ) ? true : false;
		$redirect_to   = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : admin_url();
		$login_nonce   = wp_create_nonce( 'sgs2fa_login' );

		include_once SG_Security\DIR . '/templates/2fa-form.php';

		login_footer();
		exit;
	}

	/**
	 * Initialize the 2fa
	 *
	 * @since  1.0.0
	 *
	 * @param  string $user_login The username.
	 * @param  object $user       WP_User object.
	 */
	public function init_2fa( $user_login, $user ) {
		if ( empty( array_intersect( $this->roles, $user->roles ) ) ) {
			return;
		}

		// Bail if the user doesn't have secret.
		if ( empty( get_user_meta( $user->ID, 'sg_security_2fa_secret', true ) ) ) { // phpcs:ignore
			return;
		}

		// Check if the 2fa is configured.
		$is_2fa_configured = (int) get_user_meta( $user->ID, 'sg_security_2fa_configured', true ); // phpcs:ignore

		// Remove the auth cookie.
		wp_clear_auth_cookie();

		// Load the 2fa form.
		$this->load_form( $user, '', $is_2fa_configured );
	}
}
