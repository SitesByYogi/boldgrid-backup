<?php
/**
 * File: class-site-check.php
 *
 * Check the integrity of the website.
 *
 * @link       https://www.boldgrid.com
 * @since      1.9.0
 *
 * @package    Boldgrid\Backup
 * @subpackage Boldgrid\Backup\Cron
 * @copyright  BoldGrid
 * @author     BoldGrid <support@boldgrid.com>
 *
 * phpcs:disable WordPress.WP.AlternativeFunctions
 */

namespace Boldgrid\Backup\Cron;

/**
 * Class: Site_Check.
 *
 * @since 1.9.0
 */
class Site_Check {
	/**
	 * Maximum restoration attempts.
	 *
	 * @since  1.9.0
	 * @access private
	 * @static
	 *
	 * @var int
	 */
	private static $max_restore_attempts = 2;

	/**
	 * Test resukt output from the wp-test script.
	 *
	 * @since  1.10.0
	 * @access private
	 * @static
	 *
	 * @var string
	 */
	private static $wp_test_result;

	/**
	 * Run the site check process, to determine if the site needs to be restored from backup.
	 *
	 * Decide if a restoration should be completed.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cron\Info::has_errors()
	 * @see self::does_wp_load()
	 * @see \Boldgrid\Backup\Cron\Info::has_arg_flag()
	 *
	 * @return bool
	 */
	public static function should_restore() {
		$should_restore = false;

		// Abort if there are errors retrieving information.
		if ( Info::has_errors() ) {
			return false;
		}

		// If the "auto_recovery" argument is passed and set to "1", then set-up for auto-restore.
		$auto_restore      = false !== Info::has_arg_flag( 'auto_recovery' ) &&
			'1' === Info::get_cli_args()['auto_recovery'];
		$mode              = Info::get_mode();
		$attempts_exceeded = Info::get_info()['restore_attempts'] >= self::$max_restore_attempts;

		// If "check" flag was passed and there have not been too many restoration attempts.
		if ( 'check' === $mode && ! $attempts_exceeded ) {
			if ( ! self::check() && $auto_restore ) {
				$should_restore = true;
			}
		}

		// If "Restore" flag was possed, which forces a restoration.
		if ( 'restore' === $mode ) {
			$should_restore = true;
		}

		return $should_restore;
	}

	/**
	 * Is the site reachable.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid\Backup\Cron\Info::get_info()
	 * @see \Boldgrid_Backup_Url_Helper::call_url()
	 *
	 * @return bool;
	 */
	public static function is_siteurl_reachable() {
		$result = false;

		require_once __DIR__ . '/class-boldgrid-backup-url-helper.php';

		if ( ! empty( Info::get_info()['siteurl'] ) ) {
			$response = ( new \Boldgrid_Backup_Url_Helper() )->call_url(
				Info::get_info()['siteurl'],
				$status,
				$errorno,
				$error
			);

			if ( 200 === $status ) {
				$result = true;
			}
		}

		return $result;
	}

	/**
	 * Does WordPress load via PHP.
	 *
	 * @since 1.9.0
	 * @static
	 *
	 * @see \Boldgrid_Backup_Admin_Cli::call_command()
	 *
	 * @return bool
	 */
	public static function does_wp_load() {
		self::$wp_test_result = \Boldgrid_Backup_Admin_Cli::call_command(
			'cd ' . __DIR__ . '; php -qf wp-test.php',
			$success
		);

		return $success;
	}

	/**
	 * Check if a port is open.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @link https://www.php.net/manual/en/function.fsockopen.php
	 *
	 * @param  int    $port    Port number (1-65535).
	 * @param  string $host    Optional hostname; defaults to "localhost".
	 * @param  int    $timeout Connect timeout, in seconds; defaults to 5.
	 * @param  int    $errno   If provided, holds the system level error number that occurred in the system-level connect() call.
	 * @param  string $errstr  The error message as a string.
	 * @return bool
	 */
	public static function check_port( $port, $host = 'localhost', $timeout = 5, &$errno, &$errstr ) {
		// Check for valid port reange.
		if ( 0 > $port || 65535 < $port ) {
			return false;
		}

		$res = @fsockopen( $host, $port, $errno, $errstr, $timeout ); // phpcs:ignore Generic.PHP.NoSilencedErrors

		if ( is_resource( $res ) ) {
			fclose( $res );
			return true;
		}

		return false;
	}

	/**
	 * Perform a site check.
	 *
	 * @since 1.10.0
	 * @static
	 *
	 * @see self::does_wp_load()
	 * @see Log::write()
	 *
	 * @todo More checks and login coming soon.
	 *
	 * @param  bool $print Print status information.  Defaults to TRUE.
	 * @return bool
	 */
	public static function check( $print = true ) {
		$status  = self::does_wp_load();
		$output  = json_decode( self::$wp_test_result, true );
		$message = 'Site Check: ' . ( $status ? 'Ok' : 'Failed' ) .
			( ! $status && is_array( $output ) ? ': ' . self::$wp_test_result : '' );
		Log::write( $message, ( $status ? LOG_INFO : LOG_ERR ) );

		if ( $print ) {
			echo $message . PHP_EOL; // phpcs:ignore WordPress.XSS.EscapeOutput
		}

		return $status;
	}
}
