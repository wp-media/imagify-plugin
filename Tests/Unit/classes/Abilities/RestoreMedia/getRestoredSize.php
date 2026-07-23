<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\RestoreMedia;

use Imagify\Abilities\RestoreMedia;
use Imagify\Tests\Unit\TestCase;
use ReflectionMethod;

/**
 * Tests for \Imagify\Abilities\RestoreMedia::get_restored_size().
 *
 * @covers \Imagify\Abilities\RestoreMedia::get_restored_size
 * @group  MCP
 */
class Test_GetRestoredSize extends TestCase {

	/**
	 * Invokes the protected get_restored_size() method.
	 *
	 * @param object $process A process double exposing get_media().
	 * @return int
	 */
	private function invoke_get_restored_size( $process ): int {
		$method = new ReflectionMethod( RestoreMedia::class, 'get_restored_size' );
		$method->setAccessible( true );

		return $method->invoke( new RestoreMedia(), $process );
	}

	/**
	 * Builds a process double whose media returns the given path.
	 *
	 * @param mixed $media The media double or false.
	 * @return object
	 */
	private function make_process( $media ) {
		return new class( $media ) {
			private $media;

			public function __construct( $media ) {
				$this->media = $media;
			}

			public function get_media() {
				return $this->media;
			}
		};
	}

	/**
	 * Builds a media double returning the given raw original path.
	 *
	 * @param mixed $path The path or false.
	 * @return object
	 */
	private function make_media( $path ) {
		return new class( $path ) {
			private $path;

			public function __construct( $path ) {
				$this->path = $path;
			}

			public function get_raw_original_path() {
				return $this->path;
			}
		};
	}

	/**
	 * Tests that 0 is returned when the process has no media.
	 */
	public function testReturnsZeroWhenNoMedia(): void {
		$this->assertSame( 0, $this->invoke_get_restored_size( $this->make_process( false ) ) );
	}

	/**
	 * Tests that 0 is returned when the media has no original path.
	 */
	public function testReturnsZeroWhenNoPath(): void {
		$process = $this->make_process( $this->make_media( false ) );

		$this->assertSame( 0, $this->invoke_get_restored_size( $process ) );
	}

	/**
	 * Tests that 0 is returned when the file does not exist.
	 */
	public function testReturnsZeroWhenFileDoesNotExist(): void {
		$process = $this->make_process( $this->make_media( '/nonexistent/imagify-test-file.jpg' ) );

		$this->assertSame( 0, $this->invoke_get_restored_size( $process ) );
	}

	/**
	 * Tests that the file size in bytes is returned for an existing file.
	 */
	public function testReturnsFileSizeForExistingFile(): void {
		$path = tempnam( sys_get_temp_dir(), 'imagify' );
		file_put_contents( $path, 'twelve bytes' );

		$process = $this->make_process( $this->make_media( $path ) );

		$this->assertSame( 12, $this->invoke_get_restored_size( $process ) );

		unlink( $path );
	}
}
