<?php
/**
 * WP_CLI stub for unit tests.
 *
 * Provides no-op static methods so that code calling \WP_CLI::log() /
 * \WP_CLI::warning() does not fatal in a WordPress-less test environment.
 * Individual tests can replace these via Patchwork if they need to capture calls.
 */

if ( ! class_exists( 'WP_CLI' ) ) {
	/**
	 * Stub class for WP_CLI in unit tests.
	 */
	class WP_CLI {

		/**
		 * Captured log messages (reset per test via ::reset()).
		 *
		 * @var array
		 */
		public static $log_messages = [];

		/**
		 * Captured warning messages.
		 *
		 * @var array
		 */
		public static $warning_messages = [];

		/**
		 * Captured error messages.
		 *
		 * @var array
		 */
		public static $error_messages = [];

		/**
		 * Captured success messages.
		 *
		 * @var array
		 */
		public static $success_messages = [];

		/**
		 * Logs a message.
		 *
		 * @param string $message The message to log.
		 */
		public static function log( $message ) {
			static::$log_messages[] = $message;
		}

		/**
		 * Emits a warning message.
		 *
		 * @param string $message The warning message.
		 */
		public static function warning( $message ) {
			static::$warning_messages[] = $message;
		}

		/**
		 * Emits an error message.
		 *
		 * @param string $message  The error message.
		 * @param bool   $do_exit  Whether to exit (no-op in tests).
		 */
		public static function error( $message, $do_exit = true ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
			static::$error_messages[] = $message;
		}

		/**
		 * Emits a success message.
		 *
		 * @param string $message The success message.
		 */
		public static function success( $message ) {
			static::$success_messages[] = $message;
		}

		/**
		 * Resets all captured messages between tests.
		 */
		public static function reset() {
			static::$log_messages     = [];
			static::$warning_messages = [];
			static::$error_messages   = [];
			static::$success_messages = [];
		}
	}
}

if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
