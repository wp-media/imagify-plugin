<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Bulk\Bulk::check_optimization_status() — regression coverage for the
 * bulk progress-counter conflation fix: a completion whose $item does not carry the
 * 'bulk' origin flag (e.g. a manual click or an auto-optimize-on-upload) must never
 * decrement the active bulk job's counters, even while a bulk transient is active.
 *
 * @covers \Imagify\Bulk\Bulk::check_optimization_status
 * @group  BulkCheckOptimizationStatus
 * @since  2.4
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CheckOptimizationStatusTest extends TestCase {

	/**
	 * Test: a completion without the 'bulk' flag leaves the active bulk counters
	 * (and the bulk progress stats) untouched, and never marks the bulk job complete.
	 */
	public function testNonBulkCompletionDoesNotAffectActiveBulkCounters(): void {
		Functions\when( 'get_transient' )->alias(
			function ( $name ) {
				if ( 'imagify_wp_optimize_running' === $name ) {
					return [
						'total'     => 5,
						'remaining' => 5,
					];
				}

				if ( 'imagify_custom-folders_optimize_running' === $name ) {
					return false;
				}

				// imagify_bulk_optimization_result and any other transient.
				return false;
			}
		);

		Functions\expect( 'set_transient' )->never();
		Functions\expect( 'delete_transient' )->never();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$process->shouldNotReceive( 'get_data' );

		$item = [
			'process_class' => 'Imagify\\Optimization\\Process\\WP',
			'data'          => [
				// No 'bulk' key: this completion was not enqueued by Bulk::run_optimize().
				'hook_suffix' => 'optimize_media',
			],
		];

		( new Bulk() )->check_optimization_status( $process, $item );
	}

	/**
	 * Test: a genuinely bulk-tagged completion is still processed as before (sanity check
	 * that the new origin guard does not also block legitimate bulk completions).
	 */
	public function testBulkTaggedCompletionStillDecrementsCounters(): void {
		Functions\when( 'get_transient' )->alias(
			function ( $name ) {
				if ( 'imagify_wp_optimize_running' === $name ) {
					return [
						'total'     => 5,
						'remaining' => 5,
					];
				}

				if ( 'imagify_custom-folders_optimize_running' === $name ) {
					return false;
				}

				return false;
			}
		);

		Functions\expect( 'set_transient' )
			->once()
			->with(
				'imagify_wp_optimize_running',
				[
					'total'     => 5,
					'remaining' => 4,
				],
				Mockery::any()
			)
			->andReturn( true );

		$data = Mockery::mock();
		$data->shouldReceive( 'is_optimized' )->andReturn( false );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$process->shouldReceive( 'get_data' )->andReturn( $data );

		$item = [
			'process_class' => 'Imagify\\Optimization\\Process\\WP',
			'data'          => [
				'bulk'        => true,
				'hook_suffix' => 'optimize_media',
			],
		];

		( new Bulk() )->check_optimization_status( $process, $item );
	}
}
