<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\McpTrackingSubscriber;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\McpTracking;
use Imagify\Tracking\McpTrackingSubscriber;
use Mockery;

/**
 * Tests for \Imagify\Tracking\McpTrackingSubscriber::on_permission_denied().
 *
 * @covers \Imagify\Tracking\McpTrackingSubscriber::on_permission_denied
 * @group  Tracking
 */
class Test_OnPermissionDenied extends TestCase {

	/**
	 * Tests that on_permission_denied() delegates to McpTracking::track_permission_denied().
	 */
	public function testDelegatesToTrackPermissionDenied(): void {
		$ability_id          = 'imagify/optimize-media';
		$ability_name        = 'Optimize media';
		$required_capability = 'manage';

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_permission_denied' )
			->once()
			->with( $ability_id, $ability_name, $required_capability );

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_permission_denied( $ability_id, $ability_name, $required_capability );
	}
}
