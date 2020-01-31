<?php
namespace Imagify\Tests\Unit\inc\admin;

use Brain\Monkey;
use Imagify\tests\Unit\TestCase;

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
			$this->assertFalse( imagify_reset_opcache( true ) );
			return;
		}

		// Make sure OPcache is not empty.
		ini_set( 'opcache.restrict_api', '' );
		$this->assertTrue( @opcache_compile_file( $this->test_file_path ) );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ) );

		// Now the real test.
		$this->assertTrue( imagify_reset_opcache( true ) );
		$this->assertFalse( opcache_is_script_cached( $this->test_file_path ) );
	}

	public function testShouldReturnFalseWhenOpcacheIsAvailable() {
		if ( ! filter_var( ini_get( 'opcache.enable' ), FILTER_VALIDATE_BOOLEAN ) ) {
			$this->assertFalse( imagify_reset_opcache( true ) );
			return;
		}

		// Make sure OPcache is not empty.
		ini_set( 'opcache.restrict_api', '' );
		$this->assertTrue( @opcache_compile_file( $this->test_file_path ) );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ) );

		// Now the real test: restrict.
		ini_set( 'opcache.restrict_api', \dirname( $this->function_path ) );

		// Enabled but restricted.
		$this->assertFalse( imagify_reset_opcache( true ) );
		$this->assertTrue( opcache_is_script_cached( $this->test_file_path ) );
		opcache_invalidate( $this->test_file_path );
	}

	public function testShouldReturnFalseWhenOpcacheNotAvailable() {
		if ( filter_var( ini_get( 'opcache.enable' ), FILTER_VALIDATE_BOOLEAN ) ) {
			$this->assertFalse( imagify_reset_opcache( true ) );
			return;
		}

		// Disabled but not restricted.
		ini_set( 'opcache.restrict_api', '' );
		$this->assertFalse( imagify_reset_opcache( true ) );

		// Disabled and restricted.
		ini_set( 'opcache.restrict_api', dirname( $this->function_path ) );
		$this->assertFalse( imagify_reset_opcache( true ) );
	}
}
