<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Picture\Display;

use Imagify\Picture\Display;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\Picture\Display::process_content().
 *
 * @covers \Imagify\Picture\Display::process_content
 * @group  PictureDisplay
 * @since  2.3.6
 */
class ProcessContentTest extends TestCase {
	/**
	 * No API calls here.
	 *
	 * @var bool
	 */
	protected $useApi = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Absolute paths created by the test, removed in tear_down().
	 *
	 * @var array
	 */
	protected $created_files = [];

	/**
	 * Removes the files created by the test.
	 */
	public function tear_down() {
		foreach ( $this->created_files as $path ) {
			wp_delete_file( $path );
		}

		$this->created_files = [];

		parent::tear_down();
	}

	/**
	 * Tests process_content() wraps images in <picture> tags, except the admin bar site icon.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   Test configuration.
	 * @param array $expected Expected results.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$uploads = wp_get_upload_dir();

		wp_mkdir_p( $uploads['basedir'] );

		// Each image gets a fake file and its WebP counterpart, so it is eligible for a <picture> tag.
		foreach ( $config['files'] as $file ) {
			foreach ( [ $file, $file . '.webp' ] as $name ) {
				$path = trailingslashit( $uploads['basedir'] ) . $name;

				file_put_contents( $path, 'fake' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
				$this->created_files[] = $path;
			}
		}

		$uploads_url = untrailingslashit( $uploads['baseurl'] );
		$html        = str_replace( '{{uploads}}', $uploads_url, $config['html'] );

		$result = ( new Display( imagify_get_filesystem() ) )->process_content( $html );

		$this->assertSame( $expected['picture_count'], substr_count( $result, '<picture' ) );

		foreach ( $expected['unchanged'] as $tag ) {
			$this->assertStringContainsString( str_replace( '{{uploads}}', $uploads_url, $tag ), $result );
		}

		foreach ( $expected['converted'] as $file ) {
			$this->assertMatchesRegularExpression(
				'#<picture[^>]*>\s*<source type="image/webp" srcset="' . preg_quote( "{$uploads_url}/{$file}.webp", '#' ) . '"#',
				$result
			);
		}
	}
}
