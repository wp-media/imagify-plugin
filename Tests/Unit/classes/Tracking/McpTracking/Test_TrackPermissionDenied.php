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
 * Tests for \Imagify\Tracking\McpTracking::track_permission_denied().
 *
 * @covers \Imagify\Tracking\McpTracking::track_permission_denied
 * @group  Tracking
 */
class Test_TrackPermissionDenied extends TestCase {

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

		( new McpTracking( $optin, $mixpanel ) )
			->track_permission_denied( 'imagify/optimize-media', 'Optimize media', 'manage' );
	}

	/**
	 * Tests that the "MCP Ability Permission Denied" event is fired with correct properties.
	 */
	public function testFiresPermissionDeniedEvent(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$wp_user        = Mockery::mock( 'WP_User' );
		$wp_user->roles = [ 'editor' ];

		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 'MCP Ability Permission Denied' === $event
						&& 'wp_plugin_mcp' === $props['context']
						&& 'imagify/optimize-media' === $props['ability_id']
						&& 'manage' === $props['required_capability']
						&& 'editor' === $props['user_role'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 7 );
		Functions\when( 'wp_get_current_user' )->justReturn( $wp_user );

		( new McpTracking( $optin, $mixpanel ) )
			->track_permission_denied( 'imagify/optimize-media', 'Optimize media', 'manage' );
	}

	/**
	 * Tests that user_role is empty string when user has no roles.
	 */
	public function testUserRoleIsEmptyWhenNoRoles(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$wp_user        = Mockery::mock( 'WP_User' );
		$wp_user->roles = [];

		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return '' === $props['user_role'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );
		Functions\when( 'wp_get_current_user' )->justReturn( $wp_user );

		( new McpTracking( $optin, $mixpanel ) )
			->track_permission_denied( 'imagify/optimize-media', 'Optimize media', 'manage' );
	}
}
