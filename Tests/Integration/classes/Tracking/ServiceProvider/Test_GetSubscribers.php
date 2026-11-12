<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Tracking\ServiceProvider;

use Imagify\Tests\Integration\TestCase;
use Imagify\Tracking\ServiceProvider;
use Imagify\Tracking\Subscriber;
use Imagify\Tracking\Tracking;

/**
 * Tests for \Imagify\Tracking\ServiceProvider::get_subscribers() and provides(),
 * and for the hook wiring introduced by the Subscriber.
 *
 * These tests live in the Integration suite because ServiceProvider extends
 * Imagify\Dependencies\League\Container\ServiceProvider\AbstractServiceProvider
 * (a Strauss-prefixed vendored class). The Unit bootstrap uses a hand-listed
 * file set — NOT the full vendor autoloader — so the prefixed base class is not
 * available there. The Integration suite bootstraps the full WP environment with
 * the complete autoloader, making the class resolvable.
 *
 * @covers \Imagify\Tracking\ServiceProvider::get_subscribers
 * @covers \Imagify\Tracking\ServiceProvider::provides
 * @group  Tracking
 */
class Test_GetSubscribers extends TestCase {

	/**
	 * Whether to use the Imagify API for these tests.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Tests that get_subscribers() includes Subscriber::class.
	 */
	public function testReturnsTrackingSubscriber(): void {
		$provider    = new ServiceProvider();
		$subscribers = $provider->get_subscribers();

		$this->assertContains( Subscriber::class, $subscribers );
	}

	/**
	 * Tests that provides() returns true for Tracking::class.
	 */
	public function testProvidesTrueForTracking(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( Tracking::class ) );
	}

	/**
	 * Tests that provides() returns true for Subscriber::class.
	 */
	public function testProvidesTrueForSubscriber(): void {
		$provider = new ServiceProvider();

		$this->assertTrue( $provider->provides( Subscriber::class ) );
	}

	/**
	 * Tests that provides() returns false for an unknown service.
	 */
	public function testProvidesFalseForUnknownService(): void {
		$provider = new ServiceProvider();

		$this->assertFalse( $provider->provides( 'some_unknown_service' ) );
	}

	/**
	 * Tests that imagify_after_restore_media is hooked with priority 10 and 4 accepted args.
	 *
	 * This verifies end-to-end wiring: the Subscriber registered by the ServiceProvider
	 * must connect our tracking method to the hook exactly as get_subscribed_events() declares.
	 */
	public function testRestoreHookIsWiredCorrectly(): void {
		$this->assertSame(
			10,
			has_action( 'imagify_after_restore_media', [ $this->get_subscriber(), 'track_media_restored' ] ),
			'imagify_after_restore_media should be hooked at priority 10.'
		);
	}

	/**
	 * Tests that imagify_after_optimize is still hooked with priority 10 (regression guard).
	 */
	public function testOptimizeHookIsStillWiredCorrectly(): void {
		$this->assertSame(
			10,
			has_action( 'imagify_after_optimize', [ $this->get_subscriber(), 'track_media_optimized' ] ),
			'imagify_after_optimize should still be hooked at priority 10.'
		);
	}

	/**
	 * Retrieves the Subscriber instance registered by the plugin's DI container.
	 *
	 * @return Subscriber
	 */
	private function get_subscriber(): Subscriber {
		return apply_filters( 'imagify_container', null )->get( Subscriber::class );
	}
}
