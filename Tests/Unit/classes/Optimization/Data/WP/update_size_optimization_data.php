<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Data\WP;

use Brain\Monkey\Functions;
use Imagify\Media\MediaInterface;
use Imagify\Optimization\Data\WP;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Optimization\Data\WP::update_size_optimization_data().
 *
 * @covers \Imagify\Optimization\Data\WP::update_size_optimization_data
 * @group  NextGenPermanentError
 */
class Test_UpdateSizeOptimizationData extends TestCase {

	/**
	 * The `_imagify_data` meta value the class last stored.
	 *
	 * @var array
	 */
	private $stored = [];

	/**
	 * The `_imagify_data` meta value the class reads before writing.
	 *
	 * @var mixed
	 */
	private $existing = [];

	/**
	 * Sets up the post meta stubs.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->stored   = [];
		$this->existing = [];

		Functions\when( 'get_post_meta' )->alias(
			function ( $id, $key ) {
				return '_imagify_data' === $key ? $this->existing : '';
			}
		);

		Functions\when( 'update_post_meta' )->alias(
			function ( $id, $key, $value ) {
				if ( '_imagify_data' === $key ) {
					$this->stored = $value;
				}

				return true;
			}
		);
	}

	/**
	 * Builds a data instance bound to a valid media.
	 *
	 * @return WP
	 */
	private function get_data_instance(): WP {
		$media = Mockery::mock( MediaInterface::class );

		$media->shouldReceive( 'is_valid' )->andReturn( true );
		$media->shouldReceive( 'get_id' )->andReturn( 13 );

		return new DataStub( $media );
	}

	/**
	 * Test: a permanent error is stored with `permanent_error` as the very first key.
	 */
	public function testShouldStorePermanentErrorAsFirstKey() {
		$this->get_data_instance()->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$size_data = $this->stored['sizes']['full@imagify-avif'];

		$this->assertSame( [ 'permanent_error', 'success', 'error' ], array_keys( $size_data ) );
		$this->assertTrue( $size_data['permanent_error'] );
		$this->assertFalse( $size_data['success'] );
		$this->assertSame( 'AVIF file is larger than the original image', $size_data['error'] );
	}

	/**
	 * Test: the serialized entry starts with the prefix the bulk queries match on.
	 */
	public function testShouldSerializePermanentErrorWithThePrefixTheBulkQueriesMatch() {
		$this->get_data_instance()->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$this->assertStringContainsString(
			'full@imagify-avif";a:3:{s:15:"permanent_error";b:1;',
			serialize( $this->stored['sizes'] ) // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		);
	}

	/**
	 * Test: a transient error is stored without the flag, so the media keeps being retried.
	 */
	public function testShouldStoreTransientErrorWithoutTheFlag() {
		$this->get_data_instance()->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success' => false,
				'error'   => 'cURL error 28: Operation timed out',
			]
		);

		$size_data = $this->stored['sizes']['full@imagify-avif'];

		$this->assertSame( [ 'success', 'error' ], array_keys( $size_data ) );
		$this->assertFalse( $size_data['success'] );
		$this->assertSame( 'cURL error 28: Operation timed out', $size_data['error'] );
	}

	/**
	 * Test: a falsy `permanent_error` value does not add the flag.
	 */
	public function testShouldNotAddTheFlagWhenPermanentErrorIsFalsy() {
		$this->get_data_instance()->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'cURL error 28: Operation timed out',
				'permanent_error' => false,
			]
		);

		$this->assertSame( [ 'success', 'error' ], array_keys( $this->stored['sizes']['full@imagify-avif'] ) );
	}

	/**
	 * Test: previously stored sizes are preserved when a permanent error is added.
	 */
	public function testShouldPreserveOtherSizesWhenStoringAPermanentError() {
		$this->existing = [
			'sizes' => [
				'full' => [
					'success'        => true,
					'original_size'  => 1000,
					'optimized_size' => 800,
					'percent'        => 20,
				],
			],
		];

		$this->get_data_instance()->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$this->assertTrue( $this->stored['sizes']['full']['success'] );
		$this->assertTrue( $this->stored['sizes']['full@imagify-avif']['permanent_error'] );
	}

	/**
	 * Test: nothing is stored when the media is not valid.
	 */
	public function testShouldStoreNothingWhenMediaIsNotValid() {
		$media = Mockery::mock( MediaInterface::class );

		$media->shouldReceive( 'is_valid' )->andReturn( false );

		( new DataStub( $media ) )->update_size_optimization_data(
			'full@imagify-avif',
			[
				'success'         => false,
				'error'           => 'AVIF file is larger than the original image',
				'permanent_error' => true,
			]
		);

		$this->assertSame( [], $this->stored );
	}
}

/**
 * Data class with a constructor that does not touch the filesystem.
 */
class DataStub extends WP {

	/**
	 * The constructor.
	 *
	 * @param MediaInterface $media The media instance.
	 */
	public function __construct( $media ) {
		$this->media = $media;
	}
}
