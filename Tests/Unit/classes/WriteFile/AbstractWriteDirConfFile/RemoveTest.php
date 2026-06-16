<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\classes\WriteFile\AbstractWriteDirConfFile;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify\WriteFile\AbstractWriteDirConfFile;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\WriteFile\AbstractWriteDirConfFile::remove().
 *
 * @covers \Imagify\WriteFile\AbstractWriteDirConfFile::remove
 * @group  WriteFile
 */
class RemoveTest extends TestCase {

	/**
	 * Build a partial mock of AbstractWriteDirConfFile with a fake filesystem injected.
	 *
	 * @param callable|null $filesystem_callback Receives the filesystem mock for extra expectations.
	 * @return AbstractWriteDirConfFile&\Mockery\MockInterface
	 */
	private function buildSut( ?callable $filesystem_callback = null ) {
		// Create a minimal filesystem stub that does NOT require WordPress files.
		$filesystem = new class() {
			public function make_path_relative( string $path ): string {
				return basename( $path );
			}
		};

		if ( null !== $filesystem_callback ) {
			$filesystem_callback( $filesystem );
		}

		/** @var AbstractWriteDirConfFile&\Mockery\MockInterface $sut */
		$sut = Mockery::mock( AbstractWriteDirConfFile::class )
			->makePartial()
			->shouldAllowMockingProtectedMethods();

		// Bypass the real constructor by injecting the stub directly.
		$reflection = new \ReflectionProperty( AbstractWriteDirConfFile::class, 'filesystem' );
		$reflection->setAccessible( true );
		$reflection->setValue( $sut, $filesystem );

		return $sut;
	}

	/**
	 * Test that remove() returns a WP_Error with a valid (non-empty) message when
	 * insert_contents() fails — verifying $file_path is correctly assigned so
	 * $file_name does not trigger a PHP Warning about undefined variables.
	 */
	public function testShouldReturnWpErrorWithValidMessageOnFailure() {
		$insert_error = new WP_Error( 'add_contents_failure', 'Write failed.' );

		$sut = $this->buildSut();

		$sut->shouldReceive( 'insert_contents' )
			->once()
			->with( '' )
			->andReturn( $insert_error );

		$sut->shouldReceive( 'get_file_path' )
			->once()
			->andReturn( '/var/www/.htaccess' );

		Functions\when( '__' )->returnArg( 1 );

		$result = $sut->remove();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertNotEmpty( $result->get_error_message() );
		$this->assertStringContainsString( '.htaccess', $result->get_error_message() );
	}

	/**
	 * Test that remove() returns a WP_Error with the 'edition_disabled' code
	 * when insert_contents() returns that error code.
	 */
	public function testShouldReturnEditionDisabledErrorWhenDisabled() {
		$insert_error = new WP_Error( 'edition_disabled', 'Edition disabled.' );

		$sut = $this->buildSut();

		$sut->shouldReceive( 'insert_contents' )
			->once()
			->with( '' )
			->andReturn( $insert_error );

		$sut->shouldReceive( 'get_file_path' )
			->once()
			->andReturn( '/var/www/.htaccess' );

		Functions\when( '__' )->returnArg( 1 );

		$result = $sut->remove();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'edition_disabled', $result->get_error_code() );
	}

	/**
	 * Test that remove() returns true when insert_contents() succeeds.
	 */
	public function testShouldReturnTrueOnSuccess() {
		$sut = $this->buildSut();

		$sut->shouldReceive( 'insert_contents' )
			->once()
			->with( '' )
			->andReturn( true );

		$result = $sut->remove();

		$this->assertTrue( $result );
	}
}
