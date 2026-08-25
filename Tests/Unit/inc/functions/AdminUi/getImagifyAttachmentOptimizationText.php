<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\Functions\AdminUi;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for get_imagify_attachment_optimization_text().
 *
 * @covers get_imagify_attachment_optimization_text
 * @group  NextGenPermanentError
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class Test_GetImagifyAttachmentOptimizationText extends TestCase {

	/**
	 * Sets up the WordPress and plugin function stubs.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		Monkey\Functions\stubTranslationFunctions();
		Monkey\Functions\stubEscapeFunctions();

		Functions\when( 'get_imagify_attachment_reoptimize_link' )->justReturn( '' );
		Functions\when( 'get_imagify_attachment_optimize_missing_thumbnails_link' )->justReturn( '' );
		Functions\when( 'get_imagify_attachment_generate_nextgen_versions_link' )->justReturn( '' );
		Functions\when( 'get_imagify_attachment_delete_nextgen_versions_link' )->justReturn( '' );
		Functions\when( 'imagify_get_optimization_level_label' )->justReturn( 'Smart compression' );

		$views = Mockery::mock( 'alias:Imagify_Views' );

		$views->shouldReceive( 'get_instance' )->andReturn( $views );
		$views->shouldReceive( 'is_media_page' )->andReturn( true );
		$views->shouldReceive( 'is_wp_library_page' )->andReturn( false );
	}

	/**
	 * Builds a process whose data layer returns the given optimization data.
	 *
	 * @param array $sizes The `sizes` entry of the optimization data.
	 *
	 * @return \Imagify\Optimization\Process\ProcessInterface
	 */
	private function get_process( array $sizes ) {
		$data = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$data->shouldReceive( 'get_optimization_data' )->andReturn(
			[
				'status'  => 'success',
				'message' => '',
				'level'   => 1,
				'sizes'   => $sizes,
				'stats'   => [],
			]
		);
		$data->shouldReceive( 'get_optimization_level' )->andReturn( 1 );
		$data->shouldReceive( 'get_optimized_size' )->andReturn( '800 B' );
		$data->shouldReceive( 'get_original_size' )->andReturn( '1 kB' );
		$data->shouldReceive( 'get_saving_percent' )->andReturn( 20 );
		$data->shouldReceive( 'get_optimized_sizes_count' )->andReturn( 0 );

		$media = Mockery::mock( 'Imagify\Media\MediaInterface' );

		$media->shouldReceive( 'get_id' )->andReturn( 13 );
		$media->shouldReceive( 'is_image' )->andReturn( true );
		$media->shouldReceive( 'has_backup' )->andReturn( false );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );

		$process->shouldReceive( 'is_valid' )->andReturn( true );
		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$process->shouldReceive( 'get_media' )->andReturn( $media );
		$process->shouldReceive( 'has_next_gen' )->andReturn( false );
		$process->shouldReceive( 'is_full_next_gen' )->andReturn( false );

		return $process;
	}

	/**
	 * Test: the reason given by the API is printed when a size holds a permanent error.
	 */
	public function testShouldPrintTheReasonWhenASizeHoldsAPermanentError() {
		$output = get_imagify_attachment_optimization_text(
			$this->get_process(
				[
					'full@imagify-avif' => [
						'permanent_error' => true,
						'success'         => false,
						'error'           => 'AVIF file is larger than the original image',
					],
				]
			)
		);

		$this->assertStringContainsString( 'Next-Gen status:', $output );
		$this->assertStringContainsString( 'AVIF file is larger than the original image', $output );
	}

	/**
	 * Test: the first flagged size wins when several sizes hold a permanent error.
	 */
	public function testShouldPrintTheFirstFlaggedReason() {
		$output = get_imagify_attachment_optimization_text(
			$this->get_process(
				[
					'thumbnail@imagify-avif' => [
						'permanent_error' => true,
						'success'         => false,
						'error'           => 'First reason',
					],
					'full@imagify-avif'      => [
						'permanent_error' => true,
						'success'         => false,
						'error'           => 'Second reason',
					],
				]
			)
		);

		$this->assertStringContainsString( 'First reason', $output );
		$this->assertStringNotContainsString( 'Second reason', $output );
	}

	/**
	 * Test: a size flagged as permanent but carrying no message prints no status line.
	 */
	public function testShouldPrintNoStatusWhenTheFlaggedSizeHasNoError() {
		$output = get_imagify_attachment_optimization_text(
			$this->get_process(
				[
					'full@imagify-avif' => [
						'permanent_error' => true,
						'success'         => false,
						'error'           => '',
					],
				]
			)
		);

		$this->assertStringNotContainsString( 'Next-Gen status:', $output );
	}

	/**
	 * Test: a transient failure prints no status line, so the media does not look permanently refused.
	 */
	public function testShouldPrintNoStatusWhenNoSizeIsFlagged() {
		$output = get_imagify_attachment_optimization_text(
			$this->get_process(
				[
					'full@imagify-avif' => [
						'success' => false,
						'error'   => 'cURL error 28: Operation timed out',
					],
				]
			)
		);

		$this->assertStringNotContainsString( 'Next-Gen status:', $output );
		$this->assertStringNotContainsString( 'cURL error 28', $output );
	}

	/**
	 * Test: no status line is printed when the media has no size data at all.
	 */
	public function testShouldPrintNoStatusWhenThereAreNoSizes() {
		$output = get_imagify_attachment_optimization_text( $this->get_process( [] ) );

		$this->assertStringNotContainsString( 'Next-Gen status:', $output );
		$this->assertStringContainsString( 'Next-Gen generated:', $output );
	}

	/**
	 * Test: the reason is escaped before being printed.
	 */
	public function testShouldEscapeTheReason() {
		$output = get_imagify_attachment_optimization_text(
			$this->get_process(
				[
					'full@imagify-avif' => [
						'permanent_error' => true,
						'success'         => false,
						'error'           => '<script>alert(1)</script>',
					],
				]
			)
		);

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}
}
