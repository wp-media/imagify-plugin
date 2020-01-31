<?php
namespace Imagify\tests\Unit\Functions;

use Brain\Monkey;
use Imagify\tests\Unit\TestCase;

/**
 * Tests for imagify_reset_opcache()
 *
 * @covers imagify_reset_opcache
 * @group  upgrade
 */
class Test_ImagifyResetOpcache extends TestCase {
	private $enabled_raw;
	private $restricted_raw;
	private $function_path;
	private $test_file_path;

	/**
	 * Prepares the test environment before each test.
	 */
	protected function setUp() {
		parent::setUp();

		// Store initial values.
		$this->enabled_raw    = ini_get( 'opcache.enable' );
		$this->restricted_raw = ini_get( 'opcache.restrict_api' );
		$this->function_path  = IMAGIFY_PLUGIN_ROOT . 'inc/admin/upgrader.php';
		$this->test_file_path = IMAGIFY_PLUGIN_TESTS_ROOT . '../Fixtures/inc/admin/fileToOpcache.php';

		require_once $this->function_path;
	}

	/**
	 * Cleans up the test environment after each test.
	 */
	protected function tearDown() {
		parent::tearDown();

		// Reinit.
		ini_set( 'opcache.enable', $this->enabled_raw );
		ini_set( 'opcache.restrict_api', $this->restricted_raw );
	}

	/**
	 * Test should return false when opcache is not available.
	 */
	public function testShouldReturnFalseWhenOpcacheNotAvailable() {
		if ( ! function_exists( 'opcache_reset' ) ) {
			// We're screwed, opcache is not available.
			$this->assertFalse( imagify_reset_opcache( true ) );
			return;
		}

		// Enabled.
		ini_set( 'opcache.enable', 'on' );
		// Not restricted.
		ini_set( 'opcache.restrict_api', '' );

		// Add a file to opcache.
		$this->assertTrue( @opcache_compile_file( $this->test_file_path ) );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ) );

		// Disabled.
		ini_set( 'opcache.enable', 'off' );

		// Disabled but not restricted.
		$this->assertFalse( imagify_reset_opcache( true ) );

		// Restricted.
		ini_set( 'opcache.restrict_api', \dirname( $this->function_path ) );

		// Disabled and restricted.
		$this->assertFalse( imagify_reset_opcache( true ) );

		// Enabled.
		ini_set( 'opcache.enable', 'on' );

		// Enabled but restricted.
		$this->assertFalse( imagify_reset_opcache( true ) );

		// Not restricted.
		ini_set( 'opcache.restrict_api', '' );

		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ) );
		opcache_invalidate( $this->test_file_path );
	}

	/**
	 * Test should return true when opcache is available.
	 */
	public function testShouldReturnTrueWhenOpcacheAvailable() {
		if ( ! function_exists( 'opcache_reset' ) ) {
			// We're screwed, opcache is not available.
			$this->assertFalse( imagify_reset_opcache( true ) );
			return;
		}

		// Enabled.
		ini_set( 'opcache.enable', 'on' );
		// Not restricted.
		ini_set( 'opcache.restrict_api', '' );

		// Add a file to opcache.
		$this->assertTrue( @opcache_compile_file( $this->test_file_path ) );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ) );

		$this->assertTrue( imagify_reset_opcache( true ) );

		$this->assertFalse( opcache_is_script_cached( $this->test_file_path ) );
	}
}
