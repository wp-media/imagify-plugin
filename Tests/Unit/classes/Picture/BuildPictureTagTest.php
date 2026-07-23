<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Picture;

use Brain\Monkey\Functions;
use Imagify\Picture\Display;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;

/**
 * Tests for \Imagify\Picture\Display::build_picture_tag().
 *
 * @covers \Imagify\Picture\Display::build_picture_tag
 * @group  PictureDisplayBuildPictureTag
 * @since  2.3.1
 */
class BuildPictureTagTest extends TestCase {

	/**
	 * Set up the test environment.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'esc_attr' )->returnArg();
	}

	/**
	 * Get an instance of Display without invoking the constructor (its only
	 * dependency, Imagify_Filesystem, is not needed by build_picture_tag()).
	 *
	 * @return Display
	 */
	private function get_display_instance(): Display {
		$reflection = new ReflectionClass( Display::class );

		return $reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Build a minimal $image array as expected by build_picture_tag(), build_source_tag()
	 * and build_img_tag(). $attributes is the raw <img> attributes array.
	 *
	 * @param array $attributes The <img> attributes.
	 *
	 * @return array
	 */
	private function get_image_data( array $attributes ): array {
		return [
			'attributes'       => $attributes,
			'src_attribute'    => 'src',
			'srcset_attribute' => false,
			'srcset'           => [],
			'src'              => [],
		];
	}

	/**
	 * Extract the opening <picture ...> tag's attribute string from the built output.
	 *
	 * @param string $output The output of build_picture_tag().
	 *
	 * @return string
	 */
	private function get_picture_opening_tag( string $output ): string {
		preg_match( '/^<picture([^>]*)>/', $output, $matches );

		return $matches[1] ?? '';
	}

	/**
	 * Extract the <img .../> tag's attribute string from the built output.
	 *
	 * @param string $output The output of build_picture_tag().
	 *
	 * @return string
	 */
	private function get_img_tag( string $output ): string {
		preg_match( '/<img([^>]*)\/>/', $output, $matches );

		return $matches[1] ?? '';
	}

	/**
	 * `data-wp-*` directive attributes must be filtered out of the <picture> tag,
	 * but kept on the inner <img> tag.
	 */
	public function testDataWpAttributesAreExcludedFromPictureTagButKeptOnImgTag(): void {
		$attributes = [
			'data-object-fit'     => 'cover',
			'data-wp-interactive' => 'core/image',
			'data-wp-context'     => '{"isOpen":false}',
			'data-wp-on--click'   => 'actions.showLightbox',
		];

		$image = $this->get_image_data( $attributes );

		$method = $this->get_reflective_method( 'build_picture_tag', Display::class );
		$output = $method->invoke( $this->get_display_instance(), $image );

		$picture_tag = $this->get_picture_opening_tag( $output );
		$img_tag     = $this->get_img_tag( $output );

		$this->assertStringNotContainsString( 'data-wp-interactive', $picture_tag );
		$this->assertStringNotContainsString( 'data-wp-context', $picture_tag );
		$this->assertStringNotContainsString( 'data-wp-on--click', $picture_tag );

		$this->assertStringContainsString( 'data-wp-interactive="core/image"', $img_tag );
		$this->assertStringContainsString( 'data-wp-context="{"isOpen":false}"', $img_tag );
		$this->assertStringContainsString( 'data-wp-on--click="actions.showLightbox"', $img_tag );
	}

	/**
	 * Non `data-wp` attributes are unaffected by the new filter and keep behaving
	 * as they did before: they remain on the <picture> tag, and follow the
	 * pre-existing removal rules on the <img> tag.
	 */
	public function testNonDataWpAttributesAreUnaffected(): void {
		$attributes = [
			'data-object-fit' => 'cover',
			'class'           => 'my-image',
			'id'              => 'img-1',
		];

		$image = $this->get_image_data( $attributes );

		$method = $this->get_reflective_method( 'build_picture_tag', Display::class );
		$output = $method->invoke( $this->get_display_instance(), $image );

		$picture_tag = $this->get_picture_opening_tag( $output );
		$img_tag     = $this->get_img_tag( $output );

		// All 3 attributes remain on the <picture> tag, since none of them contain 'data-wp'.
		$this->assertStringContainsString( 'data-object-fit="cover"', $picture_tag );
		$this->assertStringContainsString( 'class="my-image"', $picture_tag );
		$this->assertStringContainsString( 'id="img-1"', $picture_tag );

		// Pre-existing logic (unrelated to this fix) strips class/id from the <img> tag,
		// but keeps data-object-fit.
		$this->assertStringContainsString( 'data-object-fit="cover"', $img_tag );
		$this->assertStringNotContainsString( 'class=', $img_tag );
		$this->assertStringNotContainsString( 'id=', $img_tag );
	}

	/**
	 * The new `data-wp` filter composes correctly with the pre-existing
	 * 'wp-block-cover__image-background' exclusion logic: both sets of
	 * attributes are removed from the <picture> tag, while the <img> tag
	 * (which is treated as a Gutenberg cover background image) keeps
	 * everything, including the `data-wp` attributes.
	 */
	public function testDataWpFilterComposesWithGutenbergCoverExclusion(): void {
		$attributes = [
			'class'                => 'wp-block-cover__image-background',
			'style'                => 'object-fit:cover',
			'data-object-fit'      => 'cover',
			'data-object-position' => '50% 50%',
			'data-wp-interactive'  => 'core/image',
			'data-wp-context'      => '{}',
		];

		$image = $this->get_image_data( $attributes );

		$method = $this->get_reflective_method( 'build_picture_tag', Display::class );
		$output = $method->invoke( $this->get_display_instance(), $image );

		$picture_tag = $this->get_picture_opening_tag( $output );
		$img_tag     = $this->get_img_tag( $output );

		// None of the Gutenberg-specific attributes, nor the data-wp attributes, remain on <picture>.
		$this->assertStringNotContainsString( 'class=', $picture_tag );
		$this->assertStringNotContainsString( 'style=', $picture_tag );
		$this->assertStringNotContainsString( 'data-object-fit', $picture_tag );
		$this->assertStringNotContainsString( 'data-object-position', $picture_tag );
		$this->assertStringNotContainsString( 'data-wp-interactive', $picture_tag );
		$this->assertStringNotContainsString( 'data-wp-context', $picture_tag );
		$this->assertSame( '', trim( $picture_tag ) );

		// The <img> tag, being the Gutenberg cover background image, keeps everything
		// (only 'id' and 'title' are ever stripped from it in that branch).
		$this->assertStringContainsString( 'class="wp-block-cover__image-background"', $img_tag );
		$this->assertStringContainsString( 'style="object-fit:cover"', $img_tag );
		$this->assertStringContainsString( 'data-object-fit="cover"', $img_tag );
		$this->assertStringContainsString( 'data-object-position="50% 50%"', $img_tag );
		$this->assertStringContainsString( 'data-wp-interactive="core/image"', $img_tag );
		$this->assertStringContainsString( 'data-wp-context="{}"', $img_tag );
	}
}
