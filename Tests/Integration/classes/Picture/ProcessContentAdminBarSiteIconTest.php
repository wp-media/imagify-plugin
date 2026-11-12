<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Picture;

use Imagify\Picture\Display;
use Imagify\Tests\Integration\TestCase;

/**
 * Tests for \Imagify\Picture\Display::process_content() with the admin bar site icon.
 *
 * @covers \Imagify\Picture\Display::process_content
 * @group  PictureDisplay
 * @since  2.3.6
 */
class ProcessContentAdminBarSiteIconTest extends TestCase {
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
			if ( file_exists( $path ) ) {
				unlink( $path );
			}
		}

		$this->created_files = [];

		parent::tear_down();
	}

	/**
	 * Creates a fake image and its WebP counterpart in the uploads directory.
	 *
	 * @param string $name File name, with extension.
	 *
	 * @return string The image URL.
	 */
	private function create_image( string $name ): string {
		$uploads = wp_get_upload_dir();

		wp_mkdir_p( $uploads['basedir'] );

		foreach ( [ $name, $name . '.webp' ] as $file ) {
			$path = trailingslashit( $uploads['basedir'] ) . $file;

			file_put_contents( $path, 'fake' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$this->created_files[] = $path;
		}

		return trailingslashit( $uploads['baseurl'] ) . $name;
	}

	/**
	 * Tests that the admin bar site icon is left alone while content images are converted.
	 */
	public function testShouldSkipAdminBarSiteIconAndConvertContentImage() {
		$icon_url    = $this->create_image( 'imagify-test-site-icon.png' );
		$content_url = $this->create_image( 'imagify-test-content.jpg' );
		$icon_tag    = '<img class="site-icon" src="' . $icon_url . '" alt="" width="20" height="20" />';
		$content_tag = '<img class="content-image" src="' . $content_url . '" alt="" width="300" height="200" />';

		$html = '<div id="wpadminbar"><ul><li role=\'group\' id=\'wp-admin-bar-site-name\' class="menupop has-site-icon">'
			. "<a class='ab-item' aria-haspopup='true' href='/'>" . $icon_tag . 'Site</a>'
			. '<div class="ab-sub-wrapper"><ul><li><a href="/">Visit</a></li></ul></div></li></ul></div>'
			. '<main>' . $content_tag . '</main>';

		$result = ( new Display( imagify_get_filesystem() ) )->process_content( $html );

		$this->assertStringContainsString( $icon_tag, $result );
		$this->assertSame( 1, substr_count( $result, '<picture' ) );
		$this->assertStringContainsString( 'type="image/webp"', $result );
		$this->assertStringContainsString( $content_url . '.webp', $result );
		$this->assertStringNotContainsString( $icon_url . '.webp', $result );
		$this->assertMatchesRegularExpression( '#<picture[^>]*>.*' . preg_quote( $content_url . '.webp', '#' ) . '.*</picture>#s', $result );
	}
}
