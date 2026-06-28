<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\classes\WriteFile\AbstractWriteDirConfFile;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify\WriteFile\AbstractWriteDirConfFile;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\WriteFile\AbstractWriteDirConfFile::get_file_contents().
 *
 * @covers \Imagify\WriteFile\AbstractWriteDirConfFile::get_file_contents
 * @group  WriteFile
 */
class GetFileContentsTest extends TestCase {

	/**
	 * Build a partial mock with a configurable filesystem stub.
	 *
	 * @param bool   $file_exists    Whether the filesystem should report the file as existing.
	 * @param mixed  $file_contents  The return value of get_contents() — false simulates an unreadable file.
	 * @return AbstractWriteDirConfFile&\Mockery\MockInterface
	 */
	private function buildSut( bool $file_exists = true, $file_contents = 'content' ) {
		$filesystem = new class( $file_exists, $file_contents ) {
			private bool $file_exists;
			/** @var mixed */
			private $file_contents;

			public function __construct( bool $file_exists, $file_contents ) {
				$this->file_exists    = $file_exists;
				$this->file_contents  = $file_contents;
			}

			public function make_path_relative( string $path ): string {
				return basename( $path );
			}

			public function exists( string $path ): bool {
				return $this->file_exists;
			}

			public function get_contents( string $path ) {
				return $this->file_contents;
			}
		};

		/** @var AbstractWriteDirConfFile&\Mockery\MockInterface $sut */
		$sut = Mockery::mock( AbstractWriteDirConfFile::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		$reflection = new \ReflectionProperty( AbstractWriteDirConfFile::class, 'filesystem' );
		$reflection->setAccessible( true );
		$reflection->setValue( $sut, $filesystem );

		return $sut;
	}

	/**
	 * Invoke the protected get_file_contents() method on a given instance.
	 *
	 * @param AbstractWriteDirConfFile $sut
	 * @return mixed
	 */
	private function invokeGetFileContents( $sut ) {
		$method = new \ReflectionMethod( AbstractWriteDirConfFile::class, 'get_file_contents' );
		$method->setAccessible( true );
		return $method->invoke( $sut );
	}

	/**
	 * Test that get_file_contents() returns a WP_Error with a valid (non-empty) message
	 * containing the file name when the file cannot be read — verifying that $file_name
	 * is correctly assigned so no undefined variable PHP Warning is triggered.
	 */
	public function testShouldReturnWpErrorWithValidMessageWhenFileCannotBeRead() {
		$sut = $this->buildSut( true, false );

		$sut->shouldReceive( 'is_file_writable' )
			->once()
			->andReturn( true );

		$sut->shouldReceive( 'get_file_path' )
			->once()
			->andReturn( '/var/www/.htaccess' );

		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'esc_html' )->returnArg( 1 );

		$result = $this->invokeGetFileContents( $sut );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'not_read', $result->get_error_code() );
		$this->assertStringContainsString( '.htaccess', $result->get_error_message() );
	}

	/**
	 * Test that get_file_contents() returns a WP_Error when is_file_writable() fails.
	 */
	public function testShouldReturnWpErrorWhenNotWritable() {
		$writable_error = new WP_Error( 'not_writable', 'Not writable.' );

		// We only need the minimal filesystem (it won't be called in this code path).
		$sut = $this->buildSut();

		$sut->shouldReceive( 'is_file_writable' )
			->once()
			->andReturn( $writable_error );

		$result = $this->invokeGetFileContents( $sut );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'not_writable', $result->get_error_code() );
	}

	/**
	 * Test that get_file_contents() returns the file contents as a string on success.
	 */
	public function testShouldReturnContentsOnSuccess() {
		$sut = $this->buildSut( true, 'file contents' );

		$sut->shouldReceive( 'is_file_writable' )
			->once()
			->andReturn( true );

		$sut->shouldReceive( 'get_file_path' )
			->once()
			->andReturn( '/var/www/.htaccess' );

		$result = $this->invokeGetFileContents( $sut );

		$this->assertSame( 'file contents', $result );
	}

	/**
	 * Test that get_file_contents() returns an empty string when the file does not exist.
	 */
	public function testShouldReturnEmptyStringWhenFileDoesNotExist() {
		$sut = $this->buildSut( false );

		$sut->shouldReceive( 'is_file_writable' )
			->once()
			->andReturn( true );

		$sut->shouldReceive( 'get_file_path' )
			->once()
			->andReturn( '/var/www/.htaccess' );

		$result = $this->invokeGetFileContents( $sut );

		$this->assertSame( '', $result );
	}
}
