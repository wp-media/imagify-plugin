<?php
/**
 * Minimal stub of WP core's WP_Filesystem_Direct, so that Imagify_Filesystem can be
 * loaded in the unit test suite without a WordPress installation.
 *
 * @package Imagify\Tests\Fixtures
 */

if ( ! class_exists( 'WP_Filesystem_Direct' ) ) {
	/**
	 * Stub for WP_Filesystem_Direct.
	 */
	class WP_Filesystem_Direct extends WP_Filesystem_Base {
	}
}
