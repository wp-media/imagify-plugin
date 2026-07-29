<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Bulk\Bulk::run_stop().
 *
 * @covers \Imagify\Bulk\Bulk::run_stop
 * @group  BulkRunStop
 * @since  2.3
 */
class RunStopTest extends TestCase {

	/**
	 * Test: nothing running returns a not-running result, orphan actions are still flushed.
	 */
	public function testReturnsNotRunningWhenNoProcessIsRunning(): void {
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'delete_transient' )->atLeast()->once();
		Functions\expect( 'as_unschedule_all_actions' )->twice()->andReturnNull();

		$result = ( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame(
			[
				'success'   => false,
				'message'   => 'not-running',
				'cancelled' => 0,
			],
			$result
		);
	}

	/**
	 * Test: a running process is stopped and the remaining media are reported as cancelled.
	 */
	public function testCancelsRemainingMediaForRunningContext(): void {
		Functions\when( 'get_transient' )->justReturn(
			[
				'total'     => 10,
				'remaining' => 4,
			]
		);
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\expect( 'as_unschedule_all_actions' )
			->twice()
			->andReturnNull();

		$result = ( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame(
			[
				'success'   => true,
				'message'   => 'success',
				'cancelled' => 4,
			],
			$result
		);
	}

	/**
	 * Test: the remaining media of every given context are summed up.
	 */
	public function testSumsRemainingMediaAcrossContexts(): void {
		Functions\when( 'get_transient' )->justReturn(
			[
				'total'     => 10,
				'remaining' => 3,
			]
		);
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'as_unschedule_all_actions' )->justReturn( null );

		$result = ( new Bulk() )->run_stop( [ 'wp', 'custom-folders' ] );

		$this->assertSame( 6, $result['cancelled'] );
		$this->assertTrue( $result['success'] );
	}

	/**
	 * Test: a negative remaining counter is never reported as a negative cancelled count.
	 */
	public function testNeverReportsNegativeCancelledCount(): void {
		Functions\when( 'get_transient' )->justReturn(
			[
				'total'     => 10,
				'remaining' => -2,
			]
		);
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'as_unschedule_all_actions' )->justReturn( null );

		$result = ( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame(
			[
				'success'   => false,
				'message'   => 'not-running',
				'cancelled' => 0,
			],
			$result
		);
	}

	/**
	 * Test: the progress transients are deleted so a new run starts from a clean state.
	 */
	public function testDeletesProgressTransients(): void {
		Functions\when( 'get_transient' )->justReturn(
			[
				'total'     => 5,
				'remaining' => 5,
			]
		);
		Functions\when( 'as_unschedule_all_actions' )->justReturn( null );

		Functions\expect( 'delete_transient' )->once()->with( 'imagify_wp_optimize_running' );
		Functions\expect( 'delete_transient' )->once()->with( 'imagify_bulk_optimization_result' );
		Functions\expect( 'delete_transient' )->once()->with( 'imagify_missing_next_gen_total' );

		( new Bulk() )->run_stop( [ 'wp' ] );
	}

	/**
	 * Test: the imagify_bulk_stopped action is fired when a process was actually stopped.
	 */
	public function testFiresStoppedAction(): void {
		Functions\when( 'get_transient' )->justReturn(
			[
				'total'     => 5,
				'remaining' => 2,
			]
		);
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'as_unschedule_all_actions' )->justReturn( null );

		( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame( 1, Actions\did( 'imagify_bulk_stopped' ) );
	}
}
