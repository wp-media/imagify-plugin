<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\MCP\ServiceProvider;

use Imagify\MCP\AbilitiesSubscriber;
use Imagify\MCP\ConfigSubscriber;
use Imagify\MCP\ServiceProvider;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\MCP\ServiceProvider::get_subscribers() and provides().
 *
 * @covers \Imagify\MCP\ServiceProvider::get_subscribers
 * @covers \Imagify\MCP\ServiceProvider::provides
 * @group  MCP
 */
class Test_GetSubscribers extends TestCase {

	/**
	 * Tests that get_subscribers() returns ConfigSubscriber and AbilitiesSubscriber.
	 */
	public function testReturnsConfigAndAbilitiesSubscribers(): void {
		$provider    = new ServiceProvider();
		$subscribers = $provider->get_subscribers();

		$this->assertContains( ConfigSubscriber::class, $subscribers );
		$this->assertContains( AbilitiesSubscriber::class, $subscribers );
	}

	/**
	 * Tests that get_subscribers() returns exactly two subscribers.
	 */
	public function testReturnsTwoSubscribers(): void {
		$provider    = new ServiceProvider();
		$subscribers = $provider->get_subscribers();

		$this->assertCount( 2, $subscribers );
	}

	/**
	 * Tests that provides() returns true for ConfigSubscriber.
	 */
	public function testProvidesTrueForConfigSubscriber(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( ConfigSubscriber::class ) );
	}

	/**
	 * Tests that provides() returns true for AbilitiesSubscriber.
	 */
	public function testProvidesTrueForAbilitiesSubscriber(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( AbilitiesSubscriber::class ) );
	}

	/**
	 * Tests that provides() returns false for an unknown service.
	 */
	public function testProvidesFalseForUnknownService(): void {
		$provider = new ServiceProvider();

		$this->assertFalse( $provider->provides( 'some_unknown_service' ) );
	}
}
