<?php

namespace Imagify\Tests\Integration\classes\Optimization\Process;

use Imagify\Optimization\File;
use Imagify\Optimization\Process\WP;
use Imagify\Tests\Integration\TestCase;
use WP_Error;

/**
 * Tests for \Imagify\Optimization\Process\AbstractProcess::compare_next_gen_file_size().
 *
 * Runs against a real attachment, its real `_imagify_data` and real files on disk. The method
 * caches the `imagify_keep_large_next_gen` filter result in a function-level static, so each
 * test runs in its own process to read the filter afresh.
 *
 * @covers \Imagify\Optimization\Process\AbstractProcess::compare_next_gen_file_size
 * @group  Optimization
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CompareNextGenFileSizeTest extends TestCase {
	/**
	 * This test does not call the Imagify API.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Absolute paths created by the test, removed in tear_down().
	 *
	 * @var array
	 */
	protected $created_files = [];

	/**
	 * Cleans up the files created by the test.
	 */
	public function tear_down() {
		foreach ( $this->created_files as $path ) {
			wp_delete_file( $path );
		}

		$this->created_files = [];

		parent::tear_down();
	}

	/**
	 * Tests that heavier next-gen files are discarded by default, unless the filter opts in.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ) {
		if ( null !== $config['filter_value'] ) {
			add_filter( 'imagify_keep_large_next_gen', $config['filter_value'] ? '__return_true' : '__return_false' );
		}

		$attachment_id = $this->create_optimized_attachment( $config['optimized_size'] );

		// The file the next-gen version was just generated from (a temporary copy in real runs).
		$next_gen_source = $this->create_file( 'imagify-nextgen-source-' . uniqid() . '.jpg' );

		$process  = new WP( $attachment_id );
		$response = (object) [ 'new_size' => $config['new_size'] ];

		$result = $this->get_reflective_method( 'compare_next_gen_file_size', WP::class )->invoke(
			$process,
			[
				'response'                => $response,
				'file'                    => new File( $next_gen_source ),
				'is_next_gen'             => true,
				'next_gen_format'         => 'webp',
				'non_next_gen_thumb_size' => 'full',
				'non_next_gen_file_path'  => get_attached_file( $attachment_id ),
				'optimization_level'      => 2,
			]
		);

		if ( $expected['is_error'] ) {
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'next_gen_heavy', $result->get_error_code() );
		} else {
			$this->assertSame( $response, $result );
		}

		$this->assertSame( $expected['deleted'], ! file_exists( $next_gen_source ) );
	}

	/**
	 * Creates an attachment whose full size is optimized to the given file size.
	 *
	 * @param int $optimized_size Optimized file size of the full size, in bytes.
	 *
	 * @return int
	 */
	private function create_optimized_attachment( $optimized_size ) {
		$filename = 'imagify-original-' . uniqid() . '.jpg';

		$this->create_file( $filename );

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
				'sizes' => [
					'full' => [
						'success'        => true,
						'original_size'  => 2000,
						'optimized_size' => $optimized_size,
						'percent'        => 50.0,
					],
				],
			]
		);

		return $attachment_id;
	}

	/**
	 * Writes a file in the uploads directory and registers it for cleanup.
	 *
	 * @param string $filename File name, relative to the uploads base directory.
	 *
	 * @return string Absolute path to the file.
	 */
	private function create_file( $filename ) {
		$uploads = wp_upload_dir();
		$path    = trailingslashit( $uploads['basedir'] ) . $filename;

		wp_mkdir_p( dirname( $path ) );
		file_put_contents( $path, 'not-a-real-jpeg' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$this->created_files[] = $path;

		return $path;
	}
}
