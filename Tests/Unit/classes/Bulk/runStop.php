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
 *
 * @group  BulkRunStop
 * @since  2.3
 */
class Test_RunStop extends TestCase {

	/**
	 * Transient names read by run_stop(), keyed by transient name.
	 *
	 * @var array
	 */
	private $transients = [];

	/**
	 * Transient names passed to delete_transient().
	 *
	 * @var array
	 */
	private $deleted = [];

	/**
	 * Hook|group pairs passed to as_unschedule_all_actions().
	 *
	 * @var array
	 */
	private $unscheduled = [];

	/**
	 * Pending action IDs in the queue, keyed by "hook|group".
	 *
	 * @var array
	 */
	private $pending = [];

	/**
	 * Sets up the test fixture.
	 *
	 * @inheritDoc
	 */
	public function setUp(): void {
		parent::setUp();

		$this->transients  = [];
		$this->deleted     = [];
		$this->unscheduled = [];
		$this->pending     = [];

		Functions\when( 'get_transient' )->alias(
			function ( string $transient ) {
				return isset( $this->transients[ $transient ] ) ? $this->transients[ $transient ] : false;
			}
		);

		Functions\when( 'delete_transient' )->alias(
			function ( string $transient ) {
				$this->deleted[] = $transient;

				return true;
			}
		);

		// ActionScheduler is not loaded in unit tests: serve a fake queue and record the flushes.
		Functions\when( 'as_get_scheduled_actions' )->alias(
			function ( array $args ) {
				$key = $args['hook'] . '|' . $args['group'];

				return isset( $this->pending[ $key ] ) ? $this->pending[ $key ] : [];
			}
		);

		Functions\when( 'as_unschedule_all_actions' )->alias(
			function ( string $hook, array $args = [], string $group = '' ) {
				$this->unscheduled[] = $hook . '|' . $group;
			}
		);
	}

	/**
	 * Test: run_stop() returns the expected result for the given running state.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The contexts to stop, the running transients and the queued actions.
	 * @param array $expected The expected run_stop() result.
	 */
	public function testShouldReturnExpectedResult( $config, $expected ): void {
		$this->transients = $config['transients'];
		$this->pending    = $config['pending'];

		$this->assertSame( $expected, ( new Bulk() )->run_stop( $config['contexts'] ) );
	}

	/**
	 * Test: the pending actions of both bulk hooks are flushed for every given context.
	 */
	public function testShouldUnscheduleBothHooksForEveryContext(): void {
		( new Bulk() )->run_stop( [ 'wp', 'custom-folders' ] );

		$this->assertSame(
			[
				'imagify_optimize_media|imagify-wp-optimize-media',
				'imagify_convert_next_gen|imagify-wp-convert-nextgen',
				'imagify_optimize_media|imagify-custom-folders-optimize-media',
				'imagify_convert_next_gen|imagify-custom-folders-convert-nextgen',
			],
			$this->unscheduled
		);
	}

	/**
	 * Test: the progress transients are deleted so a new run starts from a clean state.
	 */
	public function testShouldDeleteProgressTransients(): void {
		( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame(
			[
				'imagify_wp_optimize_running',
				'imagify_bulk_optimization_result',
				'imagify_missing_next_gen_total',
			],
			$this->deleted
		);
	}

	/**
	 * Test: the imagify_bulk_stopped action fires when a process was actually stopped.
	 */
	public function testShouldFireStoppedActionWhenRunning(): void {
		$this->transients = [
			'imagify_wp_optimize_running' => [
				'total'     => 5,
				'remaining' => 2,
			],
		];

		( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame( 1, Actions\did( 'imagify_bulk_stopped' ) );
	}

	/**
	 * Test: the imagify_bulk_stopped action does not fire when nothing was running.
	 */
	public function testShouldNotFireStoppedActionWhenIdle(): void {
		( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame( 0, Actions\did( 'imagify_bulk_stopped' ) );
	}

	/**
	 * Test: only pending actions are counted, so a drifted `remaining` counter cannot inflate
	 * the number reported to the user.
	 */
	public function testShouldNotTrustTheRemainingCounter(): void {
		$this->transients = [
			'imagify_wp_optimize_running' => [
				'total'     => 20,
				'remaining' => 18,
			],
		];
		$this->pending    = [
			'imagify_optimize_media|imagify-wp-optimize-media' => [ 101, 102 ],
		];

		$result = ( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertSame( 2, $result['cancelled'] );
	}

	/**
	 * Test: the pending actions are queried for the exact hook, group and status.
	 */
	public function testShouldQueryOnlyPendingActionsOfTheBulkGroups(): void {
		$queried = [];

		Functions\when( 'as_get_scheduled_actions' )->alias(
			function ( array $args ) use ( &$queried ) {
				$queried[] = $args;

				return [];
			}
		);

		( new Bulk() )->run_stop( [ 'wp' ] );

		$this->assertCount( 2, $queried );

		foreach ( $queried as $args ) {
			$this->assertSame( 'pending', $args['status'] );
			$this->assertSame( -1, $args['per_page'] );
		}

		$this->assertSame( 'imagify_optimize_media', $queried[0]['hook'] );
		$this->assertSame( 'imagify-wp-optimize-media', $queried[0]['group'] );
		$this->assertSame( 'imagify_convert_next_gen', $queried[1]['hook'] );
		$this->assertSame( 'imagify-wp-convert-nextgen', $queried[1]['group'] );
	}
}
