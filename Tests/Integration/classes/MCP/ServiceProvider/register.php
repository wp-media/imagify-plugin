<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\ServiceProvider;

use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\MCP\ServiceProvider;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\ServiceProvider::register().
 *
 * Asserts that GenerateMissingNextgen and Bulk are declared in the ServiceProvider's
 * provides array. Full container resolution is not tested here because it requires
 * Bulk::get_instance() to be called in a live WP environment, which is not reliably
 * available in the integration test environment.
 *
 * These tests live in the Integration suite because ServiceProvider extends
 * Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider
 * (a Strauss-prefixed vendored class) that is not available in the Unit bootstrap.
 *
 * @covers \Imagify\MCP\ServiceProvider::provides
 * @group  MCP
 */
class Test_Register extends TestCase {

	protected $useApi = false;

	/**
	 * Tests that GenerateMissingNextgen is listed in the ServiceProvider's provides array.
	 */
	public function testProvidesGenerateMissingNextgen(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( GenerateMissingNextgen::class ) );
	}

	/**
	 * Tests that Bulk::class is listed in the ServiceProvider's provides array.
	 */
	public function testProvidesBulk(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( Bulk::class ) );
	}
}
