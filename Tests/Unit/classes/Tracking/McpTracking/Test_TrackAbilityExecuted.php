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
 * Tests for \Imagify\Tracking\McpTracking::track_ability_executed().
 *
 * @covers \Imagify\Tracking\McpTracking::track_ability_executed
 * @group  Tracking
 */
class Test_TrackAbilityExecuted extends TestCase {

	/**
	 * Tests that nothing is tracked when opt-in is disabled.
	 */
	public function testDoesNothingWhenOptinDisabled(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( false );

		$mixpanel = Mockery::mock( TrackingPlugin::class );
		$mixpanel->shouldNotReceive( 'track_direct' );

		( new McpTracking( $optin, $mixpanel ) )
			->track_ability_executed( 'imagify/get-stats', 'Get Imagify optimization stats', microtime( true ) );
	}

	/**
	 * Tests that "MCP Ability Executed" is fired with the correct properties.
	 */
	public function testFiresAbilityExecutedEvent(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 'MCP Ability Executed' === $event
						&& 'wp_plugin_mcp' === $props['context']
						&& 'imagify/get-stats' === $props['ability_id']
						&& 'Get Imagify optimization stats' === $props['ability_name']
						&& isset( $props['execution_time_ms'] );
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		( new McpTracking( $optin, $mixpanel ) )
			->track_ability_executed( 'imagify/get-stats', 'Get Imagify optimization stats', microtime( true ) );
	}

	/**
	 * Tests that "MCP Ability Executed" fires for any ability ID (not just optimize-media).
	 */
	public function testFiresForAnyAbilityId(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 'MCP Ability Executed' === $event
						&& 'imagify/get-account' === $props['ability_id'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		( new McpTracking( $optin, $mixpanel ) )
			->track_ability_executed( 'imagify/get-account', 'Get Imagify account status', microtime( true ) );
	}

	/**
	 * Tests that is_preview defaults to false and is included in the Mixpanel event_data.
	 */
	public function testIsPreviewDefaultsToFalse(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 'MCP Ability Executed' === $event
						&& array_key_exists( 'is_preview', $props )
						&& false === $props['is_preview'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		( new McpTracking( $optin, $mixpanel ) )
			->track_ability_executed( 'imagify/get-stats', 'Get Imagify optimization stats', microtime( true ) );
	}

	/**
	 * Tests that is_preview is forwarded as true in the Mixpanel event_data when passed explicitly.
	 */
	public function testIsPreviewTrueIsForwardedToEventData(): void {
		$optin = Mockery::mock( Optin::class );
		$optin->shouldReceive( 'can_track' )->andReturn( true );

		$mixpanel = Mockery::mock( TrackingPlugin::class );
		$mixpanel->shouldReceive( 'track_direct' )
			->once()
			->withArgs(
				static function ( string $event, array $props ): bool {
					return 'MCP Ability Executed' === $event
						&& true === $props['is_preview'];
				}
			);

		Functions\when( 'get_imagify_user' )->justReturn( new \WP_Error() );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 1 );

		( new McpTracking( $optin, $mixpanel ) )
			->track_ability_executed( 'imagify/optimize-media', 'Optimize media', microtime( true ), true );
	}
}
