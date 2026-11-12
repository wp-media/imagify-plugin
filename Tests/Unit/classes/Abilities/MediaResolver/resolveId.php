<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\MediaResolver;

use Brain\Monkey\Functions;
use Imagify\Abilities\MediaResolver;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\MediaResolver::resolve_id().
 *
 * Covers the identifier dispatch and the URL path. The file-name path runs a
 * real `WP_Query` against the `_wp_attached_file` meta and is covered by
 * Tests/Integration/classes/Abilities/MediaResolver/ResolveIdTest.php instead.
 *
 * @covers \Imagify\Abilities\MediaResolver::resolve_id
 * @group  MCP
 */
class Test_ResolveId extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		Functions\stubTranslationFunctions();
	}

	/**
	 * Tests that a positive media_id is returned untouched.
	 */
	public function testReturnsMediaIdWhenPositive(): void {
		$this->assertSame( 42, MediaResolver::resolve_id( [ 'media_id' => 42 ] ) );
	}

	/**
	 * Tests that a numeric-string media_id is cast to an integer.
	 */
	public function testCastsNumericStringMediaId(): void {
		$this->assertSame( 42, MediaResolver::resolve_id( [ 'media_id' => '42' ] ) );
	}

	/**
	 * Tests that media_id takes precedence over the other identifiers, without
	 * spending a lookup on them.
	 */
	public function testMediaIdTakesPrecedenceOverUrl(): void {
		Functions\expect( 'attachment_url_to_postid' )->never();

		$resolved = MediaResolver::resolve_id(
			[
				'media_id'  => 42,
				'media_url' => 'https://example.com/wp-content/uploads/2026/08/hero.jpg',
			]
		);

		$this->assertSame( 42, $resolved );
	}

	/**
	 * Tests that a URL resolving to an attachment returns its ID.
	 */
	public function testResolvesUrlToAttachmentId(): void {
		Functions\expect( 'attachment_url_to_postid' )
			->once()
			->with( 'https://example.com/wp-content/uploads/2026/08/hero.jpg' )
			->andReturn( 7 );

		$resolved = MediaResolver::resolve_id( [ 'media_url' => 'https://example.com/wp-content/uploads/2026/08/hero.jpg' ] );

		$this->assertSame( 7, $resolved );
	}

	/**
	 * Tests that surrounding whitespace does not prevent a URL from resolving.
	 */
	public function testTrimsUrlBeforeResolving(): void {
		Functions\expect( 'attachment_url_to_postid' )
			->once()
			->with( 'https://example.com/hero.jpg' )
			->andReturn( 9 );

		$this->assertSame( 9, MediaResolver::resolve_id( [ 'media_url' => "  https://example.com/hero.jpg\n" ] ) );
	}

	/**
	 * Tests that an unusable identifier is reported rather than guessed at.
	 *
	 * @dataProvider unusableIdentifierProvider
	 *
	 * @param array $args Ability arguments with no usable identifier.
	 */
	public function testReturnsErrorWhenNoIdentifierIsUsable( array $args ): void {
		$resolved = MediaResolver::resolve_id( $args );

		$this->assertInstanceOf( 'WP_Error', $resolved );
		$this->assertSame( 'imagify_missing_media_identifier', $resolved->get_error_code() );
	}

	/**
	 * Argument sets carrying no usable identifier.
	 *
	 * @return array<string, array{0: array}>
	 */
	public function unusableIdentifierProvider(): array {
		return [
			'empty args'           => [ [] ],
			'zero media_id'        => [ [ 'media_id' => 0 ] ],
			'negative media_id'    => [ [ 'media_id' => -5 ] ],
			'blank media_url'      => [ [ 'media_url' => '   ' ] ],
			'blank media_filename' => [ [ 'media_filename' => '' ] ],
		];
	}

	/**
	 * Tests that the shared schema properties describe both new inputs.
	 */
	public function testExposesFilenameAndUrlSchemaProperties(): void {
		$properties = MediaResolver::get_input_schema_properties();

		$this->assertSame( [ 'media_filename', 'media_url' ], array_keys( $properties ) );
		$this->assertSame( 'string', $properties['media_filename']['type'] );
		$this->assertSame( 'string', $properties['media_url']['type'] );
		$this->assertNotEmpty( $properties['media_filename']['description'] );
		$this->assertNotEmpty( $properties['media_url']['description'] );
	}
}
