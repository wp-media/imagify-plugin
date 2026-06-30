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
	 * Tests that imagify_after_reset_internal_state is registered.
	 */
	public function testMapsImagifyAfterResetInternalStateHook(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'imagify_after_reset_internal_state', $events );
	}

	/**
	 * Tests that the reset hook maps to track_internal_state_reset with priority 10 and 0 args.
	 */
	public function testResetHookConfigurationIsCorrect(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertSame( [ 'track_internal_state_reset', 10, 0 ], $events['imagify_after_reset_internal_state'] );
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

	/**
	 * Tests that update_option_imagify_settings maps to track_settings_saved correctly.
	 */
	public function testMapsUpdateOptionImagifySettingsHook(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'update_option_imagify_settings', $events );
		$this->assertSame( [ 'track_settings_saved', 10, 2 ], $events['update_option_imagify_settings'] );
	}

	/**
	 * Tests that update_site_option_imagify_settings maps to track_settings_saved correctly.
	 */
	public function testMapsUpdateSiteOptionImagifySettingsHook(): void {
		$events = Subscriber::get_subscribed_events();

		$this->assertArrayHasKey( 'update_site_option_imagify_settings', $events );
		$this->assertSame( [ 'track_settings_saved', 10, 2 ], $events['update_site_option_imagify_settings'] );
	}

	/**
	 * Tests that track_settings_saved() delegates to Tracking::track_settings_saved() with array-cast args.
	 */
	public function testTrackSettingsSavedDelegatesToTracking(): void {
		$tracking  = Mockery::mock( Tracking::class );
		$old_value = [ 'optimization_level' => 1 ];
		$new_value = [ 'optimization_level' => 2 ];

		$tracking->shouldReceive( 'track_settings_saved' )
			->once()
			->with( $old_value, $new_value );

		$subscriber = new Subscriber( $tracking );
		$subscriber->track_settings_saved( $old_value, $new_value );
	}

	/**
	 * Tests multisite-style delegation where first arg is the option name string.
	 * On multisite, update_site_option_imagify_settings passes ($option, $new_value, $old_value).
	 * The delegator must cast the string option name to array without error.
	 */
	public function testTrackSettingsSavedDelegatesToTrackingWithMultisiteArgs(): void {
		$tracking    = Mockery::mock( Tracking::class );
		$option_name = 'imagify_settings'; // multisite: first arg is the option name
		$new_value   = [ 'optimization_level' => 2 ];

		$tracking->shouldReceive( 'track_settings_saved' )
			->once()
			->with( (array) $option_name, $new_value );

		$subscriber = new Subscriber( $tracking );
		$subscriber->track_settings_saved( $option_name, $new_value );
	}

	/**
	 * Tests that track_internal_state_reset delegates to the Tracking service.
	 */
	public function testTrackInternalStateResetDelegatesToTracking(): void {
		$tracking = Mockery::mock( Tracking::class );

		$tracking->shouldReceive( 'track_internal_state_reset' )->once();

		$subscriber = new Subscriber( $tracking );
		$subscriber->track_internal_state_reset();
	}
}
