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
 * Tests for \Imagify\Tracking\Tracking::track_settings_saved().
 *
 * @covers \Imagify\Tracking\Tracking::track_settings_saved
 * @group  Tracking
 */
class Test_TrackSettingsSaved extends TestCase {

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

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );

		$optin->shouldReceive( 'can_track' )->andReturn( $can_track );

		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'email' => 'user@example.com' ] );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$tracking = new Tracking( $optin, $mixpanel );

		return compact( 'tracking', 'optin', 'mixpanel' );
	}

	/**
	 * Returns a minimal valid settings array for the happy path.
	 *
	 * @return array<string, mixed>
	 */
	private function valid_new_value(): array {
		return [
			'auto_optimize'          => 1,
			'backup'                 => 1,
			'lossless'               => 1,
			'optimization_format'    => 'avif',
			'display_nextgen'        => 1,
			'display_nextgen_method' => 'picture',
			'cdn_url'                => '',
			'resize_larger'          => 1,
			'resize_larger_w'        => 2560,
		];
	}

	/**
	 * Tests that no event is tracked when opt-in is disabled.
	 */
	public function testNoTrackWhenOptInDisabled(): void {
		$mocks    = $this->create_tracking( false );
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldNotReceive( 'track_direct' );

		$tracking->track_settings_saved( [], $this->valid_new_value() );
	}

	/**
	 * Tests the happy path: track_direct() called once with 'Settings Saved'.
	 */
	public function testHappyPathCallsTrackDirect(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$forbidden = [ 'domain', 'wp_version', 'php_version', 'plugin', 'brand', 'application' ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) use ( $forbidden ) {
						foreach ( $forbidden as $key ) {
							if ( array_key_exists( $key, $props ) ) {
								return false;
							}
						}
						return isset( $props['context'], $props['optimization_format'] );
					}
				)
			);

		$tracking->track_settings_saved( [], $this->valid_new_value() );
	}

	/**
	 * Tests that option keys are correctly mapped to Mixpanel property names.
	 */
	public function testPropertyMappingIsCorrect(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$new_value = [
			'auto_optimize'          => '1',
			'backup'                 => '1',
			'lossless'               => '1',
			'optimization_format'    => 'webp',
			'display_nextgen'        => '1',
			'display_nextgen_method' => 'picture',
			'cdn_url'                => 'https://cdn.example.com',
			'resize_larger'          => '1',
			'resize_larger_w'        => '1920',
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return 'webp' === $props['optimization_format']
							&& true === $props['lossless']
							&& true === $props['auto_optimize_on_upload']
							&& true === $props['backup_original']
							&& true === $props['resize_larger_images']
							&& 1920 === $props['resize_larger_width']
							&& true === $props['display_nextgen']
							&& 'picture' === $props['display_nextgen_method']
							&& true === $props['cdn_enabled'];
					}
				)
			);

		$tracking->track_settings_saved( [], $new_value );
	}

	/**
	 * Tests that optimization_format is null when missing.
	 */
	public function testOptimizationFormatNullWhenMissing(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return null === $props['optimization_format'];
					}
				)
			);

		$tracking->track_settings_saved( [], [] );
	}

	/**
	 * Tests that resize_larger_width is cast to int.
	 */
	public function testResizeLargerWidthCastToInt(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return 2560 === $props['resize_larger_width'] && is_int( $props['resize_larger_width'] );
					}
				)
			);

		$tracking->track_settings_saved( [], [ 'resize_larger_w' => '2560' ] );
	}

	/**
	 * Tests that cdn_enabled is false when cdn_url is empty.
	 */
	public function testCdnEnabledFalseWhenCdnUrlEmpty(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return false === $props['cdn_enabled'];
					}
				)
			);

		$tracking->track_settings_saved( [], [ 'cdn_url' => '' ] );
	}

	/**
	 * Tests that boolean options stored as string '0' are normalised to false.
	 */
	public function testBooleanNormalisationFromStringZero(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$new_value = [
			'auto_optimize'   => '0',
			'backup'          => '0',
			'lossless'        => '0',
			'display_nextgen' => '0',
			'resize_larger'   => '0',
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return false === $props['auto_optimize_on_upload']
							&& false === $props['backup_original']
							&& false === $props['lossless']
							&& false === $props['display_nextgen']
							&& false === $props['resize_larger_images'];
					}
				)
			);

		$tracking->track_settings_saved( [], $new_value );
	}

	/**
	 * Tests that boolean options stored as string '1' are normalised to true.
	 */
	public function testBooleanNormalisationFromStringOne(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$new_value = [
			'auto_optimize'   => '1',
			'backup'          => '1',
			'lossless'        => '1',
			'display_nextgen' => '1',
			'resize_larger'   => '1',
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return true === $props['auto_optimize_on_upload']
							&& true === $props['backup_original']
							&& true === $props['lossless']
							&& true === $props['display_nextgen']
							&& true === $props['resize_larger_images'];
					}
				)
			);

		$tracking->track_settings_saved( [], $new_value );
	}

	/**
	 * Tests that auto-managed Mixpanel keys are not present in props passed to track_direct().
	 */
	public function testAutoManagedKeysNotPassedToTrackDirect(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$forbidden = [ 'domain', 'wp_version', 'php_version', 'plugin', 'brand', 'application' ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
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

		$tracking->track_settings_saved( [], $this->valid_new_value() );
	}

	/**
	 * Tests that $old_value is ignored — passing garbage as old value does not change output.
	 */
	public function testOldValueIgnored(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$new_value   = $this->valid_new_value();
		$garbage_old = [
			'completely' => 'irrelevant',
			'data'       => 12345,
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return isset( $props['auto_optimize_on_upload'] );
					}
				)
			);

		$tracking->track_settings_saved( $garbage_old, $new_value );
	}
}
