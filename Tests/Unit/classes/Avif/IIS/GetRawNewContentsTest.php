<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\classes\Avif\IIS;

use Imagify\Avif\IIS;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for \Imagify\Avif\IIS::get_raw_new_contents().
 *
 * @covers \Imagify\Avif\IIS::get_raw_new_contents
 * @covers \Imagify\Avif\IIS::get_owned_mime_extensions
 * @group  WriteFile
 * @group  IIS
 */
class GetRawNewContentsTest extends TestCase {

	/**
	 * Instantiate the concrete IIS class without running its constructor
	 * (which would need a WordPress filesystem), then invoke a protected method.
	 *
	 * @param string $method Protected method to invoke.
	 * @return mixed
	 */
	private function invokeProtected( string $method ) {
		$sut = ( new ReflectionClass( IIS::class ) )->newInstanceWithoutConstructor();

		$ref = new ReflectionMethod( IIS::class, $method );
		$ref->setAccessible( true );

		return $ref->invoke( $sut );
	}

	/**
	 * The emitted fragment must target the single shared <staticContent>
	 * collection and NOT wrap the mimeMap in its own <staticContent>.
	 */
	public function testShouldTargetStaticContentParentWithLeafMimeMap() {
		$contents = $this->invokeProtected( 'get_raw_new_contents' );

		$this->assertStringContainsString( '<!-- @parent /configuration/system.webServer/staticContent -->', $contents );
		$this->assertStringContainsString( '<mimeMap fileExtension=".avif" mimeType="image/avif" />', $contents );
	}

	/**
	 * The fragment must not emit a wrapping <staticContent> element nor a
	 * non-schema `name` attribute (Option B, issue #509).
	 */
	public function testShouldNotEmitWrappingStaticContentNorNameAttribute() {
		$contents = $this->invokeProtected( 'get_raw_new_contents' );

		$this->assertStringNotContainsString( '<staticContent', $contents );
		$this->assertStringNotContainsString( 'name=', $contents );
	}

	/**
	 * The class must declare `.avif` as its owned MIME extension for deduping.
	 */
	public function testShouldOwnTheAvifExtension() {
		$this->assertSame( [ '.avif' ], $this->invokeProtected( 'get_owned_mime_extensions' ) );
	}
}
