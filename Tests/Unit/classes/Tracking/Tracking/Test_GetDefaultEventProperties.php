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
 * Tests for \Imagify\Tracking\BaseTracking::get_default_event_properties().
 *
 * @covers \Imagify\Tracking\BaseTracking::get_default_event_properties
 * @group  Tracking
 */
class Test_GetDefaultEventProperties extends TestCase {

	/**
	 * Calls the protected get_default_event_properties() via reflection.
	 *
	 * @param Tracking $tracking The tracking instance.
	 *
	 * @return array<string, mixed>
	 */
	private function call_get_default_event_properties( Tracking $tracking ): array {
		$ref = new \ReflectionMethod( $tracking, 'get_default_event_properties' );
		$ref->setAccessible( true );
		return $ref->invoke( $tracking );
	}

	/**
	 * Creates a Tracking instance with mocked dependencies.
	 *
	 * @return Tracking
	 */
	private function create_tracking(): Tracking {
		$optin    = Mockery::mock( Optin::class );
		$mixpanel = Mockery::mock( TrackingPlugin::class );
		return new Tracking( $optin, $mixpanel );
	}

	/**
	 * Tests that license_owner is a hash of the user's email when connected.
	 */
	public function testLicenseOwnerIsHashedEmailWhenConnected(): void {
		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'email' => 'user@example.com' ] );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 5 );

		$tracking = $this->create_tracking();
		$props    = $this->call_get_default_event_properties( $tracking );

		$expected_hash = hash( 'sha256', 'user@example.com' );

		$this->assertSame( $expected_hash, $props['license_owner'] );
		$this->assertSame( 'wp_plugin', $props['context'] );
		$this->assertSame( 5, $props['user_id'] );
	}

	/**
	 * Tests that license_owner is an empty string when get_imagify_user() returns WP_Error.
	 */
	public function testLicenseOwnerIsEmptyStringWhenWpError(): void {
		$wp_error = Mockery::mock( 'WP_Error' );

		Functions\when( 'get_imagify_user' )->justReturn( $wp_error );
		Functions\when( 'is_wp_error' )->justReturn( true );
		Functions\when( 'get_current_user_id' )->justReturn( 0 );

		$tracking = $this->create_tracking();
		$props    = $this->call_get_default_event_properties( $tracking );

		$this->assertSame( '', $props['license_owner'] );
	}

	/**
	 * Tests that user_id comes from get_current_user_id().
	 */
	public function testUserIdFromGetCurrentUserId(): void {
		Functions\when( 'get_imagify_user' )->justReturn( (object) [ 'email' => '' ] );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'get_current_user_id' )->justReturn( 42 );

		$tracking = $this->create_tracking();
		$props    = $this->call_get_default_event_properties( $tracking );

		$this->assertSame( 42, $props['user_id'] );
	}
}
