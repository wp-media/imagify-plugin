<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Tracking\McpTrackingSubscriber;

use Imagify\Tests\Unit\TestCase;
use Imagify\Tracking\McpTracking;
use Imagify\Tracking\McpTrackingSubscriber;
use Mockery;

/**
 * Tests for \Imagify\Tracking\McpTrackingSubscriber::on_ability_executed().
 *
 * @covers \Imagify\Tracking\McpTrackingSubscriber::on_ability_executed
 * @group  Tracking
 */
class Test_OnAbilityExecuted extends TestCase {

	/**
	 * Tests that on_ability_executed() delegates to McpTracking::track_media_optimized().
	 */
	public function testDelegatesToTrackMediaOptimized(): void {
		$ability_id   = 'imagify/optimize-media';
		$ability_name = 'Optimize media';
		$result       = [ 'status' => 'success', 'original_size' => 1000, 'optimized_size' => 800, 'savings_percent' => 20.0, 'error_message' => null ];
		$start_time   = microtime( true );
		$input_params = [ 'media_id' => 42, 'optimization_level' => 1 ];

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_media_optimized' )
			->once()
			->with( $ability_id, $result, $start_time, $input_params );

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_ability_executed( $ability_id, $ability_name, $result, $start_time, $input_params );
	}
}
