<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\Functions;

use Brain\Monkey;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for imagify_translate_api_message().
 *
 * @covers imagify_translate_api_message
 * @group  NextGenPermanentError
 */
class Test_ImagifyTranslateApiMessage extends TestCase {

	/**
	 * Sets up the translation stubs.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		Monkey\Functions\stubTranslationFunctions();
	}

	/**
	 * Test: the lowercase AVIF refusal returned by the API is translated.
	 */
	public function testShouldTranslateLowercaseAvifRefusal() {
		$this->assertSame(
			'AVIF file is larger than the original image',
			imagify_translate_api_message( 'Avif is less performant than original' )
		);
	}

	/**
	 * Test: the uppercase AVIF refusal returned by the API is translated the same way.
	 */
	public function testShouldTranslateUppercaseAvifRefusal() {
		$this->assertSame(
			'AVIF file is larger than the original image',
			imagify_translate_api_message( 'AVIF is less performant than original' )
		);
	}

	/**
	 * Test: surrounding dots and spaces do not prevent the AVIF message from being matched.
	 */
	public function testShouldTranslateAvifRefusalSurroundedByDotsAndSpaces() {
		$this->assertSame(
			'AVIF file is larger than the original image',
			imagify_translate_api_message( ' AVIF is less performant than original. ' )
		);
	}

	/**
	 * Test: the WebP counterpart keeps its own wording.
	 */
	public function testShouldTranslateWebpRefusalWithItsOwnWording() {
		$this->assertSame(
			'WebP file is larger than the original image',
			imagify_translate_api_message( 'Webp is less performant than original' )
		);
	}

	/**
	 * Test: an unknown message is returned untouched.
	 */
	public function testShouldReturnUnknownMessageUnchanged() {
		$this->assertSame(
			'Some message the map does not know',
			imagify_translate_api_message( 'Some message the map does not know' )
		);
	}

	/**
	 * Test: an empty message falls back to the unknown error wording rather than staying empty.
	 */
	public function testShouldFallBackToUnknownErrorForAnEmptyMessage() {
		$this->assertStringContainsString(
			'An unknown error occurred',
			imagify_translate_api_message( '' )
		);
	}
}
