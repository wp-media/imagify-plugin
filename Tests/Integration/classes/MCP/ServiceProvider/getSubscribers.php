<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\MCP\ServiceProvider;

use Imagify\MCP\ServiceProvider;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\MCP\ServiceProvider::get_subscribers().
 *
 * Integration suite: the Strauss-prefixed AbstractServiceProvider is not loaded by the unit bootstrap.
 *
 * @covers \Imagify\MCP\ServiceProvider::get_subscribers
 * @group  MCP
 */
class Test_GetSubscribers extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Tests the subscribers returned by the provider.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $expected Expected subscribers.
	 */
	public function testShouldReturnExpected( $expected ): void {
		$this->assertSame( $expected, ( new ServiceProvider() )->get_subscribers() );
	}
}
