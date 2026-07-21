<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Bulk\BulkInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Bulk\Bulk::run_optimize().
 *
 * `Imagify_Requirements` is a legacy classmap-autoloaded class; it is stubbed via a
 * Mockery alias mock in every test, so every test in this class runs in its own
 * process (`@runTestsInSeparateProcesses`) to guarantee the alias is registered
 * before the real class would otherwise be autoloaded.
 *
 * @covers \Imagify\Bulk\Bulk::run_optimize
 * @group  BulkRunOptimize
 * @since  2.3
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class RunOptimizeTest extends TestCase {

	/**
	 * Stubs `Imagify_Requirements::is_api_key_valid()` / `is_over_quota()` via a Mockery
	 * alias mock so can_optimize() lets execution reach the enqueue loop.
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
	 * Test: the imagify_bulk_optimize_media filter excludes a specific media ID
	 * from being enqueued, and the total/remaining count reflects the exclusion.
	 */
	public function testExcludesMediaIdVetoedByFilter(): void {
		$this->stubRequirements( true, false );

		Filters\expectApplied( 'imagify_bulk_class_name' )
			->once()
			->andReturn( BulkWithThreeOptimizeIdsStub::class );

		// Exclude media ID 2 (e.g. a PDF), keep 1 and 3.
		Filters\expectApplied( 'imagify_bulk_optimize_media' )
			->times( 3 )
			->andReturnUsing(
				function ( $optimize, $media_id ) {
					return 2 !== $media_id;
				}
			);

		Functions\expect( 'as_enqueue_async_action' )
			->twice()
			->withArgs(
				function ( $hook, $args, $group ) {
					return 'imagify_optimize_media' === $hook
						&& in_array( $args['id'], [ 1, 3 ], true )
						&& 'wp' === $args['context']
						&& 1 === $args['level']
						&& 'imagify-wp-optimize-media' === $group;
				}
			);

		Functions\expect( 'set_transient' )
			->once()
			->with(
				'imagify_wp_optimize_running',
				[
					'total'     => 2,
					'remaining' => 2,
				],
				DAY_IN_SECONDS
			)
			->andReturn( true );

		$result = ( new Bulk() )->run_optimize( 'wp', 1 );

		$this->assertSame(
			[
				'success' => true,
				'message' => 'success',
			],
			$result
		);
	}

	/**
	 * Test: when the filter excludes every media ID, run_optimize() returns the
	 * same 'no-images' result as if the underlying list was empty to begin with.
	 */
	public function testReturnsNoImagesWhenFilterExcludesAllMedia(): void {
		$this->stubRequirements( true, false );

		Filters\expectApplied( 'imagify_bulk_class_name' )
			->once()
			->andReturn( BulkWithThreeOptimizeIdsStub::class );

		Filters\expectApplied( 'imagify_bulk_optimize_media' )
			->times( 3 )
			->andReturn( false );

		Functions\expect( 'as_enqueue_async_action' )->never();
		Functions\expect( 'set_transient' )->never();

		$result = ( new Bulk() )->run_optimize( 'wp', 1 );

		$this->assertSame(
			[
				'success' => false,
				'message' => 'no-images',
			],
			$result
		);
	}
}

/**
 * Stub bulk: three eligible media IDs for optimization.
 */
class BulkWithThreeOptimizeIdsStub implements BulkInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		return [ 1, 2, 3 ];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_optimized_media_ids(): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_optimized_media_ids_without_format( $format ) {
		return [
			'ids'    => [],
			'errors' => [
				'no_file_path' => [],
				'no_backup'    => [],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function has_optimized_media_without_nextgen() {
		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_context_data() {
		return [];
	}
}
