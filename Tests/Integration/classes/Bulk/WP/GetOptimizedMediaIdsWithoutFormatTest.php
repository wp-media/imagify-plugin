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
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Absolute paths created by the fixtures, removed in tear_down().
	 *
	 * @var array
	 */
	protected $created_files = [];

	/**
	 * Cleans up the files created by the fixtures.
	 */
	public function tear_down() {
		foreach ( $this->created_files as $path ) {
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}

		$this->created_files = [];

		parent::tear_down();
	}

	/**
	 * Creates an optimized attachment carrying the given `_imagify_data` sizes.
	 *
	 * The media file and its backup are really written to disk: the query filters its results
	 * against the filesystem after the SQL runs, and drops any media whose file or backup is
	 * missing. Without both files the media never reaches the result set and the test would
	 * pass whatever the SQL does.
	 *
	 * @param array $sizes The `sizes` sub-array of `_imagify_data`.
	 *
	 * @return int
	 */
	private function create_optimized_attachment( array $sizes ) {
		$uploads   = wp_upload_dir();
		$filename  = 'imagify-nextgen-' . uniqid() . '.jpg';
		$file_path = trailingslashit( $uploads['basedir'] ) . $filename;

		wp_mkdir_p( dirname( $file_path ) );
		file_put_contents( $file_path, 'not-a-real-jpeg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->created_files[] = $file_path;

		$backup_path = get_imagify_attachment_backup_path( $file_path );

		wp_mkdir_p( dirname( $backup_path ) );
		file_put_contents( $backup_path, 'not-a-real-jpeg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->created_files[] = $backup_path;

		$attachment_id = $this->factory()->attachment->create_object(
			[
				'file'           => $filename,
				'post_mime_type' => 'image/jpeg',
				'post_status'    => 'inherit',
			]
		);

		update_post_meta( $attachment_id, '_wp_attached_file', $filename );
		update_post_meta( $attachment_id, '_wp_attachment_metadata', [ 'file' => $filename ] );
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
	 * The optimization data of a size that was optimized normally.
	 *
	 * @return array
	 */
	private function get_successful_size_data() {
		return [
			'success'        => true,
			'original_size'  => 1000,
			'optimized_size' => 800,
			'percent'        => 20.0,
		];
	}

	/**
	 * Guard: a media with no next-gen entry at all is returned, so the fixtures really do reach
	 * the result set. Without this, the exclusion test below could pass for the wrong reason.
	 */
	public function testReturnsMediaWithoutAnyNextGenEntry() {
		$attachment_id = $this->create_optimized_attachment(
			[
				'full' => $this->get_successful_size_data(),
			]
		);

		$result = ( new BulkWP() )->get_optimized_media_ids_without_format( 'avif' );

		$this->assertContains( $attachment_id, $result['ids'] );
	}

	/**
	 * A media whose AVIF conversion was permanently refused is not returned any more.
	 */
	public function testExcludesMediaWithPermanentNextGenError() {
		$attachment_id = $this->create_optimized_attachment(
			[
				'full'              => $this->get_successful_size_data(),
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
	 * A media whose AVIF conversion failed transiently is still returned, so it gets retried.
	 */
	public function testKeepsMediaWithTransientNextGenError() {
		$attachment_id = $this->create_optimized_attachment(
			[
				'full'              => $this->get_successful_size_data(),
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
