<?php
namespace Imagify\Tests\Unit\inc\admin;

use Brain\Monkey;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers imagify_reset_opcache
 * @group  upgrade
 */
class Test_ImagifyResetOpcache extends TestCase {
	private $restricted_raw;
	private $function_path;
	private $test_file_path;

	protected function setUp() {
		parent::setUp();

		// Store initial values.
		$this->restricted_raw = ini_get( 'opcache.restrict_api' );
		$this->function_path  = IMAGIFY_PLUGIN_ROOT . 'inc/admin/upgrader.php';
		$this->test_file_path = IMAGIFY_PLUGIN_TESTS_ROOT . '../Fixtures/inc/admin/fileToOpcache.php';

		require_once $this->function_path;
	}

	protected function tearDown() {
		parent::tearDown();

		// Reset.
		ini_set( 'opcache.restrict_api', $this->restricted_raw );
	}

	public function testShouldReturnTrueWhenOpcacheAvailable() {
		if ( ! filter_var( ini_get( 'opcache.enable' ), FILTER_VALIDATE_BOOLEAN ) ) {
			$this->assertFalse( imagify_reset_opcache( true ), 'imagify_reset_opcache() returned true while OPcache is disabled.' );
			return;
		}

		// Make sure OPcache is not empty.
		ini_set( 'opcache.restrict_api', '' );
		@opcache_compile_file( $this->test_file_path, 'Test file was not added to cache.' );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ), 'Test file is not cached.' );

		// Now the real test.
		$this->assertTrue( imagify_reset_opcache( true ), 'imagify_reset_opcache() failed to reset OPcache.' );
		$this->assertFalse( opcache_is_script_cached( $this->test_file_path ), 'Test file has not been removed from cache.' );
	}

	public function testShouldReturnFalseWhenOpcacheIsAvailable() {
		if ( ! filter_var( ini_get( 'opcache.enable' ), FILTER_VALIDATE_BOOLEAN ) ) {
			$this->assertFalse( imagify_reset_opcache( true ), 'imagify_reset_opcache() returned true while OPcache is disabled.' );
			return;
		}

		// Make sure OPcache is not empty.
		ini_set( 'opcache.restrict_api', '' );
		@opcache_compile_file( $this->test_file_path, 'Test file was not added to cache.' );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ), 'Test file is not cached.' );

		// Now the real test: restrict.
		ini_set( 'opcache.restrict_api', \dirname( $this->function_path ) );

		// Enabled but restricted.
		$this->assertFalse( imagify_reset_opcache( true ), 'imagify_reset_opcache() reset OPcache while it should not have.' );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ), 'Test file has been removed from cache.' );
		opcache_invalidate( $this->test_file_path );
	}

	public function testShouldReturnFalseWhenOpcacheNotAvailable() {
		if ( filter_var( ini_get( 'opcache.enable' ), FILTER_VALIDATE_BOOLEAN ) ) {
			$this->assertFalse( imagify_reset_opcache( true ), 'imagify_reset_opcache() returned true while OPcache is disabled.' );
			return;
		}

		// Disabled but not restricted.
		ini_set( 'opcache.restrict_api', '' );
		$this->assertFalse( imagify_reset_opcache( true ), 'imagify_reset_opcache() reset OPcache while it should not have.' );

		// Disabled and restricted.
		ini_set( 'opcache.restrict_api', dirname( $this->function_path ) );
		$this->assertFalse( imagify_reset_opcache( true ), 'imagify_reset_opcache() reset OPcache while it should not have.' );
	}
}
