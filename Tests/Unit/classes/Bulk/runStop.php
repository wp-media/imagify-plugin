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
	 * Action Scheduler groups passed to as_unschedule_all_actions().
	 *
	 * @var array
	 */
	private $unscheduled = [];

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

		// ActionScheduler is not loaded in unit tests: record what would have been flushed.
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
	 * @param array $config   The contexts to stop and the running transients.
	 * @param array $expected The expected run_stop() result.
	 */
	public function testShouldReturnExpectedResult( $config, $expected ): void {
		$this->transients = $config['transients'];

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
}
