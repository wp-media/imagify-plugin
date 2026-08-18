<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Media\WP;

use Imagify\Media\WP;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Media\WP::keep_companion_files() — WordPress 7.1 can keep the file an
 * upload started from next to the one it serves, a HEIC beside its JPEG or a GIF beside its
 * video, and records the name in the attachment metadata. Regenerating thumbnails replaces
 * that metadata wholesale, and wp_generate_attachment_metadata() never produces those keys,
 * so they have to be carried over or wp_delete_attachment_files() can never clean the files up.
 *
 * @covers \Imagify\Media\WP::keep_companion_files
 * @group  MediaWP
 * @since  2.3.3
 */
class KeepCompanionFilesTest extends TestCase {

	/**
	 * Expose the helper, which is internal to the class.
	 *
	 * @param  array $metadata          Freshly generated metadata.
	 * @param  mixed $previous_metadata Metadata stored before regeneration.
	 * @return array
	 */
	private function keepCompanionFiles( $metadata, $previous_metadata ): array {
		/*
		 * The helper works purely on the two arrays it is handed, so the constructor is
		 * skipped: it would reach for the post and the filesystem for nothing.
		 */
		$media  = ( new \ReflectionClass( WP::class ) )->newInstanceWithoutConstructor();
		$method = new \ReflectionMethod( WP::class, 'keep_companion_files' );
		$method->setAccessible( true );

		return $method->invoke( $media, $metadata, $previous_metadata );
	}

	/**
	 * Test: every companion key WordPress 7.1 records is carried over.
	 */
	public function testCarriesOverEveryCompanionKey(): void {
		$previous = [
			'file'                  => 'old.jpg',
			'source_image'          => 'photo.heic',
			'animated_video'        => 'animation.mp4',
			'animated_video_poster' => 'animation-poster.jpg',
		];

		$result = $this->keepCompanionFiles( [ 'file' => 'new.jpg' ], $previous );

		$this->assertSame(
			[
				'file'                  => 'new.jpg',
				'source_image'          => 'photo.heic',
				'animated_video'        => 'animation.mp4',
				'animated_video_poster' => 'animation-poster.jpg',
			],
			$result
		);
	}

	/**
	 * Test: a companion key the fresh metadata already carries is not overwritten.
	 */
	public function testDoesNotOverwriteAKeyAlreadyPresent(): void {
		$result = $this->keepCompanionFiles(
			[ 'source_image' => 'kept.heic' ],
			[ 'source_image' => 'stale.heic' ]
		);

		$this->assertSame( [ 'source_image' => 'kept.heic' ], $result );
	}

	/**
	 * Test: metadata without companion keys is returned as it was, with nothing invented.
	 */
	public function testLeavesMetadataAloneWhenThereIsNothingToCarryOver(): void {
		$metadata = [
			'file'  => 'image.jpg',
			'sizes' => [ 'thumbnail' => [ 'file' => 'image-150x150.jpg' ] ],
		];

		$this->assertSame( $metadata, $this->keepCompanionFiles( $metadata, [ 'file' => 'old.jpg' ] ) );
	}

	/**
	 * Test: an attachment with no metadata yet is handled, since wp_get_attachment_metadata()
	 * returns false in that case on WP 7.1.
	 */
	public function testHandlesPreviousMetadataThatIsNotAnArray(): void {
		$metadata = [ 'file' => 'image.jpg' ];

		$this->assertSame( $metadata, $this->keepCompanionFiles( $metadata, false ) );
		$this->assertSame( $metadata, $this->keepCompanionFiles( $metadata, null ) );
		$this->assertSame( $metadata, $this->keepCompanionFiles( $metadata, '' ) );
	}

	/**
	 * Test: an empty companion value is not carried over, so a cleared key stays cleared.
	 */
	public function testIgnoresEmptyCompanionValues(): void {
		$result = $this->keepCompanionFiles( [ 'file' => 'image.jpg' ], [ 'source_image' => '' ] );

		$this->assertSame( [ 'file' => 'image.jpg' ], $result );
	}
}
