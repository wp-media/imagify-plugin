<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;
use Imagify\Tests\Unit\classes\Bulk\Stubs\BulkNoBackupOnlyStub;
use Imagify\Tests\Unit\classes\Bulk\Stubs\BulkWithIdsStub;
use Mockery;

/**
 * Tests for \Imagify\Bulk\Bulk::run_generate_nextgen().
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via
 * a Mockery alias mock, so this test runs in its own process to guarantee the
 * alias is registered before the real class would otherwise be autoloaded.
 *
 * @covers \Imagify\Bulk\Bulk::run_generate_nextgen
 * @group  GenerateNextgen
 * @since  2.3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RunGenerateNextgenTest extends TestCase {

	/**
	 * Stubs `Imagify_Requirements::is_api_key_valid()` / `is_over_quota()` via a Mockery
	 * alias mock so `can_optimize()` lets execution proceed past the initial guard.
	 */
	private function stubRequirements(): void {
		$mock = Mockery::mock( 'alias:Imagify_Requirements' );
		$mock->shouldReceive( 'is_api_key_valid' )->andReturn( true );
		$mock->shouldReceive( 'is_over_quota' )->andReturn( false );
	}

	/**
	 * Regression guard: a context that only has `no_backup` errors (empty ids) must not
	 * abort the whole run. Another context with valid ids must still be scheduled.
	 */
	public function testContextWithOnlyNoBackupErrorsDoesNotAbortOtherContexts(): void {
		$this->stubRequirements();

		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'as_enqueue_async_action' )->justReturn( 1 );

		Filters\expectApplied( 'imagify_bulk_class_name' )
			->twice()
			->andReturnUsing(
				function ( $class_name, $context ) {
					return 'wp' === $context ? BulkNoBackupOnlyStub::class : BulkWithIdsStub::class;
				}
			);

		$result = ( new Bulk() )->run_generate_nextgen( [ 'wp', 'custom-folders' ], [ 'webp' ] );

		$this->assertSame(
			[
				'success' => true,
				'message' => 2,
			],
			$result
		);
	}
}
