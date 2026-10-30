<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\ServiceProvider;

use Imagify\MCP\ServiceProvider;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\ServiceProvider::provides().
 *
 * Integration suite: the Strauss-prefixed AbstractServiceProvider is not loaded by the unit bootstrap.
 *
 * @covers \Imagify\MCP\ServiceProvider::provides
 * @group  MCP
 */
class Test_Provides extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Tests whether the provider provides the given service.
	 *
	 * @dataProvider configTestData
	 *
	 * @param string $service  Service identifier.
	 * @param bool   $expected Whether the provider provides it.
	 */
	public function testShouldReturnExpected( $service, $expected ): void {
		$this->assertSame( $expected, ( new ServiceProvider() )->provides( $service ) );
	}
}
