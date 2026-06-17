<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Bulk;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify\Bulk\Bulk;
use Imagify\Bulk\BulkInterface;
use Imagify\Tests\Unit\TestCase;
use WP_Error;

/**
 * Tests for \Imagify\Bulk\Bulk::run_restore().
 *
 * @covers \Imagify\Bulk\Bulk::run_restore
 * @group  BulkRunRestore
 * @since  2.3
 */
class RunRestoreTest extends TestCase {

	/**
	 * Test: no eligible media IDs returns a no-images result.
	 */
	public function testReturnsNoImagesWhenNoEligibleMedia(): void {
		Filters\expectApplied( 'imagify_bulk_class_name' )
			->once()
			->andReturn( BulkEmptyStub::class );

		$result = ( new Bulk() )->run_restore( 'wp' );

		$this->assertSame(
			[
				'success'  => false,
				'message'  => 'no-images',
				'restored' => 0,
				'errors'   => 0,
				'total'    => 0,
			],
			$result
		);
	}

	/**
	 * Test: all media restore successfully — restored count equals total, errors is 0.
	 */
	public function testCountsAllRestoredWhenNoErrors(): void {
		Filters\expectApplied( 'imagify_bulk_class_name' )
			->once()
			->andReturn( BulkWithThreeIdsStub::class );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( new BulkProcessStub( true ) );
		Functions\when( 'is_wp_error' )->justReturn( false );

		$result = ( new Bulk() )->run_restore( 'wp' );

		$this->assertSame(
			[
				'success'  => true,
				'message'  => 'success',
				'restored' => 3,
				'errors'   => 0,
				'total'    => 3,
			],
			$result
		);
	}

	/**
	 * Test: all media fail to restore — errors count equals total, restored is 0.
	 */
	public function testCountsAllErrorsWhenAllRestoresFail(): void {
		Filters\expectApplied( 'imagify_bulk_class_name' )
			->once()
			->andReturn( BulkWithThreeIdsStub::class );

		$wp_error = new WP_Error( 'restore_failed', 'Could not restore.' );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( new BulkProcessStub( $wp_error ) );
		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) {
				return $thing instanceof WP_Error;
			}
		);

		$result = ( new Bulk() )->run_restore( 'wp' );

		$this->assertSame(
			[
				'success'  => true,
				'message'  => 'success',
				'restored' => 0,
				'errors'   => 3,
				'total'    => 3,
			],
			$result
		);
	}

	/**
	 * Test: mixed results — restored and errors are counted independently.
	 */
	public function testCountsMixedRestoredAndErrors(): void {
		Filters\expectApplied( 'imagify_bulk_class_name' )
			->once()
			->andReturn( BulkWithThreeIdsStub::class );

		$wp_error = new WP_Error( 'restore_failed', 'Could not restore.' );

		// imagify_get_optimization_process is called once per media ID.
		// Return a failing process for media ID 1, success for 2 and 3.
		Functions\when( 'imagify_get_optimization_process' )->alias(
			function ( $media_id ) use ( $wp_error ) {
				return new BulkProcessStub( 1 === $media_id ? $wp_error : true );
			}
		);

		Functions\when( 'is_wp_error' )->alias(
			function ( $thing ) {
				return $thing instanceof WP_Error;
			}
		);

		$result = ( new Bulk() )->run_restore( 'wp' );

		$this->assertSame(
			[
				'success'  => true,
				'message'  => 'success',
				'restored' => 2,
				'errors'   => 1,
				'total'    => 3,
			],
			$result
		);
	}
}

/**
 * Stub bulk: no eligible media.
 */
class BulkEmptyStub implements BulkInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		return [];
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

/**
 * Stub optimization process that returns a fixed result on restore().
 */
class BulkProcessStub {

	/**
	 * The value to return from restore().
	 *
	 * @var mixed
	 */
	private $result;

	/**
	 * @param mixed $result The value restore() should return.
	 */
	public function __construct( $result ) {
		$this->result = $result;
	}

	/**
	 * Stub restore.
	 *
	 * @return mixed
	 */
	public function restore() {
		return $this->result;
	}
}

/**
 * Stub bulk: three eligible media IDs.
 */
class BulkWithThreeIdsStub implements BulkInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_unoptimized_media_ids( $optimization_level ) {
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_optimized_media_ids(): array {
		return [ 1, 2, 3 ];
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
