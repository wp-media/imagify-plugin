<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\inc\classes\AutoOptimization;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify_Auto_Optimization;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify_Auto_Optimization::store_ids_to_optimize() — on a client side upload the
 * sub sizes are all stored in one write, and the sizes to optimize are read from the stored
 * metadata. Launching from inside the filter would read the value that is about to be replaced
 * and see no sub sizes at all, so that pass waits for the write instead.
 *
 * @covers \Imagify_Auto_Optimization::store_ids_to_optimize
 * @group  AutoOptimization
 * @since  2.3.3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class StoreIdsToOptimizeTest extends TestCase {

	/**
	 * Stubs what both methods need beyond the transient calls.
	 */
	private function stubDependencies(): void {
		Functions\when( 'imagify_is_attachment_mime_type_supported' )->justReturn( true );
		Functions\when( 'get_imagify_option' )->justReturn( 1 );
		Functions\when( 'delete_transient' )->justReturn( true );
	}

	/**
	 * Test: an ordinary upload launches straight away, as it always did.
	 */
	public function testLaunchesImmediatelyOnAnOrdinaryUpload(): void {
		$this->stubDependencies();
		Functions\when( 'get_transient' )->justReturn( false );

		$auto_optimization = new Imagify_Auto_Optimization();
		$metadata          = [ 'file' => 'image.jpg' ];

		$auto_optimization->set_step( 42, 'upload' );
		$auto_optimization->maybe_store_generate_step( $metadata, 42, 'create' );

		Actions\expectDone( 'imagify_after_auto_optimization_init' )->once()->with( 42, true );

		$auto_optimization->store_ids_to_optimize( $metadata, 42 );
	}

	/**
	 * Test: the pass that brings the client side sub sizes in does not launch from the filter,
	 * because the metadata it would read has not been written yet.
	 */
	public function testDefersWhenTheClientSideSubsizesJustArrived(): void {
		$this->stubDependencies();
		Functions\when( 'get_transient' )->justReturn( 1 );

		$auto_optimization = new Imagify_Auto_Optimization();
		$metadata          = [ 'file' => 'image.jpg' ];

		// The finalize request: this restores the upload step and marks the pass as deferred.
		$auto_optimization->maybe_store_generate_step( $metadata, 42, 'update' );

		Actions\expectDone( 'imagify_after_auto_optimization_init' )->never();

		$this->assertSame( $metadata, $auto_optimization->store_ids_to_optimize( $metadata, 42 ) );

		// The step is left in place for do_auto_optimization_after_meta_update() to pick up.
		$this->assertTrue( $auto_optimization->has_step( 42, 'update' ) );
		$this->assertTrue( $auto_optimization->has_step( 42, 'upload' ) );
	}

	/**
	 * Test: deferring happens once. A later metadata write for the same attachment, with the
	 * flag gone, launches normally rather than being skipped again.
	 */
	public function testDeferringAppliesOnlyToThatPass(): void {
		$this->stubDependencies();
		Functions\when( 'get_transient' )->justReturn( 1 );

		$auto_optimization = new Imagify_Auto_Optimization();
		$metadata          = [ 'file' => 'image.jpg' ];

		$auto_optimization->maybe_store_generate_step( $metadata, 42, 'update' );
		$auto_optimization->store_ids_to_optimize( $metadata, 42 );

		Actions\expectDone( 'imagify_after_auto_optimization_init' )->once();

		$auto_optimization->store_ids_to_optimize( $metadata, 42 );
	}
}
