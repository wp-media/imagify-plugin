<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\MediaResolver;

use Brain\Monkey\Functions;
use Imagify\Abilities\MediaResolver;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\MediaResolver::resolve_id().
 *
 * The filename-lookup path is exercised via MediaResolver::set_query_factory()
 * so the production class can be tested without touching the real WP_Query
 * constructor. The URL path is exercised via Brain\Monkey stubs for
 * `esc_url_raw()` and `attachment_url_to_postid()`.
 *
 * @covers \Imagify\Abilities\MediaResolver
 * @group  MCP
 */
class Test_ResolveId extends TestCase {

	/**
	 * Stub i18n functions so MediaResolver's __() calls do not fatal.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Functions\stubTranslationFunctions();
	}

	/**
	 * Reset the test-only query factory after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		MediaResolver::set_query_factory( null );
		parent::tearDown();
	}

	/**
	 * Inject a fake query factory that returns an object exposing the
	 * given posts array.
	 *
	 * @param array $posts Value returned by the fake query's ->posts property.
	 * @return void
	 */
	private function fake_query_with_posts( array $posts ): void {
		$obj       = new \stdClass();
		$obj->posts = $posts;

		MediaResolver::set_query_factory(
			static function () use ( $obj ) {
				return $obj;
			}
		);
	}

	// ---------------------------------------------------------------------
	// media_id passthrough
	// ---------------------------------------------------------------------

	public function testMediaIdPassthrough() {
		$this->assertSame( 262, MediaResolver::resolve_id( [ 'media_id' => 262 ] ) );
		$this->assertSame( 262, MediaResolver::resolve_id( [ 'media_id' => '262' ] ) );
	}

	public function testZeroOrNegativeMediaIdIsIgnored() {
		// Zero/negative media_id should not be the source of truth — fall through
		// to the next identifier (or error if none).
		Functions\expect( 'esc_url_raw' )->never();
		Functions\expect( 'attachment_url_to_postid' )->never();

		$result = MediaResolver::resolve_id( [ 'media_id' => 0 ] );
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'missing_identifier', $result->get_error_code() );
	}

	// ---------------------------------------------------------------------
	// media_url
	// ---------------------------------------------------------------------

	public function testMediaUrlResolution() {
		Functions\expect( 'esc_url_raw' )
			->once()
			->with( 'https://example.com/wp-content/uploads/2024/01/hero.jpg' )
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/hero.jpg' );

		Functions\expect( 'attachment_url_to_postid' )
			->once()
			->with( 'https://example.com/wp-content/uploads/2024/01/hero.jpg' )
			->andReturn( 262 );

		$this->assertSame( 262, MediaResolver::resolve_id( [ 'media_url' => 'https://example.com/wp-content/uploads/2024/01/hero.jpg' ] ) );
	}

	public function testMediaUrlReturnsErrorWhenInvalid() {
		Functions\expect( 'esc_url_raw' )
			->once()
			->andReturn( '' );

		Functions\expect( 'attachment_url_to_postid' )->never();

		$result = MediaResolver::resolve_id( [ 'media_url' => 'not-a-url' ] );
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'invalid_media_url', $result->get_error_code() );
	}

	public function testMediaUrlReturnsErrorWhenNoMatch() {
		Functions\expect( 'esc_url_raw' )->once()->andReturn( 'https://example.com/missing.jpg' );
		Functions\expect( 'attachment_url_to_postid' )
			->once()
			->andReturn( 0 );

		$result = MediaResolver::resolve_id( [ 'media_url' => 'https://example.com/missing.jpg' ] );
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'media_not_found', $result->get_error_code() );
	}

	// ---------------------------------------------------------------------
	// media_filename
	// ---------------------------------------------------------------------

	public function testMediaFilenameReturnsSingleMatch() {
		$this->fake_query_with_posts( [ 262 ] );

		Functions\expect( 'sanitize_file_name' )
			->once()
			->with( 'hero.jpg' )
			->andReturn( 'hero.jpg' );

		$this->assertSame( 262, MediaResolver::resolve_id( [ 'media_filename' => 'hero.jpg' ] ) );
	}

	public function testMediaFilenameStripsPathBeforeBasename() {
		$this->fake_query_with_posts( [ 262 ] );

		Functions\expect( 'sanitize_file_name' )
			->once()
			->with( '2024/01/hero.jpg' )
			->andReturn( '2024/01/hero.jpg' );

		$this->assertSame( 262, MediaResolver::resolve_id( [ 'media_filename' => '2024/01/hero.jpg' ] ) );
	}

	public function testMediaFilenameReturnsErrorOnNoMatch() {
		$this->fake_query_with_posts( [] );

		Functions\expect( 'sanitize_file_name' )
			->once()
			->with( 'missing.jpg' )
			->andReturn( 'missing.jpg' );

		$result = MediaResolver::resolve_id( [ 'media_filename' => 'missing.jpg' ] );
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'media_not_found', $result->get_error_code() );
	}

	public function testMediaFilenameReturnsErrorOnAmbiguousMatch() {
		$this->fake_query_with_posts( [ 10, 11, 12 ] );

		Functions\expect( 'sanitize_file_name' )
			->once()
			->with( 'shared.jpg' )
			->andReturn( 'shared.jpg' );

		$result = MediaResolver::resolve_id( [ 'media_filename' => 'shared.jpg' ] );
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'ambiguous_media_filename', $result->get_error_code() );
	}

	public function testMediaFilenameReturnsErrorWhenSanitizationEmptiesString() {
		Functions\expect( 'sanitize_file_name' )
			->once()
			->with( '../../../etc/passwd' )
			->andReturn( '' );

		$result = MediaResolver::resolve_id( [ 'media_filename' => '../../../etc/passwd' ] );
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'invalid_media_filename', $result->get_error_code() );
	}

	public function testMediaFilenameQueryFactoryReceivesExpectedArgs() {
		$captured = null;
		$obj      = new \stdClass();
		$obj->posts = [ 262 ];

		MediaResolver::set_query_factory(
			function ( $args ) use ( $obj, &$captured ) {
				$captured = $args;
				return $obj;
			}
		);

		Functions\when( 'sanitize_file_name' )->justReturn( 'hero.jpg' );

		MediaResolver::resolve_id( [ 'media_filename' => 'hero.jpg' ] );

		$this->assertNotNull( $captured );
		$this->assertSame( 'attachment', $captured['post_type'] );
		$this->assertSame( 'inherit', $captured['post_status'] );
		$this->assertSame( 'ids', $captured['fields'] );
		$this->assertSame( MediaResolver::MAX_FILENAME_CANDIDATES, $captured['posts_per_page'] );
		$this->assertTrue( $captured['no_found_rows'] );
		$this->assertSame( '_wp_attached_file', $captured['meta_query'][0]['key'] );
		$this->assertSame( 'hero.jpg', $captured['meta_query'][0]['value'] );
		$this->assertSame( 'LIKE', $captured['meta_query'][0]['compare'] );
	}

	// ---------------------------------------------------------------------
	// Missing identifier
	// ---------------------------------------------------------------------

	public function testReturnsErrorWhenNoIdentifierProvided() {
		$result = MediaResolver::resolve_id( [] );
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'missing_identifier', $result->get_error_code() );
	}

	public function testReturnsErrorWhenAllIdentifiersEmpty() {
		$result = MediaResolver::resolve_id(
			[
				'media_id'       => 0,
				'media_url'      => '',
				'media_filename' => '',
			]
		);
		$this->assertInstanceOf( "WP_Error", $result );
		$this->assertSame( 'missing_identifier', $result->get_error_code() );
	}

	// ---------------------------------------------------------------------
	// Priority: media_id wins over media_url/media_filename
	// ---------------------------------------------------------------------

	public function testMediaIdTakesPriorityOverOtherIdentifiers() {
		Functions\expect( 'attachment_url_to_postid' )->never();

		$this->assertSame(
			262,
			MediaResolver::resolve_id(
				[
					'media_id'       => 262,
					'media_url'      => 'https://example.com/other.jpg',
					'media_filename' => 'other.jpg',
				]
			)
		);
	}
}
