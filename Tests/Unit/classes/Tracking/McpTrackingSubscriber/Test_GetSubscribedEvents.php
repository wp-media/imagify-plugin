<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\McpTrackingSubscriber;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\McpTrackingSubscriber;

/**
 * Tests for \Imagify\Tracking\McpTrackingSubscriber::get_subscribed_events().
 *
 * @covers \Imagify\Tracking\McpTrackingSubscriber::get_subscribed_events
 * @group  Tracking
 */
class Test_GetSubscribedEvents extends TestCase {

	/**
	 * Tests that both MCP hooks are registered.
	 *
	 * @dataProvider provideExpectedHooks
	 */
	public function testRegistersHook( string $hook ): void {
		$this->assertArrayHasKey( $hook, McpTrackingSubscriber::get_subscribed_events() );
	}

	/**
	 * @return array<string, array<int, string>>
	 */
	public function provideExpectedHooks(): array {
		return [
			'ability executed' => [ 'imagify_mcp_ability_executed' ],
			'permission denied' => [ 'imagify_mcp_permission_denied' ],
		];
	}

	/**
	 * Tests that each hook maps to the correct method and argument count.
	 *
	 * @dataProvider provideHookConfig
	 */
	public function testHookConfig( string $hook, string $method, int $accepted_args ): void {
		$events = McpTrackingSubscriber::get_subscribed_events();

		$this->assertSame( $method, $events[ $hook ][0] );
		$this->assertSame( $accepted_args, $events[ $hook ][2] );
	}

	/**
	 * @return array<string, array{0: string, 1: string, 2: int}>
	 */
	public function provideHookConfig(): array {
		return [
			'ability executed -> on_ability_executed, 5 args'   => [ 'imagify_mcp_ability_executed', 'on_ability_executed', 5 ],
			'permission denied -> on_permission_denied, 3 args' => [ 'imagify_mcp_permission_denied', 'on_permission_denied', 3 ],
		];
	}
}
