<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Tracking;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Tracking;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Tracking::track_internal_state_reset().
 *
 * @covers \Imagify\Tracking\Tracking::track_internal_state_reset
 * @group  Tracking
 */
class Test_TrackInternalStateReset extends TestCase {

	/**
	 * Creates a Tracking instance with mocked dependencies.
	 *
	 * @param bool $can_track Whether tracking is allowed.
	 *
	 * @return array{tracking: Tracking, optin: \Mockery\MockInterface&Optin, mixpanel: \Mockery\MockInterface&TrackingPlugin}
	 */
	private function create_tracking( bool $can_track = true ): array {
		$optin    = Mockery::mock( Optin::class );
		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$optin->shouldReceive( 'can_track' )->andReturn( $can_track );

		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'email' => 'user@example.com' ] );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$tracking = new Tracking( $optin, $mixpanel );

		return compact( 'tracking', 'optin', 'mixpanel' );
	}

	/**
	 * Tests that no event is tracked when opt-in is disabled.
	 */
	public function testDoesNotTrackWhenOptInDisabled(): void {
		$mocks    = $this->create_tracking( false );
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldNotReceive( 'track_direct' );

		Functions\when( 'is_multisite' )->justReturn( false );

		$tracking->track_internal_state_reset();
	}

	/**
	 * Tests that track_direct is called once with the correct event name when opt-in is enabled.
	 */
	public function testTracksWhenOptInEnabled(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		Functions\when( 'is_multisite' )->justReturn( false );

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Internal State Reset',
				Mockery::type( 'array' )
			);

		$tracking->track_internal_state_reset();
	}

	/**
	 * Tests that is_multisite is included and reflects false on a single-site install.
	 */
	public function testEventContainsIsMultisiteFalseOnSingleSite(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		Functions\when( 'is_multisite' )->justReturn( false );

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Internal State Reset',
				Mockery::on(
					function ( array $props ) {
						return array_key_exists( 'is_multisite', $props ) && $props['is_multisite'] === false;
					}
				)
			);

		$tracking->track_internal_state_reset();
	}

	/**
	 * Tests that is_multisite is true on a multisite install.
	 */
	public function testEventContainsIsMultisiteTrueOnMultisite(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		Functions\when( 'is_multisite' )->justReturn( true );

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Internal State Reset',
				Mockery::on(
					function ( array $props ) {
						return $props['is_multisite'] === true;
					}
				)
			);

		$tracking->track_internal_state_reset();
	}

	/**
	 * Tests that auto-merged properties (domain, wp_version, etc.) are NOT set inline.
	 */
	public function testEventDoesNotContainAutoMergedProperties(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		Functions\when( 'is_multisite' )->justReturn( false );

		$forbidden = [ 'domain', 'wp_version', 'php_version', 'plugin', 'brand', 'application' ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Internal State Reset',
				Mockery::on(
					function ( array $props ) use ( $forbidden ) {
						foreach ( $forbidden as $key ) {
							if ( array_key_exists( $key, $props ) ) {
								return false;
							}
						}
						return true;
					}
				)
			);

		$tracking->track_internal_state_reset();
	}
}
