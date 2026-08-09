<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Optimization\File;

use Imagify\Optimization\File;
use Imagify\Tests\Unit\TestCase;
use Mockery;
use WP_Error;

/**
 * Tests for \Imagify\Optimization\File::maybe_correct_exif_orientation().
 *
 * @covers \Imagify\Optimization\File::maybe_correct_exif_orientation
 * @covers \Imagify\Optimization\File::rotate_editor_by_exif_orientation
 * @group  Optimization
 */
class MaybeCorrectExifOrientationTest extends TestCase {

	/**
	 * Path used for the fake file under test.
	 *
	 * @var string
	 */
	private $path = '/tmp/fake-image@imagify-tmp.jpg';

	/**
	 * Builds a File instance with a mocked filesystem (and optionally editor) injected, bypassing
	 * WordPress/filesystem globals.
	 *
	 * @param \Mockery\MockInterface $filesystem Mock for the Imagify_Filesystem dependency.
	 * @param mixed                  $editor     Optional. Value to inject as the cached editor.
	 * @param object|null            $file_type  Optional. Value to inject as the cached file type.
	 *
	 * @return File
	 */
	private function make_file( $filesystem, $editor = null, $file_type = null ): File {
		// Bypass the real constructor: it instantiates Imagify_Filesystem::get_instance(), which
		// requires a full WordPress environment that isn't available in unit tests.
		$file = ( new \ReflectionClass( File::class ) )->newInstanceWithoutConstructor();

		$this->setPropertyValue( 'path', $file, $this->path );
		$this->setPropertyValue( 'filesystem', $file, $filesystem );

		if ( null !== $file_type ) {
			$this->setPropertyValue( 'file_type', $file, $file_type );
		} else {
			$this->setPropertyValue(
				'file_type',
				$file,
				(object) [
					'ext'  => 'jpg',
					'type' => 'image/jpeg',
				]
			);
		}

		if ( null !== $editor ) {
			$this->setPropertyValue( 'editor', $file, $editor );
		}

		return $file;
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( $config['can_get_exif'] );

		if ( $expected['get_image_exif_called'] ) {
			$filesystem->shouldReceive( 'get_image_exif' )
				->with( $this->path )
				->andReturn( [ 'Orientation' => $config['orientation'] ] );
		} else {
			$filesystem->shouldNotReceive( 'get_image_exif' );
		}

		$editor     = null;
		$save_error = null;

		if ( 'valid' === $config['editor'] ) {
			$editor = Mockery::mock();

			if ( null !== $expected['rotate'] ) {
				$editor->shouldReceive( 'rotate' )->once()->with( $expected['rotate'] )->andReturn( true );
			} else {
				$editor->shouldNotReceive( 'rotate' );
			}

			if ( null !== $expected['flip'] ) {
				$editor->shouldReceive( 'flip' )->once()->with( ...$expected['flip'] );
			} else {
				$editor->shouldNotReceive( 'flip' );
			}

			if ( $expected['save_called'] ) {
				if ( 'error' === $config['save'] ) {
					$save_error = new WP_Error( 'image_save_error', 'Could not save.' );
					$editor->shouldReceive( 'save' )->once()->with( $this->path )->andReturn( $save_error );
				} else {
					$editor->shouldReceive( 'save' )->once()->with( $this->path )->andReturn( [ 'path' => $this->path ] );
				}
			} else {
				$editor->shouldNotReceive( 'save' );
			}
		} elseif ( 'error' === $config['editor'] ) {
			$editor = new WP_Error( 'image_editor', 'No editor available.' );
		}

		$file_type = isset( $config['file_type'] ) ? (object) $config['file_type'] : null;
		$file      = $this->make_file( $filesystem, $editor, $file_type );

		$result = $file->maybe_correct_exif_orientation();

		switch ( $expected['result'] ) {
			case 'true':
				$this->assertTrue( $result );
				break;

			case 'false':
				$this->assertFalse( $result );
				break;

			case 'wp_error':
				$this->assertInstanceOf( WP_Error::class, $result );
				// The very same WP_Error instance must be surfaced, unchanged.
				$this->assertSame( 'error' === $config['editor'] ? $editor : $save_error, $result );
				break;
		}
	}
}
