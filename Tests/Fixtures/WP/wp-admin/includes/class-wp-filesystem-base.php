<?php
/**
 * Minimal stub of WP core's WP_Filesystem_Base, so that Imagify_Filesystem can be
 * loaded in the unit test suite without a WordPress installation.
 *
 * Only the class shape is needed: unit tests instantiate Imagify_Filesystem via
 * ReflectionClass::newInstanceWithoutConstructor(), so no behaviour is required here.
 *
 * @package Imagify\Tests\Fixtures
 */

if ( ! class_exists( 'WP_Filesystem_Base' ) ) {
	/**
	 * Stub for WP_Filesystem_Base.
	 */
	class WP_Filesystem_Base {

		/**
		 * Constructor.
		 *
		 * @param mixed $arg Unused.
		 */
		public function __construct( $arg = '' ) {} // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
	}
}
