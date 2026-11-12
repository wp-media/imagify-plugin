<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Process;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Imagify\Optimization\Data\DataInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use ReflectionClass;
use WP_Error;

/**
 * Tests for \Imagify\Optimization\Process\AbstractProcess::compare_next_gen_file_size().
 *
 * The method caches the `imagify_keep_large_next_gen` filter result in a function-level static,
 * so each test runs in its own process to get a fresh value.
 *
 * @covers \Imagify\Optimization\Process\AbstractProcess::compare_next_gen_file_size
 * @group  Optimization
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CompareNextGenFileSizeTest extends TestCase {

	/**
	 * Tests that heavier next-gen files are discarded by default, unless the filter opts in.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );

		if ( null !== $config['filter_value'] ) {
			Filters\expectApplied( 'imagify_keep_large_next_gen' )
				->once()
				->with( false )
				->andReturn( $config['filter_value'] );
		}

		$data = Mockery::mock( DataInterface::class );
		$data->shouldReceive( 'get_size_data' )
			->with( 'thumbnail' )
			->andReturn( [ 'optimized_size' => $config['optimized_size'] ] );

		$file = Mockery::mock();
		$file->shouldReceive( 'is_image' )->andReturn( true );
		$file->shouldReceive( 'get_path' )->andReturn( '/uploads/thumbnail.jpg.webp' );

		$filesystem = Mockery::mock();

		if ( $expected['deleted'] ) {
			$filesystem->shouldReceive( 'delete' )
				->once()
				->with( '/uploads/thumbnail.jpg.webp' );
		} else {
			$filesystem->shouldNotReceive( 'delete' );
		}

		$process = ( new ReflectionClass( ConcreteProcessDouble::class ) )->newInstanceWithoutConstructor();
		$this->setPropertyValue( 'data', $process, $data );
		$this->setPropertyValue( 'filesystem', $process, $filesystem );

		$response = (object) [ 'new_size' => $config['new_size'] ];

		$method = $this->get_reflective_method( 'compare_next_gen_file_size', ConcreteProcessDouble::class );
		$result = $method->invoke(
			$process,
			[
				'response'                => $response,
				'file'                    => $file,
				'is_next_gen'             => true,
				'non_next_gen_thumb_size' => 'thumbnail',
				'non_next_gen_file_path'  => '/uploads/thumbnail.jpg',
				'optimization_level'      => 2,
			]
		);

		if ( $expected['is_error'] ) {
			$this->assertInstanceOf( WP_Error::class, $result );
			$this->assertSame( 'next_gen_heavy', $result->get_error_code() );
		} else {
			$this->assertSame( $response, $result );
		}
	}
}
