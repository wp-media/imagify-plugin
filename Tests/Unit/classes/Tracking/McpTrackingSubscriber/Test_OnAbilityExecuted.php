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
	 * Tests that on_ability_executed() calls both tracking methods for optimize-media.
	 */
	public function testDelegatesToBothTrackersForOptimizeMedia(): void {
		$ability_id   = 'imagify/optimize-media';
		$ability_name = 'Optimize media';
		$result       = [ 'status' => 'success', 'original_size' => 1000, 'optimized_size' => 800, 'savings_percent' => 20.0, 'error_message' => null ];
		$start_time   = microtime( true );
		$input_params = [ 'media_id' => 42, 'optimization_level' => 1 ];

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_ability_executed' )
			->once()
			->with( $ability_id, $ability_name, $start_time );
		$mcp_tracking->shouldReceive( 'track_media_optimized' )
			->once()
			->with( $ability_id, $result, $start_time, $input_params );

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_ability_executed( $ability_id, $ability_name, $result, $start_time, $input_params );
	}

	/**
	 * Tests that on_ability_executed() calls only the generic tracker for non-optimize-media abilities.
	 */
	public function testDelegatesToGenericTrackerForOtherAbilities(): void {
		$ability_id   = 'imagify/get-stats';
		$ability_name = 'Get Imagify optimization stats';
		$result       = [ 'wp' => [], 'custom-folders' => [] ];
		$start_time   = microtime( true );

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_ability_executed' )
			->once()
			->with( $ability_id, $ability_name, $start_time );
		$mcp_tracking->shouldReceive( 'track_media_optimized' )
			->once()
			->with( $ability_id, $result, $start_time, [] );

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_ability_executed( $ability_id, $ability_name, $result, $start_time, [] );
	}

	/**
	 * Tests that on_ability_executed() skips track_media_optimized when result is not an array (WP_Error).
	 */
	public function testSkipsMediaOptimizedWhenResultIsNotArray(): void {
		$ability_id   = 'imagify/update-settings';
		$ability_name = 'Update Imagify settings';
		$result       = new \WP_Error( 'invalid', 'Error' );
		$start_time   = microtime( true );

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_ability_executed' )
			->once()
			->with( $ability_id, $ability_name, $start_time );
		$mcp_tracking->shouldNotReceive( 'track_media_optimized' );

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_ability_executed( $ability_id, $ability_name, $result, $start_time, [] );
	}
}
