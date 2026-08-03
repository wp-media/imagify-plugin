<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\Process;

use Imagify\Optimization\Process\WP;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Optimization\Process\AbstractProcess::is_next_gen_file_missing().
 *
 * @covers \Imagify\Optimization\Process\AbstractProcess::is_next_gen_file_missing
 * @group  Optimization
 */
class IsNextGenFileMissingTest extends TestCase {

	/**
	 * Builds a process instance with a mocked filesystem injected, bypassing the real
	 * constructor (which requires Imagify_Filesystem::get_instance(), unavailable here).
	 *
	 * @param \Mockery\MockInterface $filesystem Mock for the Imagify_Filesystem dependency.
	 *
	 * @return WP
	 */
	private function make_process( $filesystem ): WP {
		$process = ( new \ReflectionClass( WP::class ) )->newInstanceWithoutConstructor();

		$this->setPropertyValue( 'filesystem', $process, $filesystem );

		return $process;
	}

	/**
	 * Tests is_next_gen_file_missing() for both next-gen formats, file present and missing.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'exists' )
			->with( $config['next_gen_path'] )
			->andReturn( $config['file_exists'] );

		$process = $this->make_process( $filesystem );

		$method = ( new \ReflectionClass( WP::class ) )->getMethod( 'is_next_gen_file_missing' );
		$method->setAccessible( true );

		$result = $method->invoke( $process, $config['size'], $config['original_path'] );

		$this->assertSame( $expected['result'], $result );
	}
}
