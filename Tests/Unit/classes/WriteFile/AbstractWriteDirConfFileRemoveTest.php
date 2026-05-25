<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\WriteFile;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify\WriteFile\AbstractWriteDirConfFile;
use WP_Error;

/**
 * Tests for AbstractWriteDirConfFile::remove().
 *
 * Covers:
 *   - Bug #883: undefined variable $file_path in remove() when insert_contents() returns WP_Error.
 *
 * @covers \Imagify\WriteFile\AbstractWriteDirConfFile::remove
 * @group  WriteFile
 */
class AbstractWriteDirConfFileRemoveTest extends TestCase {

	/**
	 * Build a concrete stub of the abstract class with a controllable insert_contents() result.
	 *
	 * @param bool|WP_Error $insert_result Value that insert_contents() will return.
	 * @return AbstractWriteDirConfFile
	 */
	private function makeStub( $insert_result ): AbstractWriteDirConfFile {
		return new class( $insert_result ) extends AbstractWriteDirConfFile {

			/**
			 * The value insert_contents() will return.
			 *
			 * @var bool|WP_Error
			 */
			private $insert_result;

			/**
			 * Constructor — bypasses parent to avoid Imagify_Filesystem instantiation.
			 *
			 * @param bool|WP_Error $insert_result Value insert_contents() will return.
			 */
			public function __construct( $insert_result ) {
				// Bypass parent::__construct() which calls Imagify_Filesystem::get_instance().
				$this->filesystem    = new class() {
					/**
					 * Return a relative path for any given absolute path.
					 *
					 * @param string $path Absolute path.
					 * @return string
					 */
					public function make_path_relative( string $path ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
						return '.htaccess';
					}
				};
				$this->insert_result = $insert_result;
			}

			/**
			 * Return the insert_contents() result set at construction time.
			 *
			 * @param string $new_contents Contents to insert.
			 * @return bool|WP_Error
			 */
			protected function insert_contents( $new_contents ) {
				return $this->insert_result;
			}

			/**
			 * Return the raw path to the conf file.
			 *
			 * @return string
			 */
			protected function get_raw_file_path(): string {
				return '/var/www/.htaccess';
			}

			/**
			 * Return empty raw contents.
			 *
			 * @return string
			 */
			protected function get_raw_new_contents(): string {
				return '';
			}

			/**
			 * Return the (non-filtered) path to the conf file.
			 *
			 * @return string
			 */
			public function get_file_path(): string {
				return '/var/www/.htaccess';
			}
		};
	}

	/**
	 * Remove() returns true when insert_contents() succeeds.
	 */
	public function testRemoveReturnsTrueOnSuccess(): void {
		$stub   = $this->makeStub( true );
		$result = $stub->remove();

		$this->assertTrue( $result );
	}

	/**
	 * Remove() returns a WP_Error with code 'edition_disabled' when that error is propagated,
	 * without triggering a PHP Warning for undefined variable $file_path (Bug #883).
	 */
	public function testRemoveReturnsEditionDisabledErrorWithoutWarning(): void {
		Functions\when( '__' )->returnArg( 1 );

		$inner  = new WP_Error( 'edition_disabled', 'Edition is disabled.' );
		$stub   = $this->makeStub( $inner );
		$result = $stub->remove();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'edition_disabled', $result->get_error_code() );
		// The error message must reference the filename from the filesystem stub.
		$this->assertStringContainsString( '.htaccess', $result->get_error_message() );
	}

	/**
	 * Remove() returns a WP_Error with code 'add_contents_failure' for other write errors,
	 * without triggering a PHP Warning for undefined variable $file_path (Bug #883).
	 */
	public function testRemoveReturnsWriteFailureErrorWithoutWarning(): void {
		Functions\when( '__' )->returnArg( 1 );

		$inner  = new WP_Error( 'not_writable', 'File is not writable.' );
		$stub   = $this->makeStub( $inner );
		$result = $stub->remove();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'add_contents_failure', $result->get_error_code() );
		// The error message must reference the filename from the filesystem stub.
		$this->assertStringContainsString( '.htaccess', $result->get_error_message() );
	}
}
