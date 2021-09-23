<?php
namespace SG_Security\Cli;

/**
 * SG Security Cli main plugin class
 */
class Cli {
	/**
	 * Init SG Security .
	 *
	 * @version
	 */
	public function register_commands() {
		// Optimize commands.
		\WP_CLI::add_command( 'sg secure', 'SG_Security\Cli\Cli_Secure' );

		// Limits login attempts.
		\WP_CLI::add_command( 'sg limit-login-attempts', 'SG_Security\Cli\Cli_Limit_Login_Attempts' );

		// Login access configuration.
		\WP_CLI::add_command( 'sg login-access', 'SG_Security\Cli\Cli_Login_Access' );
	}
}
