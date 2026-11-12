<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\AutoOptimization;

use Brain\Monkey\Functions;
use Imagify_Auto_Optimization;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify_Auto_Optimization::do_auto_optimization() — the priority flag added for
 * new uploads (issue #704). New uploads must jump ahead of any bulk optimization queue;
 * re-optimizations of already-optimized media (existing branch) must not.
 *
 * @covers \Imagify_Auto_Optimization::do_auto_optimization
 * @group  AutoOptimization
 * @since  2.3.2
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_DoAutoOptimization extends TestCase {

	/**
	 * Stubs the WordPress action hooks fired by do_auto_optimization(), regardless of branch.
	 */
	private function stubActionHooks(): void {
		Functions\when( 'do_action_deprecated' )->justReturn( null );
		Functions\when( 'do_action' )->justReturn( null );
	}

	/**
	 * Test: a new upload (is_new_upload = true) is optimized with 'priority' => true in $args,
	 * alongside the existing 'is_new_upload' => 1 flag.
	 */
	public function testNewUploadIsOptimizedAsPriority(): void {
		$this->stubActionHooks();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$process->shouldReceive( 'optimize' )
			->once()
			->with(
				null,
				[
					'is_new_upload' => 1,
					'priority'      => true,
				]
			)
			->andReturn( true );
		$process->shouldNotReceive( 'get_data' );
		$process->shouldNotReceive( 'restore' );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		( new Imagify_Auto_Optimization() )->do_auto_optimization( 42, true );
	}

	/**
	 * Test: a re-optimization of an already-processed media (is_new_upload = false) is
	 * optimized without any 'priority' key — only brand-new uploads are prioritized.
	 */
	public function testReoptimizationIsNotFlaggedAsPriority(): void {
		$this->stubActionHooks();

		$data = Mockery::mock();
		$data->shouldReceive( 'get_optimization_level' )->andReturn( 1 );
		$data->shouldReceive( 'delete_optimization_data' )->once();

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'optimize' )
			->once()
			->with( 1 )
			->andReturn( true );
		$process->shouldNotReceive( 'restore' );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		( new Imagify_Auto_Optimization() )->do_auto_optimization( 42, false );
	}
}
