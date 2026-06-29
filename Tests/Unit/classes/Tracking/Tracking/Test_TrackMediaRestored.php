<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Tracking;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin;
use Imagify\Media\MediaInterface;
use Imagify\Optimization\Process\ProcessInterface;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Tracking;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Tracking\Tracking::track_media_restored().
 *
 * @covers \Imagify\Tracking\Tracking::track_media_restored
 * @group  Tracking
 */
class Test_TrackMediaRestored extends TestCase {

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
		Functions\when( 'is_wp_error' )->alias( function ( $thing ) {
			return $thing instanceof WP_Error;
		} );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		$tracking = new Tracking( $optin, $mixpanel );

		return compact( 'tracking', 'optin', 'mixpanel' );
	}

	/**
	 * Creates a minimal process mock with a media context.
	 *
	 * @param string $context The media context ('wp', 'custom-folders', etc.).
	 *
	 * @return ProcessInterface&\Mockery\MockInterface
	 */
	private function create_process( string $context = 'wp' ) {
		$media   = Mockery::mock( MediaInterface::class );
		$process = Mockery::mock( ProcessInterface::class );

		$media->shouldReceive( 'get_context' )->andReturn( $context );
		$process->shouldReceive( 'get_media' )->andReturn( $media );

		return $process;
	}

	/**
	 * Tests that no event is tracked when opt-in is disabled.
	 */
	public function testNoTrackWhenOptInDisabled(): void {
		$mocks    = $this->create_tracking( false );
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldNotReceive( 'track_direct' );

		$process = Mockery::mock( ProcessInterface::class );

		$tracking->track_media_restored( $process, true, [], [] );
	}

	/**
	 * Tests that no event is tracked when the response is a WP_Error.
	 */
	public function testNoTrackWhenResponseIsWpError(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldNotReceive( 'track_direct' );

		$process = Mockery::mock( ProcessInterface::class );

		$tracking->track_media_restored( $process, new WP_Error( 'copy_failed', 'Failed.' ), [], [] );
	}

	/**
	 * Tests the happy path: track_direct is called once with 'Media Restored' and expected properties.
	 * Also asserts that auto-injected properties (domain, wp_version, etc.) are NOT set inline.
	 */
	public function testHappyPathCallsTrackDirect(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_process( 'wp' );

		$data = [
			'level'  => 1,
			'status' => 'optimized',
			'sizes'  => [],
		];

		$forbidden = [ 'domain', 'wp_version', 'php_version', 'plugin', 'brand', 'application' ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Restored',
				Mockery::on(
					function ( array $props ) use ( $forbidden ) {
						foreach ( $forbidden as $key ) {
							if ( array_key_exists( $key, $props ) ) {
								return false;
							}
						}
						return isset( $props['context'], $props['media_context'] )
							&& $props['media_context'] === 'wp'
							&& $props['optimization_level'] === 1;
					}
				)
			);

		$tracking->track_media_restored( $process, true, [], $data );
	}

	/**
	 * Tests that optimization_level is null when $data['level'] is not an integer.
	 */
	public function testOptimizationLevelIsNullWhenLevelIsFalse(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_process();

		$data = [ 'level' => false, 'sizes' => [] ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Restored',
				Mockery::on( fn( array $props ) => $props['optimization_level'] === null )
			);

		$tracking->track_media_restored( $process, true, [], $data );
	}

	/**
	 * Tests that optimization_level is null when $data has no 'level' key.
	 */
	public function testOptimizationLevelIsNullWhenLevelMissing(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_process();

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Restored',
				Mockery::on( fn( array $props ) => $props['optimization_level'] === null )
			);

		$tracking->track_media_restored( $process, true, [], [] );
	}

	/**
	 * Tests that next_gen_format is 'avif' when the AVIF size succeeded.
	 */
	public function testNextGenFormatIsAvifWhenAvifSucceeds(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_process();

		$avif_key = 'full' . ProcessInterface::AVIF_SUFFIX;
		$data     = [
			'level' => 1,
			'sizes' => [ $avif_key => [ 'success' => true ] ],
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Restored',
				Mockery::on( fn( array $props ) => $props['next_gen_format'] === 'avif' )
			);

		$tracking->track_media_restored( $process, true, [], $data );
	}

	/**
	 * Tests that next_gen_format is 'webp' when only the WebP size succeeded.
	 */
	public function testNextGenFormatIsWebpWhenWebpSucceeds(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_process();

		$webp_key = 'full' . ProcessInterface::WEBP_SUFFIX;
		$data     = [
			'level' => 1,
			'sizes' => [ $webp_key => [ 'success' => true ] ],
		];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Restored',
				Mockery::on( fn( array $props ) => $props['next_gen_format'] === 'webp' )
			);

		$tracking->track_media_restored( $process, true, [], $data );
	}

	/**
	 * Tests that next_gen_format is null when neither AVIF nor WebP succeeded.
	 */
	public function testNextGenFormatIsNullWhenNoNextGenSucceeded(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_process();

		$data = [ 'level' => 1, 'sizes' => [] ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Restored',
				Mockery::on( fn( array $props ) => $props['next_gen_format'] === null )
			);

		$tracking->track_media_restored( $process, true, [], $data );
	}
}
