<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\AbilitiesSubscriber;

use Imagify\MCP\AbilitiesSubscriber;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\MCP\AbilitiesSubscriber::get_subscribed_events().
 *
 * @covers \Imagify\MCP\AbilitiesSubscriber::get_subscribed_events
 * @group  MCP
 */
class Test_GetSubscribedEvents extends TestCase {

	/**
	 * Tests that get_subscribed_events() includes the categories_init hook mapping.
	 */
	public function testIncludesCategoriesInitHook(): void {
		$events = AbilitiesSubscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'wp_abilities_api_categories_init', $events );
		$this->assertSame( 'register_categories', $events['wp_abilities_api_categories_init'] );
	}

	/**
	 * Tests that get_subscribed_events() includes the api_init hook mapping.
	 */
	public function testIncludesApiInitHook(): void {
		$events = AbilitiesSubscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'wp_abilities_api_init', $events );
		$this->assertSame( 'register_abilities', $events['wp_abilities_api_init'] );
	}

	/**
	 * Tests that get_subscribed_events() returns exactly two event entries.
	 */
	public function testReturnsExactlyTwoEvents(): void {
		$events = AbilitiesSubscriber::get_subscribed_events();

		$this->assertCount( 2, $events );
	}
}
