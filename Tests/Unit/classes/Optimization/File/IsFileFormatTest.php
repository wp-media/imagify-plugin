<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\File;

use Imagify\Optimization\File;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Optimization\File::is_file_format().
 *
 * @covers \Imagify\Optimization\File::is_file_format
 * @group  Optimization
 */
class IsFileFormatTest extends TestCase {

	/**
	 * Path used for the fake file under test.
	 *
	 * @var string
	 */
	private $path = '/tmp/fake-download-tmpfile';

	/**
	 * Builds a File instance with a mocked filesystem injected, bypassing the real
	 * constructor (which requires Imagify_Filesystem::get_instance(), unavailable here).
	 *
	 * @param \Mockery\MockInterface $filesystem Mock for the Imagify_Filesystem dependency.
	 *
	 * @return File
	 */
	private function make_file( $filesystem ): File {
		$file = ( new \ReflectionClass( File::class ) )->newInstanceWithoutConstructor();

		$this->setPropertyValue( 'path', $file, $this->path );
		$this->setPropertyValue( 'filesystem', $file, $filesystem );

		return $file;
	}

	/**
	 * Tests is_file_format() against genuine and mismatched next-gen payloads.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'get_contents' )
			->with( $this->path )
			->andReturn( $config['header'] );

		$file = $this->make_file( $filesystem );

		$method = ( new \ReflectionClass( File::class ) )->getMethod( 'is_file_format' );
		$method->setAccessible( true );

		$result = $method->invoke( $file, $this->path, $config['format'] );

		$this->assertSame( $expected['result'], $result );
	}
}
