<?php

namespace Imagify\Tests\Integration\classes\Bulk\WP;

use Imagify\Bulk\WP as BulkWP;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\Bulk\WP::get_optimized_media_ids_without_format().
 *
 * @covers \Imagify\Bulk\WP::get_optimized_media_ids_without_format
 * @group  NextGenPermanentError
 */
class GetOptimizedMediaIdsWithoutFormatTest extends TestCase {
	protected $useApi = false;

	/**
	 * Creates an optimized attachment carrying the given `_imagify_data` sizes.
	 *
	 * @param array $sizes The `sizes` sub-array of `_imagify_data`.
	 *
	 * @return int
	 */
	private function create_optimized_attachment( array $sizes ): int {
		$attachment_id = $this->factory()->attachment->create_object(
			[
				'file'           => 'image.jpg',
				'post_mime_type' => 'image/jpeg',
				'post_status'    => 'inherit',
			]
		);

		update_post_meta( $attachment_id, '_wp_attached_file', 'image.jpg' );
		update_post_meta( $attachment_id, '_wp_attachment_metadata', [ 'file' => 'image.jpg' ] );
		update_post_meta( $attachment_id, '_imagify_status', 'success' );
		update_post_meta(
			$attachment_id,
			'_imagify_data',
			[
				'sizes' => $sizes,
			]
		);

		return $attachment_id;
	}

	/**
	 * Test: a media whose AVIF conversion was permanently refused is not returned any more.
	 */
	public function testExcludesMediaWithPermanentNextGenError() {
		$attachment_id = $this->create_optimized_attachment(
			[
				'full'               => [
					'success'        => true,
					'original_size'  => 1000,
					'optimized_size' => 800,
					'percent'        => 20.0,
				],
				'full@imagify-avif' => [
					'permanent_error' => true,
					'success'         => false,
					'error'           => 'AVIF file is larger than the original image',
				],
			]
		);

		$result = ( new BulkWP() )->get_optimized_media_ids_without_format( 'avif' );

		$this->assertNotContains( $attachment_id, $result['ids'] );
	}

	/**
	 * Test: a media whose AVIF conversion failed transiently is still returned, so it gets retried.
	 */
	public function testKeepsMediaWithTransientNextGenError() {
		$attachment_id = $this->create_optimized_attachment(
			[
				'full'               => [
					'success'        => true,
					'original_size'  => 1000,
					'optimized_size' => 800,
					'percent'        => 20.0,
				],
				'full@imagify-avif' => [
					'success' => false,
					'error'   => 'cURL error 28: Operation timed out',
				],
			]
		);

		$result = ( new BulkWP() )->get_optimized_media_ids_without_format( 'avif' );

		$this->assertContains( $attachment_id, $result['ids'] );
	}
}
