<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\MediaResolver;

use Imagify\Abilities\MediaResolver;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers \Imagify\Abilities\MediaResolver::resolve_id
 *
 * @group Abilities
 * @group MediaResolver
 */
class ResolveIdTest extends TestCase {

	protected $useApi = false;

	/**
	 * Create an attachment whose `_wp_attached_file` meta is a known path.
	 *
	 * @param string $relative_path Path relative to the uploads directory.
	 * @return int Attachment ID.
	 */
	private function create_attachment( string $relative_path ): int {
		$attachment_id = self::factory()->attachment->create(
			[
				'file'           => $relative_path,
				'post_mime_type' => 'image/jpeg',
			]
		);

		// Guard the fixture itself: the whole resolver relies on this meta value.
		$this->assertSame(
			$relative_path,
			get_post_meta( $attachment_id, '_wp_attached_file', true ),
			'Fixture should store the expected _wp_attached_file meta.'
		);

		return $attachment_id;
	}

	public function testShouldReturnMediaIdWhenProvided(): void {
		$attachment_id = $this->create_attachment( '2026/08/hero.jpg' );

		$this->assertSame( $attachment_id, MediaResolver::resolve_id( [ 'media_id' => $attachment_id ] ) );
	}

	public function testShouldPreferMediaIdOverOtherIdentifiers(): void {
		$wanted = $this->create_attachment( '2026/08/wanted.jpg' );
		$this->create_attachment( '2026/08/other.jpg' );

		$resolved = MediaResolver::resolve_id(
			[
				'media_id'       => $wanted,
				'media_filename' => 'other.jpg',
			]
		);

		$this->assertSame( $wanted, $resolved );
	}

	public function testShouldResolveByFilename(): void {
		$attachment_id = $this->create_attachment( '2026/08/hero-banner.jpg' );

		$this->assertSame( $attachment_id, MediaResolver::resolve_id( [ 'media_filename' => 'hero-banner.jpg' ] ) );
	}

	public function testShouldResolveByFilenameIgnoringCase(): void {
		$attachment_id = $this->create_attachment( '2026/08/Hero-Banner.jpg' );

		$this->assertSame( $attachment_id, MediaResolver::resolve_id( [ 'media_filename' => 'hero-banner.JPG' ] ) );
	}

	public function testShouldResolveByFilenameGivenAsRelativePath(): void {
		$attachment_id = $this->create_attachment( '2026/08/hero.jpg' );

		$this->assertSame( $attachment_id, MediaResolver::resolve_id( [ 'media_filename' => '2026/08/hero.jpg' ] ) );
	}

	/**
	 * A `LIKE` match alone would also return `my-hero.jpg` and `hero.jpg.bak`,
	 * which would optimize or restore the wrong file.
	 */
	public function testShouldNotMatchFilenamesThatMerelyContainTheRequestedName(): void {
		$wanted = $this->create_attachment( '2026/08/hero.jpg' );
		$this->create_attachment( '2026/08/my-hero.jpg' );
		$this->create_attachment( '2026/08/hero.jpg.bak' );

		$this->assertSame( $wanted, MediaResolver::resolve_id( [ 'media_filename' => 'hero.jpg' ] ) );
	}

	public function testShouldReturnErrorWhenFilenameMatchesSeveralAttachments(): void {
		$first  = $this->create_attachment( '2026/07/hero.jpg' );
		$second = $this->create_attachment( '2026/08/hero.jpg' );

		$result = MediaResolver::resolve_id( [ 'media_filename' => 'hero.jpg' ] );

		$this->assertWPError( $result );
		$this->assertSame( 'imagify_ambiguous_media_filename', $result->get_error_code() );

		// The caller needs the candidate IDs to be able to retry unambiguously.
		$this->assertStringContainsString( (string) $first, $result->get_error_message() );
		$this->assertStringContainsString( (string) $second, $result->get_error_message() );
	}

	/**
	 * A bare file name shared by more attachments than the resolver is willing
	 * to list must be reported, not resolved. Narrowing the query to a single
	 * page of candidates and picking the lone exact match inside that page
	 * would act on an arbitrary attachment.
	 */
	public function testShouldReportAmbiguityBeyondTheCandidateCap(): void {
		for ( $i = 0; $i <= MediaResolver::MAX_CANDIDATES; $i++ ) {
			$this->create_attachment( sprintf( '2026/%02d/hero.jpg', $i + 1 ) );
		}

		$result = MediaResolver::resolve_id( [ 'media_filename' => 'hero.jpg' ] );

		$this->assertWPError( $result );
		$this->assertSame( 'imagify_ambiguous_media_filename', $result->get_error_code() );
	}

	/**
	 * Supplying the directory is the documented way out of that ambiguity.
	 */
	public function testShouldResolveAmbiguousFilenameWhenGivenItsDirectory(): void {
		$wanted = $this->create_attachment( '2026/08/hero.jpg' );
		$this->create_attachment( '2025/01/hero.jpg' );

		$this->assertWPError( MediaResolver::resolve_id( [ 'media_filename' => 'hero.jpg' ] ) );
		$this->assertSame( $wanted, MediaResolver::resolve_id( [ 'media_filename' => '2026/08/hero.jpg' ] ) );
	}

	/**
	 * The anchored match must not treat a longer directory as a match: asking
	 * for `08/hero.jpg` should not be satisfied by `2026/08/hero.jpg` only by
	 * accident of the suffix, nor should a partial segment match.
	 */
	public function testShouldNotMatchPartialPathSegments(): void {
		$this->create_attachment( '2026/08/hero.jpg' );

		$result = MediaResolver::resolve_id( [ 'media_filename' => '6/08/hero.jpg' ] );

		$this->assertWPError( $result );
		$this->assertSame( 'imagify_media_not_found', $result->get_error_code() );
	}

	public function testShouldReturnErrorWhenFilenameMatchesNothing(): void {
		$result = MediaResolver::resolve_id( [ 'media_filename' => 'nope.jpg' ] );

		$this->assertWPError( $result );
		$this->assertSame( 'imagify_media_not_found', $result->get_error_code() );
	}

	public function testShouldResolveByUrl(): void {
		$attachment_id = $this->create_attachment( '2026/08/hero.jpg' );

		$url = wp_get_upload_dir()['baseurl'] . '/2026/08/hero.jpg';

		$this->assertSame( $attachment_id, MediaResolver::resolve_id( [ 'media_url' => $url ] ) );
	}

	/**
	 * A next-gen or thumbnail URL has no attachment of its own, so the resolver
	 * peels the suffixes off and matches the original file name.
	 *
	 * @dataProvider derivativeUrlProvider
	 */
	public function testShouldResolveDerivativeUrlByFallingBackToFilename( string $derivative ): void {
		$attachment_id = $this->create_attachment( '2026/08/hero.jpg' );

		$url = wp_get_upload_dir()['baseurl'] . '/2026/08/' . $derivative;

		$this->assertSame( $attachment_id, MediaResolver::resolve_id( [ 'media_url' => $url ] ) );
	}

	public function derivativeUrlProvider(): array {
		return [
			'webp next-gen version'   => [ 'hero.jpg.webp' ],
			'avif next-gen version'   => [ 'hero.jpg.avif' ],
			'generated thumbnail'     => [ 'hero-300x200.jpg' ],
			'next-gen of a thumbnail' => [ 'hero-300x200.jpg.webp' ],
		];
	}

	public function testShouldReturnErrorWhenUrlMatchesNothing(): void {
		$result = MediaResolver::resolve_id( [ 'media_url' => 'https://example.com/wp-content/uploads/2026/08/absent.jpg' ] );

		$this->assertWPError( $result );
		$this->assertSame( 'imagify_media_not_found', $result->get_error_code() );
	}

	/**
	 * @dataProvider emptyIdentifierProvider
	 */
	public function testShouldReturnErrorWhenNoIdentifierIsUsable( array $args ): void {
		$result = MediaResolver::resolve_id( $args );

		$this->assertWPError( $result );
		$this->assertSame( 'imagify_missing_media_identifier', $result->get_error_code() );
	}

	public function emptyIdentifierProvider(): array {
		return [
			'no argument at all'   => [ [] ],
			'zero media_id'        => [ [ 'media_id' => 0 ] ],
			'negative media_id'    => [ [ 'media_id' => -5 ] ],
			'blank media_url'      => [ [ 'media_url' => '   ' ] ],
			'blank media_filename' => [ [ 'media_filename' => '' ] ],
		];
	}

	public function testShouldExposeFilenameAndUrlInputSchemaProperties(): void {
		$properties = MediaResolver::get_input_schema_properties();

		$this->assertSame( [ 'media_filename', 'media_url' ], array_keys( $properties ) );
		$this->assertSame( 'string', $properties['media_filename']['type'] );
		$this->assertSame( 'string', $properties['media_url']['type'] );
	}
}
