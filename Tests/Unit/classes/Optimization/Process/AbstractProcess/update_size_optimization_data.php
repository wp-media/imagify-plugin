<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Process\AbstractProcess;

use Brain\Monkey\Functions;
use Imagify\Optimization\Data\DataInterface;
use Imagify\Optimization\Process\AbstractProcess;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Optimization\Process\AbstractProcess::update_size_optimization_data().
 *
 * @covers \Imagify\Optimization\Process\AbstractProcess::update_size_optimization_data
 * @group  NextGenPermanentError
 */
class Test_UpdateSizeOptimizationData extends TestCase {

	/**
	 * Stores the arguments the data layer received.
	 *
	 * @var array
	 */
	private $stored = [];

	/**
	 * Sets up the common function stubs.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->stored = [];

		Functions\when( 'imagify_translate_api_message' )->returnArg();
	}

	/**
	 * Builds a process whose data layer records what it is asked to store.
	 *
	 * @return ProcessStub
	 */
	private function get_process(): ProcessStub {
		$data = Mockery::mock( DataInterface::class );

		$data->shouldReceive( 'update_size_optimization_data' )
			->andReturnUsing(
				function ( $size, $size_data ) {
					$this->stored[ $size ] = $size_data;
				}
			);

		return new ProcessStub( $data, ProcessStub::AVIF_SUFFIX );
	}

	/**
	 * Test: a next-gen size refused by the API is stored under the suffixed key as a permanent error.
	 */
	public function testStoresPermanentErrorUnderSuffixedKeyForNextGenSize(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$process = $this->get_process();
		$size    = 'full' . ProcessStub::AVIF_SUFFIX;

		$process->update_size_optimization_data(
			(object) [ 'message' => 'Avif is less performant than original' ],
			$size,
			1
		);

		$this->assertArrayHasKey( $size, $this->stored );
		$this->assertFalse( $this->stored[ $size ]['success'] );
		$this->assertTrue( $this->stored[ $size ]['permanent_error'] );
		$this->assertSame( 'Avif is less performant than original', $this->stored[ $size ]['error'] );
	}

	/**
	 * Test: the base size record is left untouched when a next-gen conversion is refused.
	 */
	public function testDoesNotOverwriteBaseSizeWhenNextGenIsRefused(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$process = $this->get_process();

		$process->update_size_optimization_data(
			(object) [ 'message' => 'Avif is less performant than original' ],
			'full' . ProcessStub::AVIF_SUFFIX,
			1
		);

		$this->assertArrayNotHasKey( 'full', $this->stored );
	}

	/**
	 * Test: a regular size carrying a message keeps its previous behaviour (success, no flag).
	 */
	public function testKeepsPreviousBehaviourForNonNextGenSize(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$process = $this->get_process();

		$process->update_size_optimization_data(
			(object) [
				'message'       => 'WELL DONE. This image is already compressed, no further compression required',
				'original_size' => 100,
				'new_size'      => 100,
			],
			'full',
			1
		);

		$this->assertArrayHasKey( 'full', $this->stored );
		$this->assertTrue( $this->stored['full']['success'] );
		$this->assertArrayNotHasKey( 'permanent_error', $this->stored['full'] );
	}

	/**
	 * Test: a transient next-gen failure is stored without the flag, so it stays retryable.
	 */
	public function testKeepsTransientNextGenErrorRetryable(): void {
		Functions\when( 'is_wp_error' )->justReturn( true );

		$process = $this->get_process();
		$size    = 'full' . ProcessStub::AVIF_SUFFIX;

		$process->update_size_optimization_data(
			new WP_Error( 'http_error', 'cURL error 28: Operation timed out' ),
			$size,
			1
		);

		$this->assertArrayHasKey( $size, $this->stored );
		$this->assertFalse( $this->stored[ $size ]['success'] );
		$this->assertArrayNotHasKey( 'permanent_error', $this->stored[ $size ] );
		$this->assertSame( 'error', $this->stored[ $size ]['status'] );
	}

	/**
	 * Test: a successful next-gen conversion is still stored as a success under the suffixed key.
	 */
	public function testStoresSuccessfulNextGenConversionUnchanged(): void {
		Functions\when( 'is_wp_error' )->justReturn( false );

		$process = $this->get_process();
		$size    = 'full' . ProcessStub::AVIF_SUFFIX;

		$process->update_size_optimization_data(
			(object) [
				'original_size' => 1000,
				'new_size'      => 400,
			],
			$size,
			1
		);

		$this->assertArrayHasKey( $size, $this->stored );
		$this->assertTrue( $this->stored[ $size ]['success'] );
		$this->assertArrayNotHasKey( 'permanent_error', $this->stored[ $size ] );
		$this->assertSame( 400, $this->stored[ $size ]['optimized_size'] );
	}
}

/**
 * Concrete process with a constructor that does not touch the filesystem.
 */
class ProcessStub extends AbstractProcess {

	/**
	 * The constructor.
	 *
	 * @param DataInterface $data   The data instance.
	 * @param string        $format The current next-gen format suffix.
	 */
	public function __construct( $data, $format ) {
		$this->data   = $data;
		$this->format = $format;
	}

	/**
	 * Not used by these tests.
	 *
	 * @return array
	 */
	public function get_missing_sizes() {
		return [];
	}

	/**
	 * Not used by these tests.
	 *
	 * @return bool
	 */
	public function optimize_missing_thumbnails() {
		return true;
	}
}
