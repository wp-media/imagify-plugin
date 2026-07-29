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
 * Tests for \Imagify\Tracking\Tracking::resolve_trigger().
 *
 * @covers \Imagify\Tracking\Tracking::resolve_trigger
 * @group  Tracking
 */
class Test_ResolveTrigger extends TestCase {

	/**
	 * The Tracking instance under test (accesses protected method via reflection).
	 *
	 * @var Tracking
	 */
	private $tracking;

	protected function setUp(): void {
		parent::setUp();

		$optin    = Mockery::mock( Optin::class );
		$mixpanel = Mockery::mock( TrackingPlugin::class );

		$mixpanel->shouldReceive( 'identify' )->byDefault();

		Functions\when( 'get_home_url' )->justReturn( 'https://example.com' );
		Functions\when( 'wp_parse_url' )->justReturn( 'example.com' );

		$this->tracking = new Tracking( $optin, $mixpanel );
	}

	/**
	 * Calls the protected resolve_trigger() via reflection.
	 *
	 * @param array $item The optimization item.
	 *
	 * @return string
	 */
	private function call_resolve_trigger( array $item ): string {
		$ref    = new \ReflectionMethod( $this->tracking, 'resolve_trigger' );
		$ref->setAccessible( true );
		return $ref->invoke( $this->tracking, $item );
	}

	/**
	 * Tests that 'auto' is returned when is_new_upload is set.
	 */
	public function testAutoWhenIsNewUpload(): void {
		$item = [ 'data' => [ 'is_new_upload' => true ] ];

		$this->assertSame( 'auto', $this->call_resolve_trigger( $item ) );
	}

	/**
	 * Tests that 'bulk' is returned when imagify_wp_optimize_running transient is set.
	 */
	public function testBulkWhenWpOptimizeRunningTransient(): void {
		Functions\when( 'get_transient' )->alias( function ( $key ) {
			return $key === 'imagify_wp_optimize_running' ? '1' : false;
		} );

		$item = [ 'data' => [] ];

		$this->assertSame( 'bulk', $this->call_resolve_trigger( $item ) );
	}

	/**
	 * Tests that 'bulk' is returned when imagify_custom-folders_optimize_running transient is set.
	 */
	public function testBulkWhenCustomFoldersOptimizeRunningTransient(): void {
		Functions\when( 'get_transient' )->alias( function ( $key ) {
			return $key === 'imagify_custom-folders_optimize_running' ? '1' : false;
		} );

		$item = [ 'data' => [] ];

		$this->assertSame( 'bulk', $this->call_resolve_trigger( $item ) );
	}

	/**
	 * Tests that 'manual' is returned when no trigger signal is present.
	 */
	public function testManualFallback(): void {
		Functions\when( 'get_transient' )->justReturn( false );

		$item = [ 'data' => [] ];

		$this->assertSame( 'manual', $this->call_resolve_trigger( $item ) );
	}

	/**
	 * Tests that 'auto' wins over bulk transients when is_new_upload is set.
	 */
	public function testAutoWinsOverBulkTransient(): void {
		Functions\when( 'get_transient' )->justReturn( '1' );

		$item = [ 'data' => [ 'is_new_upload' => true ] ];

		$this->assertSame( 'auto', $this->call_resolve_trigger( $item ) );
	}
}
