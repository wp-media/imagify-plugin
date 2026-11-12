<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Media\Subscriber;

use Brain\Monkey\Functions;
use Imagify\Media\Subscriber;
use Imagify\Media\Upload\Upload;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Media\Subscriber::filter_missing_nextgen_query().
 *
 * @covers \Imagify\Media\Subscriber::filter_missing_nextgen_query
 * @group  Media
 * @group  MissingNextgenFilter
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class FilterMissingNextgenQueryTest extends TestCase {

	/**
	 * Tests filter_missing_nextgen_query() against a variety of guard/format scenarios.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The scenario configuration (see the fixture file).
	 * @param array $expected The expected outcome (see the fixture file).
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		Functions\when( 'is_admin' )->justReturn( $config['is_admin'] );
		Functions\when( 'imagify_nextgen_images_formats' )->justReturn( $config['formats'] );
		// Mirrors the real imagify_get_mime_types(): an extension => mime type map, with the PDF
		// entry present unless the 'image' type is requested.
		Functions\when( 'imagify_get_mime_types' )->alias(
			function ( $type = null ) {
				$mimes = [
					'jpg|jpeg|jpe' => 'image/jpeg',
					'png'          => 'image/png',
					'gif'          => 'image/gif',
					'webp'         => 'image/webp',
				];

				if ( 'image' !== $type ) {
					$mimes['pdf'] = 'application/pdf';
				}

				return $mimes;
			}
		);
		Functions\when( 'sanitize_key' )->alias( 'strtolower' );

		if ( array_key_exists( 'get_status', $config ) && null !== $config['get_status'] ) {
			$_GET['imagify-status'] = $config['get_status'];
			Functions\when( 'sanitize_text_field' )->returnArg();
			Functions\when( 'wp_unslash' )->returnArg();
		} else {
			unset( $_GET['imagify-status'] );
		}

		$views = Mockery::mock( 'alias:Imagify_Views' );
		$views->shouldReceive( 'get_instance' )->andReturn( $views );

		if ( $config['is_admin'] ) {
			// is_wp_library_page() is only consulted once is_admin()/is_main_query() already passed,
			// matching the guard order in filter_missing_nextgen_query().
			$views->shouldReceive( 'is_wp_library_page' )->andReturn( $config['is_wp_library_page'] );
		} else {
			$views->shouldNotReceive( 'is_wp_library_page' );
		}

		$query = Mockery::mock( 'alias:WP_Query' );
		$query->shouldReceive( 'is_main_query' )->andReturn( $config['is_main_query'] );

		$meta_query_set      = false;
		$post_in_set         = false;
		$mime_type_set       = false;
		$captured_meta_query = null;
		$captured_mime_type  = null;

		$existing_post_mime_type = $config['existing_post_mime_type'] ?? null;

		$query->shouldReceive( 'get' )
			->with( 'post_mime_type' )
			->andReturn( $existing_post_mime_type );

		$query->shouldReceive( 'set' )
			->andReturnUsing(
				function ( $key, $value ) use ( &$meta_query_set, &$post_in_set, &$mime_type_set, &$captured_meta_query, &$captured_mime_type ) {
					if ( 'meta_query' === $key ) {
						$meta_query_set      = true;
						$captured_meta_query = $value;
					}
					if ( 'post__in' === $key ) {
						$post_in_set = true;
					}
					if ( 'post_mime_type' === $key ) {
						$mime_type_set      = true;
						$captured_mime_type = $value;
					}
				}
			);

		$subscriber = new Subscriber( new Upload() );
		$subscriber->filter_missing_nextgen_query( $query );

		$this->assertSame( $expected['meta_query_set'], $meta_query_set );
		$this->assertSame( $expected['post_in_set'], $post_in_set );
		$this->assertSame( $expected['mime_type_set'], $mime_type_set );

		if ( $expected['meta_query_set'] ) {
			$this->assertSame( 'AND', $captured_meta_query['relation'] );
			$this->assertCount( 3, array_filter( $captured_meta_query, 'is_array' ) );

			$status_clause = $captured_meta_query[0];
			$this->assertSame( '_imagify_status', $status_clause['key'] );
			$this->assertSame( [ 'success', 'already_optimized' ], $status_clause['value'] );
			$this->assertSame( 'IN', $status_clause['compare'] );

			$success_clause = $captured_meta_query[1];
			$this->assertSame( '_imagify_data', $success_clause['key'] );
			$this->assertSame( 'NOT LIKE', $success_clause['compare'] );
			$this->assertStringStartsWith( $expected['suffix'], $success_clause['value'] );

			$permanent_error_clause = $captured_meta_query[2];
			$this->assertSame( '_imagify_data', $permanent_error_clause['key'] );
			$this->assertSame( 'NOT LIKE', $permanent_error_clause['compare'] );
			$this->assertStringStartsWith( $expected['suffix'], $permanent_error_clause['value'] );
		}

		if ( $mime_type_set ) {
			// A PDF has no next-gen version, so it can never be missing one.
			$this->assertNotContains( 'application/pdf', $captured_mime_type );

			// An image that already is the target format is not missing that format.
			$this->assertNotContains( $expected['excluded_mime'], $captured_mime_type );

			$this->assertContains( 'image/jpeg', $captured_mime_type );
		}
	}
}
