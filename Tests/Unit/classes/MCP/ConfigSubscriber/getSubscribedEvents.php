<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\ConfigSubscriber;

use Imagify\MCP\ConfigSubscriber;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\MCP\ConfigSubscriber::get_subscribed_events().
 *
 * @covers \Imagify\MCP\ConfigSubscriber::get_subscribed_events
 * @group  MCP
 */
class Test_GetSubscribedEvents extends TestCase {

	/**
	 * Tests that get_subscribed_events() returns the correct filter hook mapping.
	 */
	public function testReturnsMcpAdapterDefaultServerConfigMapping(): void {
		$events = ConfigSubscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'mcp_adapter_default_server_config', $events );
		$this->assertSame( 'customize_mcp_server', $events['mcp_adapter_default_server_config'] );
	}

	/**
	 * Tests that get_subscribed_events() returns exactly one event entry.
	 */
	public function testReturnsExactlyOneEvent(): void {
		$events = ConfigSubscriber::get_subscribed_events();

		$this->assertCount( 1, $events );
	}
}
