<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\Tracking;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin;
use Imagify\Optimization\Data\DataInterface;
use Imagify\Optimization\Process\ProcessInterface;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\Tracking;
use Mockery;

/**
 * Tests for \Imagify\Tracking\Tracking::track_media_optimized().
 *
 * @covers \Imagify\Tracking\Tracking::track_media_optimized
 * @group  Tracking
 */
class Test_TrackMediaOptimized extends TestCase {

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
	 * Creates a minimal process mock that satisfies the happy path.
	 */
	private function create_happy_process(): ProcessInterface {
		$data    = Mockery::mock( DataInterface::class );
		$process = Mockery::mock( ProcessInterface::class );
		$media   = Mockery::mock( \Imagify\Media\MediaInterface::class );

		$data->shouldReceive( 'get_size_data' )->with( 'full' )->andReturn( [
			'success'        => true,
			'original_size'  => 200000,
			'optimized_size' => 150000,
		] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::AVIF_SUFFIX )->andReturn( [] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::WEBP_SUFFIX )->andReturn( [] );
		$data->shouldReceive( 'get_optimization_level' )->andReturn( 1 );

		$media->shouldReceive( 'get_mime_type' )->andReturn( 'image/jpeg' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
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
		$item    = [ 'sizes_done' => [ 'full' ] ];

		$tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Tests that no event is tracked when 'full' is not in sizes_done.
	 */
	public function testNoTrackWhenFullNotInSizesDone(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldNotReceive( 'track_direct' );

		$process = Mockery::mock( ProcessInterface::class );
		$item    = [ 'sizes_done' => [ 'thumbnail', 'medium' ] ];

		$tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Tests that no event is tracked when the full size did not succeed.
	 */
	public function testNoTrackWhenFullSizeNotSuccess(): void {
		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$mixpanel->shouldNotReceive( 'track_direct' );

		$data    = Mockery::mock( DataInterface::class );
		$process = Mockery::mock( ProcessInterface::class );

		$data->shouldReceive( 'get_size_data' )->with( 'full' )->andReturn( [
			'success' => false,
		] );

		$process->shouldReceive( 'get_data' )->andReturn( $data );

		$item = [ 'sizes_done' => [ 'full' ] ];

		$tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Tests that the happy path calls track_direct with expected properties.
	 * Also asserts that auto-merged properties are NOT included.
	 */
	public function testHappyPathCallsTrackDirect(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_happy_process();
		$item    = [
			'sizes_done' => [ 'full' ],
			'data'       => [],
		];

		$forbidden = [ 'domain', 'wp_version', 'php_version', 'plugin', 'brand', 'application' ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Optimized',
				Mockery::on(
					function ( array $props ) use ( $forbidden ) {
						foreach ( $forbidden as $key ) {
							if ( array_key_exists( $key, $props ) ) {
								return false;
							}
						}
						return isset( $props['context'], $props['savings_percent'], $props['trigger'] );
					}
				)
			);

		$tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Tests savings_percent is 0 when original_size is 0 (division-by-zero guard).
	 */
	public function testSavingsPercentZeroWhenOriginalSizeIsZero(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$data    = Mockery::mock( DataInterface::class );
		$process = Mockery::mock( ProcessInterface::class );
		$media   = Mockery::mock( \Imagify\Media\MediaInterface::class );

		$data->shouldReceive( 'get_size_data' )->with( 'full' )->andReturn( [
			'success'        => true,
			'original_size'  => 0,
			'optimized_size' => 0,
		] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::AVIF_SUFFIX )->andReturn( [] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::WEBP_SUFFIX )->andReturn( [] );
		$data->shouldReceive( 'get_optimization_level' )->andReturn( 1 );

		$media->shouldReceive( 'get_mime_type' )->andReturn( 'image/jpeg' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'get_media' )->andReturn( $media );

		$item = [ 'sizes_done' => [ 'full' ], 'data' => [] ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Optimized',
				Mockery::on(
					function ( array $props ) {
						return $props['savings_percent'] === 0;
					}
				)
			);

		$tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Tests next_gen_format is 'avif' when the AVIF size is successful.
	 */
	public function testNextGenFormatIsAvifWhenAvifSucceeds(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$data    = Mockery::mock( DataInterface::class );
		$process = Mockery::mock( ProcessInterface::class );
		$media   = Mockery::mock( \Imagify\Media\MediaInterface::class );

		$data->shouldReceive( 'get_size_data' )->with( 'full' )->andReturn( [
			'success'        => true,
			'original_size'  => 200000,
			'optimized_size' => 150000,
		] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::AVIF_SUFFIX )->andReturn( [ 'success' => true ] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::WEBP_SUFFIX )->andReturn( [ 'success' => true ] );
		$data->shouldReceive( 'get_optimization_level' )->andReturn( 1 );

		$media->shouldReceive( 'get_mime_type' )->andReturn( 'image/jpeg' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'get_media' )->andReturn( $media );

		$item = [ 'sizes_done' => [ 'full' ], 'data' => [] ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Optimized',
				Mockery::on(
					function ( array $props ) {
						return $props['next_gen_format'] === 'avif';
					}
				)
			);

		$tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Tests next_gen_format is 'webp' when only WebP succeeds.
	 */
	public function testNextGenFormatIsWebpWhenWebpSucceeds(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$data    = Mockery::mock( DataInterface::class );
		$process = Mockery::mock( ProcessInterface::class );
		$media   = Mockery::mock( \Imagify\Media\MediaInterface::class );

		$data->shouldReceive( 'get_size_data' )->with( 'full' )->andReturn( [
			'success'        => true,
			'original_size'  => 200000,
			'optimized_size' => 150000,
		] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::AVIF_SUFFIX )->andReturn( [] );
		$data->shouldReceive( 'get_size_data' )->with( 'full' . ProcessInterface::WEBP_SUFFIX )->andReturn( [ 'success' => true ] );
		$data->shouldReceive( 'get_optimization_level' )->andReturn( 1 );

		$media->shouldReceive( 'get_mime_type' )->andReturn( 'image/jpeg' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'get_media' )->andReturn( $media );

		$item = [ 'sizes_done' => [ 'full' ], 'data' => [] ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Optimized',
				Mockery::on(
					function ( array $props ) {
						return $props['next_gen_format'] === 'webp';
					}
				)
			);

		$tracking->track_media_optimized( $process, $item );
	}

	/**
	 * Tests next_gen_format is null when no next-gen format is generated.
	 */
	public function testNextGenFormatIsNullWhenNoneGenerated(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$mocks    = $this->create_tracking();
		$tracking = $mocks['tracking'];
		$mixpanel = $mocks['mixpanel'];

		$process = $this->create_happy_process();
		$item    = [ 'sizes_done' => [ 'full' ], 'data' => [] ];

		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->with(
				'Media Optimized',
				Mockery::on(
					function ( array $props ) {
						return $props['next_gen_format'] === null;
					}
				)
			);

		$tracking->track_media_optimized( $process, $item );
	}
}
