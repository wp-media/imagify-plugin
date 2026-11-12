<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Data\CustomFolders;

use Imagify\Media\MediaInterface;
use Imagify\Optimization\Data\CustomFolders;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Optimization\Data\CustomFolders::update_size_optimization_data().
 *
 * @covers \Imagify\Optimization\Data\CustomFolders::update_size_optimization_data
 * @group  NextGenPermanentError
 */
class Test_UpdateSizeOptimizationData extends TestCase {

	/**
	 * Builds a data instance bound to a valid media.
	 *
	 * @param array $row The row the instance reads before writing.
	 *
	 * @return CustomFoldersDataStub
	 */
	private function get_data_instance( array $row = [] ): CustomFoldersDataStub {
		$media = Mockery::mock( MediaInterface::class );

		$media->shouldReceive( 'is_valid' )->andReturn( true );

		return new CustomFoldersDataStub( $media, $row );
	}

	/**
	 * Test: a permanent error is stored with `permanent_error` as the very first key.
	 */
	public function testShouldStorePermanentErrorAsFirstKey() {
		$data = $this->get_data_instance();

		$data->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$size_data = $data->updated['data']['sizes']['full@imagify-avif'];

		$this->assertSame( [ 'permanent_error', 'success', 'error' ], array_keys( $size_data ) );
		$this->assertTrue( $size_data['permanent_error'] );
		$this->assertFalse( $size_data['success'] );
		$this->assertSame( 'AVIF file is larger than the original image', $size_data['error'] );
	}

	/**
	 * Test: the serialized entry starts with the prefix the bulk queries match on.
	 */
	public function testShouldSerializePermanentErrorWithThePrefixTheBulkQueriesMatch() {
		$data = $this->get_data_instance();

		$data->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$this->assertStringContainsString(
			'full@imagify-avif";a:3:{s:15:"permanent_error";b:1;',
			serialize( $data->updated['data']['sizes'] ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		);
	}

	/**
	 * Test: a transient error is stored without the flag, so the file keeps being retried.
	 */
	public function testShouldStoreTransientErrorWithoutTheFlag() {
		$data = $this->get_data_instance();

		$data->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success' => false,
				'error'   => 'cURL error 28: Operation timed out',
			]
		);

		$this->assertSame(
			[ 'success', 'error' ],
			array_keys( $data->updated['data']['sizes']['full@imagify-avif'] )
		);
	}

	/**
	 * Test: previously stored sizes are preserved when a permanent error is added.
	 */
	public function testShouldPreserveOtherSizesWhenStoringAPermanentError() {
		$data = $this->get_data_instance(
			[
				'data' => [
					'sizes' => [
						'thumbnail' => [
							'success'        => true,
							'original_size'  => 1000,
							'optimized_size' => 800,
							'percent'        => 20,
						],
					],
				],
			]
		);

		$data->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$this->assertTrue( $data->updated['data']['sizes']['thumbnail']['success'] );
		$this->assertTrue( $data->updated['data']['sizes']['full@imagify-avif']['permanent_error'] );
	}

	/**
	 * Test: nothing is written when the media is not valid.
	 */
	public function testShouldWriteNothingWhenMediaIsNotValid() {
		$media = Mockery::mock( MediaInterface::class );

		$media->shouldReceive( 'is_valid' )->andReturn( false );

		$data = new CustomFoldersDataStub( $media, [] );

		$data->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$this->assertNull( $data->updated );
	}
}

/**
 * Data class with an in-memory row instead of a database row.
 */
class CustomFoldersDataStub extends CustomFolders {

	/**
	 * The data passed to update_row().
	 *
	 * @var array|null
	 */
	public $updated;

	/**
	 * The row to return from get_row().
	 *
	 * @var array
	 */
	private $stub_row;

	/**
	 * The constructor.
	 *
	 * @param MediaInterface $media The media instance.
	 * @param array          $row   The row to return from get_row().
	 */
	public function __construct( $media, array $row ) {
		$this->media = $media;
		$this->stub_row   = $row;
	}

	/**
	 * Returns the in-memory row.
	 *
	 * @return array
	 */
	public function get_row() {
		return $this->stub_row;
	}

	/**
	 * Returns empty defaults: the database columns are irrelevant here.
	 *
	 * @return array
	 */
	protected function get_reset_data() {
		return [];
	}

	/**
	 * Records what would have been written to the database.
	 *
	 * @param array $data The data to update.
	 */
	public function update_row( $data ) {
		$this->updated = $data;
	}
}
