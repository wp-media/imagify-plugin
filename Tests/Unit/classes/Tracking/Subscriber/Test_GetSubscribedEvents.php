<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Subscriber;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Subscriber;
use Imagify\Tracking\Tracking;
use Imagify\Optimization\Process\ProcessInterface;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Subscriber::get_subscribed_events().
 *
 * @covers \Imagify\Tracking\Subscriber::get_subscribed_events
 * @group  Tracking
 */
class Test_GetSubscribedEvents extends TestCase {

	/**
	 * Tests that get_subscribed_events() maps imagify_after_optimize correctly.
	 */
	public function testMapsImagifyAfterOptimizeHook(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'imagify_after_optimize', $events );
	}

	/**
	 * Tests that the hook maps to track_media_optimized with priority 10 and 2 args.
	 */
	public function testHookConfigurationIsCorrect(): void {
		$events = Subscriber::get_subscribed_events();

		$mapping = $events['imagify_after_optimize'];

		$this->assertSame( [ 'track_media_optimized', 10, 2 ], $mapping );
	}

	/**
	 * Tests that track_media_optimized delegates to the Tracking service.
	 */
	public function testTrackMediaOptimizedDelegatesToTracking(): void {
		$tracking = Mockery::mock( Tracking::class );
		$process  = Mockery::mock( ProcessInterface::class );
		$item     = [ 'sizes_done' => [ 'full' ] ];

		$tracking->shouldReceive( 'track_media_optimized' )
			->once()
			->with( $process, $item );

		$subscriber = new Subscriber( $tracking );
		$subscriber->track_media_optimized( $process, $item );
	}
}
