<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\McpTracking;

use Brain\Monkey\Functions;
use Imagify\Dependencies\WPMedia\Mixpanel\Optin;
use Imagify\Dependencies\WPMedia\Mixpanel\TrackingPlugin;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\McpTracking;
use Mockery;

/**
 * Tests for \Imagify\Tracking\McpTracking::track_media_optimized().
 *
 * @covers \Imagify\Tracking\McpTracking::track_media_optimized
 * @group  Tracking
 */
class Test_TrackMediaOptimized extends TestCase {

	/**
	 * Tests that nothing is tracked when opt-in is disabled.
	 */
	public function testDoesNothingWhenOptinDisabled(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( false );
		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldNotReceive( 'track_direct' );

		$tracking = new McpTracking( $optin, $mixpanel );
		$tracking->track_media_optimized( 'imagify/optimize-media', [ 'status' => 'success' ], microtime( true ), [ 'media_id' => 1 ] );
	}

	/**
	 * Tests that nothing is tracked for unknown ability IDs.
	 */
	public function testDoesNothingForUnknownAbilityId(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );
		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldNotReceive( 'track_direct' );

		$tracking = new McpTracking( $optin, $mixpanel );
		$tracking->track_media_optimized( 'imagify/other-ability', [ 'status' => 'success' ], microtime( true ), [] );
	}

	/**
	 * Tests that nothing is tracked on error results.
	 */
	public function testDoesNothingOnErrorResult(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );
		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldNotReceive( 'track_direct' );

		$tracking = new McpTracking( $optin, $mixpanel );
		$tracking->track_media_optimized(
			'imagify/optimize-media',
			[
				'status'        => 'error',
				'error_message' => 'Something failed.',
			],
			microtime( true ),
			[ 'media_id' => 1 ]
		);
	}

	/**
	 * Tests that the "Media Optimized" event is fired with correct properties on success.
	 */
	public function testFiresMediaOptimizedEventOnSuccess(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 'Media Optimized' === $event
						&& 'wp_plugin_mcp' === $props['context']
						&& 'mcp' === $props['initiated_via']
						&& 1 === $props['optimization_level']
						&& 1000 === $props['original_size']
						&& 800 === $props['optimized_size']
						&& 20.0 === $props['savings_percent']
						&& isset( $props['execution_time_ms'] );
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_post_mime_type' )->justReturn( 'image/jpeg' );
		Functions\when( 'get_imagify_option' )->justReturn( false );

		$tracking = new McpTracking( $optin, $mixpanel );
		$tracking->track_media_optimized(
			'imagify/optimize-media',
			[
				'status'          => 'success',
				'original_size'   => 1000,
				'optimized_size'  => 800,
				'savings_percent' => 20.0,
				'error_message'   => null,
			],
			microtime( true ),
			[
				'media_id'           => 42,
				'optimization_level' => 1,
			]
		);
	}

	/**
	 * Tests that optimization_level falls back to the global option when not in input_params.
	 */
	public function testUsesGlobalOptimizationLevelWhenNotInInputParams(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 2 === $props['optimization_level'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_post_mime_type' )->justReturn( 'image/png' );
		Functions\when( 'get_imagify_option' )->justReturn( 2 );

		$tracking = new McpTracking( $optin, $mixpanel );
		$tracking->track_media_optimized(
			'imagify/optimize-media',
			[
				'status'          => 'success',
				'original_size'   => 500,
				'optimized_size'  => 400,
				'savings_percent' => 20.0,
				'error_message'   => null,
			],
			microtime( true ),
			[ 'media_id' => 5 ] // no optimization_level.
		);
	}

	/**
	 * Tests that next_gen_format is 'webp' when only WebP conversion is enabled.
	 */
	public function testResolvesWebpFormatWhenOnlyWebpEnabled(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 'webp' === $props['next_gen_format'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_post_mime_type' )->justReturn( 'image/jpeg' );
		Functions\when( 'get_imagify_option' )->alias(
			static function ( string $key ) {
				return 'convert_to_webp' === $key ? true : false;
			}
		);

		$tracking = new McpTracking( $optin, $mixpanel );
		$tracking->track_media_optimized(
			'imagify/optimize-media',
			[
				'status'          => 'success',
				'original_size'   => 800,
				'optimized_size'  => 600,
				'savings_percent' => 25.0,
				'error_message'   => null,
			],
			microtime( true ),
			[
				'media_id'           => 10,
				'optimization_level' => 1,
			]
		);
	}

	/**
	 * Tests that null size values are preserved as null in event properties.
	 */
	public function testPreservesNullSizeValues(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return null === $props['original_size']
						&& null === $props['optimized_size']
						&& null === $props['savings_percent'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_post_mime_type' )->justReturn( 'image/jpeg' );
		Functions\when( 'get_imagify_option' )->justReturn( false );

		$tracking = new McpTracking( $optin, $mixpanel );
		$tracking->track_media_optimized(
			'imagify/optimize-media',
			[
				'status'          => 'success',
				'original_size'   => null,
				'optimized_size'  => null,
				'savings_percent' => null,
				'error_message'   => null,
			],
			microtime( true ),
			[
				'media_id'           => 0,
				'optimization_level' => 1,
			]
		);
	}
}
