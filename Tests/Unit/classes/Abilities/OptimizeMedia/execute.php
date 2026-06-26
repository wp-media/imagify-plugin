<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\OptimizeMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Abilities\OptimizeMedia::execute().
 *
 * @covers \Imagify\Abilities\OptimizeMedia::execute
 * @group  OptimizeMedia
 */
class Test_Execute extends TestCase {

	/**
	 * Tests that execute() returns an error response when media_id is missing.
	 */
	public function testReturnsErrorWhenMediaIdIsMissing(): void {
		$ability = new OptimizeMedia();
		$result  = $ability->execute( [] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error response when media_id is zero.
	 */
	public function testReturnsErrorWhenMediaIdIsZero(): void {
		$ability = new OptimizeMedia();
		$result  = $ability->execute( [ 'media_id' => 0 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error response when media_id is negative.
	 */
	public function testReturnsErrorWhenMediaIdIsNegative(): void {
		$ability = new OptimizeMedia();
		$result  = $ability->execute( [ 'media_id' => -5 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns an error response when the attachment does not exist.
	 */
	public function testReturnsErrorWhenAttachmentDoesNotExist(): void {
		Functions\when( 'get_post' )->justReturn( false );

		$ability = new OptimizeMedia();
		$result  = $ability->execute( [ 'media_id' => 123 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertNull( $result['original_size'] );
		$this->assertNull( $result['optimized_size'] );
		$this->assertNull( $result['savings_percent'] );
		$this->assertIsString( $result['error_message'] );
	}

	/**
	 * Tests that execute() uses the provided optimization_level when optimizing.
	 */
	public function testPassesOptimizationLevelToOptimizeWhenNotOptimized(): void {
		$post = Mockery::mock( 'WP_Post' );

		Functions\when( 'get_post' )->justReturn( $post );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$data->shouldReceive( 'get_optimization_data' )->andReturn(
			[
				'stats' => [
					'original_size'  => 1000,
					'optimized_size' => 800,
				],
			]
		);

		$process->shouldReceive( 'optimize' )
			->once()
			->with( 1 )
			->andReturn( true );

		$process->shouldReceive( 'get_media' )->andReturn( null );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$ability->execute(
			[
				'media_id'           => 123,
				'optimization_level' => 1,
			]
		);
	}

	/**
	 * Tests that execute() uses reoptimize() when media is already optimized.
	 */
	public function testCallsReoptimizeWhenMediaIsOptimized(): void {
		$post = Mockery::mock( 'WP_Post' );

		Functions\when( 'get_post' )->justReturn( $post );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( true );

		$process->shouldReceive( 'reoptimize' )
			->once()
			->andReturn( true );

		$process->shouldReceive( 'get_media' )->andReturn( null );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$ability->execute(
			[
				'media_id'           => 123,
				'optimization_level' => 2,
			]
		);
	}

	/**
	 * Tests that execute() returns an error response when optimize() returns a WP_Error.
	 */
	public function testReturnsErrorWhenOptimizeReturnsWpError(): void {
		$post = Mockery::mock( 'WP_Post' );

		Functions\when( 'get_post' )->justReturn( $post );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		// is_optimized() is false — first-time optimization path.
		// get_optimization_data() and get_media() are called by get_media_original_size().
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$data->shouldReceive( 'get_optimization_data' )->andReturn( [] );
		$process->shouldReceive( 'get_media' )->andReturn( null );

		// Use a real WP_Error so is_wp_error() (instanceof check) works.
		$error = new \WP_Error( 'optimization_failed', 'Optimization failed.' );

		$process->shouldReceive( 'optimize' )->andReturn( $error );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		$ability = new OptimizeMedia();
		$result  = $ability->execute( [ 'media_id' => 123 ] );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 'Optimization failed.', $result['error_message'] );
	}

	/**
	 * Tests that execute() returns a success response with correct shape on successful optimization.
	 */
	public function testReturnsSuccessResponseOnSuccessfulOptimization(): void {
		$post = Mockery::mock( 'WP_Post' );

		Functions\when( 'get_post' )->justReturn( $post );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$process->shouldReceive( 'optimize' )->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		// Mock the protected methods by creating a partial mock.
		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$result = $ability->execute( [ 'media_id' => 123 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 1000, $result['original_size'] );
		$this->assertSame( 800, $result['optimized_size'] );
		$this->assertIsFloat( $result['savings_percent'] );
		$this->assertSame( 20.0, $result['savings_percent'] );
		$this->assertNull( $result['error_message'] );
	}

	/**
	 * Tests that execute() returns a success response with null savings_percent when original_size is 0.
	 */
	public function testReturnsSavingsPercentNullWhenOriginalSizeIsZero(): void {
		$post = Mockery::mock( 'WP_Post' );

		Functions\when( 'get_post' )->justReturn( $post );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$process->shouldReceive( 'optimize' )->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		// Mock the protected methods.
		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 0 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( 800 );

		$result = $ability->execute( [ 'media_id' => 123 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertNull( $result['savings_percent'] );
	}

	/**
	 * Tests that execute() returns a success response with null savings_percent when optimized_size is null.
	 */
	public function testReturnsSavingsPercentNullWhenOptimizedSizeIsNull(): void {
		$post = Mockery::mock( 'WP_Post' );

		Functions\when( 'get_post' )->justReturn( $post );

		$process = Mockery::mock( 'Imagify\Optimization\Process\ProcessInterface' );
		$data    = Mockery::mock( 'Imagify\Optimization\Data\DataInterface' );

		$process->shouldReceive( 'get_data' )->andReturn( $data );
		$data->shouldReceive( 'is_optimized' )->andReturn( false );
		$process->shouldReceive( 'optimize' )->andReturn( true );

		Functions\when( 'imagify_get_optimization_process' )->justReturn( $process );

		// Mock the protected methods.
		$ability = Mockery::mock( OptimizeMedia::class )->makePartial();
		$ability->shouldAllowMockingProtectedMethods();
		$ability->shouldReceive( 'get_media_original_size' )->andReturn( 1000 );
		$ability->shouldReceive( 'get_media_optimized_size' )->andReturn( null );

		$result = $ability->execute( [ 'media_id' => 123 ] );

		$this->assertSame( 'success', $result['status'] );
		$this->assertNull( $result['savings_percent'] );
	}
}
