<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Bulk\Bulk::optimize_media() -> force_optimize().
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via a
 * Mockery alias mock in every test, so every test in this class runs in its own
 * process (`@runTestsInSeparateProcesses`) to guarantee the alias is registered
 * before the real class would otherwise be autoloaded.
 *
 * @covers \Imagify\Bulk\Bulk::optimize_media
 * @group  BulkForceOptimize
 * @since  2.4
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class ForceOptimizeTest extends TestCase {

	/**
	 * Stubs `Imagify_Requirements::is_api_key_valid()` / `is_over_quota()` via a Mockery
	 * alias mock so can_optimize() lets execution reach force_optimize().
	 *
	 * @param bool $api_key_valid Value returned by is_api_key_valid().
	 * @param bool $over_quota    Value returned by is_over_quota().
	 */
	private function stubRequirements( bool $api_key_valid, bool $over_quota ): void {
		$mock = Mockery::mock( 'alias:Imagify_Requirements' );
		$mock->shouldReceive( 'is_api_key_valid' )->andReturn( $api_key_valid );
		$mock->shouldReceive( 'is_over_quota' )->andReturn( $over_quota );
	}

	/**
	 * Test: a bulk-triggered optimization tags its optimize() call with ['bulk' => true],
	 * so check_optimization_status() can later distinguish it from manual/auto-optimize
	 * completions.
	 */
	public function testOptimizeMediaTagsTheOptimizeCallAsBulk(): void {
		$this->stubRequirements( true, false );

		$data = Mockery::mock();
		$data->shouldReceive( 'is_optimized' )->andReturn( false );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'optimize' )
			->once()
			->with( 1, [ 'bulk' => true ] )
			->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		( new Bulk() )->optimize_media( 42, 'wp', 1 );
	}

	/**
	 * Test: force_optimize() still restores an already-optimized media before
	 * re-optimizing, and still tags the resulting optimize() call as bulk.
	 */
	public function testOptimizeMediaRestoresBeforeReoptimizingAndStillTagsBulk(): void {
		$this->stubRequirements( true, false );

		$data = Mockery::mock();
		$data->shouldReceive( 'is_optimized' )->andReturn( true );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'restore' )->once()->andReturn( true );
		$process->shouldReceive( 'optimize' )
			->once()
			->with( 2, [ 'bulk' => true ] )
			->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		( new Bulk() )->optimize_media( 42, 'wp', 2 );
	}
}
