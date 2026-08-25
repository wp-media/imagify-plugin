<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Bulk\Bulk::maybe_clear_stale_bulk_run() — a bulk run enqueued before
 * 2.3.2 pushes queue items without the 'bulk' flag introduced in that release (see
 * Bulk::check_optimization_status()). Left running across the upgrade, such a run's
 * `remaining` counter would never reach zero. This clears the running transients so a
 * stale, straddling run does not get stuck forever.
 *
 * @covers \Imagify\Bulk\Bulk::maybe_clear_stale_bulk_run
 * @group  BulkMaybeClearStaleBulkRun
 * @since  2.3.2
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class MaybeClearStaleBulkRunTest extends TestCase {

	/**
	 * Test: upgrading from a version older than 2.3.2 clears any running bulk transient.
	 */
	public function testClearsRunningTransientsWhenUpgradingFromOlderVersion(): void {
		$expected = [
			'imagify_custom-folders_optimize_running',
			'imagify_wp_optimize_running',
			'imagify_bulk_optimization_result',
			'imagify_bulk_optimization_complete',
		];

		$deleted = [];

		Functions\when( 'delete_transient' )->alias(
			function ( $name ) use ( &$deleted ) {
				$deleted[] = $name;
			}
		);

		( new Bulk() )->maybe_clear_stale_bulk_run( '2.3.1', '2.3.1' );

		sort( $expected );
		sort( $deleted );

		$this->assertSame( $expected, $deleted );
	}

	/**
	 * Test: upgrading from 2.3.2 or later leaves any running bulk transient untouched.
	 */
	public function testDoesNotClearTransientsWhenAlreadyOnOrPast232(): void {
		Functions\expect( 'delete_transient' )->never();

		( new Bulk() )->maybe_clear_stale_bulk_run( '2.3.2', '2.3.2' );
	}
}
