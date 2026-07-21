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
		$result       = [
			'status'          => 'success',
			'original_size'   => 1000,
			'optimized_size'  => 800,
			'savings_percent' => 20.0,
			'error_message'   => null,
		];
		$start_time   = microtime( true );
		$input_params = [
			'media_id'           => 42,
			'optimization_level' => 1,
		];

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_ability_executed' )
			->once()
			->with( $ability_id, $ability_name, $start_time, false );
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
		$result       = [
			'wp'             => [],
			'custom-folders' => [],
		];
		$start_time   = microtime( true );

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_ability_executed' )
			->once()
			->with( $ability_id, $ability_name, $start_time, false );
		$mcp_tracking->shouldNotReceive( 'track_media_optimized' );

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
			->with( $ability_id, $ability_name, $start_time, false );
		$mcp_tracking->shouldNotReceive( 'track_media_optimized' );

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_ability_executed( $ability_id, $ability_name, $result, $start_time, [] );
	}

	/**
	 * Tests that on_ability_executed() derives is_preview = true for each of the three guard-produced
	 * preview statuses, and forwards it as the 4th argument to track_ability_executed().
	 *
	 * @dataProvider providePreviewStatuses
	 *
	 * @param string $status The guard-produced status expected to be treated as a preview.
	 */
	public function testDerivesIsPreviewTrueForGuardProducedStatuses( string $status ): void {
		$ability_id   = 'imagify/optimize-media';
		$ability_name = 'Optimize media';
		$result       = [ 'status' => $status ];
		$start_time   = microtime( true );

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_ability_executed' )
			->once()
			->with( $ability_id, $ability_name, $start_time, true );
		$mcp_tracking->shouldReceive( 'track_media_optimized' )->zeroOrMoreTimes();

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_ability_executed( $ability_id, $ability_name, $result, $start_time, [] );
	}

	/**
	 * Tests that on_ability_executed() derives is_preview = false for success/error statuses.
	 *
	 * @dataProvider provideRealExecutionStatuses
	 *
	 * @param string $status The real-execution status expected to be treated as not a preview.
	 */
	public function testDerivesIsPreviewFalseForRealExecutionStatuses( string $status ): void {
		$ability_id   = 'imagify/get-stats';
		$ability_name = 'Get Imagify optimization stats';
		$result       = [ 'status' => $status ];
		$start_time   = microtime( true );

		$mcp_tracking = Mockery::mock( McpTracking::class );
		$mcp_tracking->shouldReceive( 'track_ability_executed' )
			->once()
			->with( $ability_id, $ability_name, $start_time, false );

		$subscriber = new McpTrackingSubscriber( $mcp_tracking );
		$subscriber->on_ability_executed( $ability_id, $ability_name, $result, $start_time, [] );
	}

	/**
	 * Data provider of guard-produced preview statuses.
	 *
	 * @return array<string, array{string}>
	 */
	public function providePreviewStatuses(): array {
		return [
			'confirmation_required' => [ 'confirmation_required' ],
			'insufficient_quota'    => [ 'insufficient_quota' ],
			'invalid_api_key'       => [ 'invalid_api_key' ],
		];
	}

	/**
	 * Data provider of real-execution (non-preview) statuses.
	 *
	 * @return array<string, array{string}>
	 */
	public function provideRealExecutionStatuses(): array {
		return [
			'success' => [ 'success' ],
			'error'   => [ 'error' ],
		];
	}
}
