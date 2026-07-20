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
	 * When EXIF data can't be read at all, nothing should happen.
	 */
	public function testReturnsFalseWhenExifIsNotAvailable(): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( false );
		$filesystem->shouldNotReceive( 'get_image_exif' );

		$file = $this->make_file( $filesystem );

		$this->assertFalse( $file->maybe_correct_exif_orientation() );
	}

	/**
	 * When the file isn't a JPEG, EXIF orientation is irrelevant.
	 */
	public function testReturnsFalseWhenNotJpeg(): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( true );
		$filesystem->shouldNotReceive( 'get_image_exif' );

		$file = $this->make_file(
			$filesystem,
			null,
			(object) [
				'ext'  => 'png',
				'type' => 'image/png',
			]
		);

		$this->assertFalse( $file->maybe_correct_exif_orientation() );
	}

	/**
	 * When the orientation is already 1 (or absent), the file must not be touched: this also
	 * guards against rotating the same working copy twice.
	 */
	public function testReturnsFalseWhenOrientationIsAlreadyOne(): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( true );
		$filesystem->shouldReceive( 'get_image_exif' )->with( $this->path )->andReturn( [ 'Orientation' => 1 ] );

		$editor = Mockery::mock();
		$editor->shouldNotReceive( 'rotate' );
		$editor->shouldNotReceive( 'flip' );
		$editor->shouldNotReceive( 'save' );

		$file = $this->make_file( $filesystem, $editor );

		$this->assertFalse( $file->maybe_correct_exif_orientation() );
	}

	/**
	 * Orientation 6 (the common "rotated 90° CW on capture" case) must rotate the editor by 270°
	 * and save the result back over the same (temporary) path.
	 */
	public function testRotatesAndSavesForOrientationSix(): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( true );
		$filesystem->shouldReceive( 'get_image_exif' )->with( $this->path )->andReturn( [ 'Orientation' => 6 ] );

		$editor = Mockery::mock();
		$editor->shouldReceive( 'rotate' )->once()->with( 270 );
		$editor->shouldNotReceive( 'flip' );
		$editor->shouldReceive( 'save' )->once()->with( $this->path )->andReturn( [ 'path' => $this->path ] );

		$file = $this->make_file( $filesystem, $editor );

		$this->assertTrue( $file->maybe_correct_exif_orientation() );
	}

	/**
	 * Orientation 5 involves a rotation followed by a flip.
	 */
	public function testRotatesAndFlipsForOrientationFive(): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( true );
		$filesystem->shouldReceive( 'get_image_exif' )->with( $this->path )->andReturn( [ 'Orientation' => 5 ] );

		$editor = Mockery::mock();
		$editor->shouldReceive( 'rotate' )->once()->with( 90 )->andReturn( true );
		$editor->shouldReceive( 'flip' )->once()->with( false, true );
		$editor->shouldReceive( 'save' )->once()->with( $this->path )->andReturn( [ 'path' => $this->path ] );

		$file = $this->make_file( $filesystem, $editor );

		$this->assertTrue( $file->maybe_correct_exif_orientation() );
	}

	/**
	 * If the editor can't be retrieved, the WP_Error must be surfaced (and nothing saved).
	 */
	public function testReturnsWpErrorWhenEditorFails(): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( true );
		$filesystem->shouldReceive( 'get_image_exif' )->with( $this->path )->andReturn( [ 'Orientation' => 6 ] );

		$error = new WP_Error( 'image_editor', 'No editor available.' );
		$file  = $this->make_file( $filesystem, $error );

		$result = $file->maybe_correct_exif_orientation();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $error, $result );
	}

	/**
	 * If saving the rotated file fails, the WP_Error must be surfaced.
	 */
	public function testReturnsWpErrorWhenSaveFails(): void {
		$filesystem = Mockery::mock();
		$filesystem->shouldReceive( 'can_get_exif' )->andReturn( true );
		$filesystem->shouldReceive( 'get_image_exif' )->with( $this->path )->andReturn( [ 'Orientation' => 6 ] );

		$save_error = new WP_Error( 'image_save_error', 'Could not save.' );

		$editor = Mockery::mock();
		$editor->shouldReceive( 'rotate' )->once()->with( 270 );
		$editor->shouldReceive( 'save' )->once()->with( $this->path )->andReturn( $save_error );

		$file = $this->make_file( $filesystem, $editor );

		$result = $file->maybe_correct_exif_orientation();

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( $save_error, $result );
	}
}
