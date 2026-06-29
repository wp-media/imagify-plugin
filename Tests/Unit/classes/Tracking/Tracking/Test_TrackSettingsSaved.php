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
			'optimization_level' => 1,
			'auto_optimize'      => 1,
			'resize_larger'      => 1,
			'convert_to_webp'    => 1,
			'convert_to_avif'    => 0,
			'backup'             => 1,
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
						return isset( $props['context'], $props['optimization_level'] );
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
			'optimization_level' => '2',
			'auto_optimize'      => '1',
			'resize_larger'      => '1',
			'convert_to_webp'    => '1',
			'convert_to_avif'    => '1',
			'backup'             => '1',
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return 2 === $props['optimization_level']
							&& true === $props['auto_optimize_on_upload']
							&& true === $props['resize_larger_images']
							&& true === $props['next_gen_images_webp']
							&& true === $props['next_gen_images_avif']
							&& true === $props['backup_original'];
					}
				)
			);

		$tracking->track_settings_saved( [], $new_value );
	}

	/**
	 * Tests that optimization_level is cast to int.
	 */
	public function testOptimizationLevelCastToInt(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$new_value = [ 'optimization_level' => '0' ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return 0 === $props['optimization_level'] && is_int( $props['optimization_level'] );
					}
				)
			);

		$tracking->track_settings_saved( [], $new_value );
	}

	/**
	 * Tests that missing optimization_level results in null.
	 */
	public function testOptimizationLevelNullWhenMissing(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return null === $props['optimization_level'];
					}
				)
			);

		$tracking->track_settings_saved( [], [] );
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
			'resize_larger'   => '0',
			'convert_to_webp' => '0',
			'convert_to_avif' => '0',
			'backup'          => '0',
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return false === $props['auto_optimize_on_upload']
							&& false === $props['resize_larger_images']
							&& false === $props['next_gen_images_webp']
							&& false === $props['next_gen_images_avif']
							&& false === $props['backup_original'];
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
			'resize_larger'   => '1',
			'convert_to_webp' => '1',
			'convert_to_avif' => '1',
			'backup'          => '1',
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return true === $props['auto_optimize_on_upload']
							&& true === $props['resize_larger_images']
							&& true === $props['next_gen_images_webp']
							&& true === $props['next_gen_images_avif']
							&& true === $props['backup_original'];
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

		$new_value  = $this->valid_new_value();
		$garbage_old = [ 'completely' => 'irrelevant', 'data' => 12345 ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Settings Saved',
				Mockery::on(
					function ( array $props ) {
						return isset( $props['optimization_level'] );
					}
				)
			);

		$tracking->track_settings_saved( $garbage_old, $new_value );
	}
}
