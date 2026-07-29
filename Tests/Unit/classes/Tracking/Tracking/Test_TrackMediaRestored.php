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
	 * @dataProvider configTestData
	 */
	public function testShouldBehaveAsExpected( array $config, array $expected ): void {
		$optin    = Mockery::mock( Optin::class );
		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );

		$optin->shouldReceive( 'can_track' )->andReturn( $config['can_track'] );

		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'email' => 'user@example.com' ] );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'is_wp_error' )->alias( function ( $thing ) {
			return $thing instanceof WP_Error;
		} );

		$tracking = new Tracking( $optin, $mixpanel );

		$response = $config['response'] === 'wp_error'
			? new WP_Error( 'restore_failed', 'Failed.' )
			: $config['response'];

		$media   = Mockery::mock( MediaInterface::class );
		$process = Mockery::mock( ProcessInterface::class );
		$media->shouldReceive( 'get_context' )->andReturn( $config['context'] );
		$process->shouldReceive( 'get_media' )->andReturn( $media );

		$forbidden = [ 'domain', 'wp_version', 'php_version', 'plugin', 'brand', 'application' ];

		if ( ! $expected['track_direct_called'] ) {
			$mixpanel->shouldNotReceive( 'track_direct' );
		} else {
			$mixpanel->shouldReceive( 'track_direct' )
				->once()
				->with(
					'Media Restored',
					Mockery::on(
						function ( array $props ) use ( $expected, $forbidden ) {
							foreach ( $forbidden as $key ) {
								if ( array_key_exists( $key, $props ) ) {
									return false;
								}
							}

							if ( isset( $expected['media_context'] ) && $props['media_context'] !== $expected['media_context'] ) {
								return false;
							}

							if ( array_key_exists( 'optimization_level', $expected ) && $props['optimization_level'] !== $expected['optimization_level'] ) {
								return false;
							}

							if ( array_key_exists( 'next_gen_format', $expected ) && $props['next_gen_format'] !== $expected['next_gen_format'] ) {
								return false;
							}

							return true;
						}
					)
				);
		}

		$tracking->track_media_restored( $process, $response, [], $config['data'] );
	}
}
