<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\ServiceProvider;

use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Dependencies\League\Container\Container;
use Imagify\MCP\ServiceProvider;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\ServiceProvider::register().
 *
 * Asserts that the DI wiring is correct: GenerateMissingNextgen is provided by
 * the ServiceProvider and is resolvable from the container (AC #1).
 *
 * These tests live in the Integration suite because ServiceProvider extends
 * Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider
 * (a Strauss-prefixed vendored class) that is not available in the Unit bootstrap.
 *
 * @covers \Imagify\MCP\ServiceProvider::register
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

	/**
	 * Tests that GenerateMissingNextgen is resolvable from the container after registration (AC #1).
	 */
	public function testGenerateMissingNextgenIsResolvableFromContainer(): void {
		$container = new Container();
		$provider  = new ServiceProvider();
		$provider->setContainer( $container );
		$container->addServiceProvider( $provider );

		$ability = $container->get( GenerateMissingNextgen::class );

		$this->assertInstanceOf( GenerateMissingNextgen::class, $ability );
	}
}
