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
 * @group  WriteFile
 * @group  IIS
 */
class Test_GetRawNewContents extends TestCase {

	/**
	 * Instantiate the concrete IIS class without running its constructor
	 * (which would need a WordPress filesystem), then invoke the protected method.
	 *
	 * @return string
	 */
	private function getRawNewContents(): string {
		$sut = ( new ReflectionClass( IIS::class ) )->newInstanceWithoutConstructor();

		$ref = new ReflectionMethod( IIS::class, 'get_raw_new_contents' );
		$ref->setAccessible( true );

		return $ref->invoke( $sut );
	}

	/**
	 * The emitted fragment must target the single shared <staticContent> collection
	 * with a leaf <mimeMap> — no wrapping <staticContent> nor non-schema `name`
	 * attribute (Option B, issue #509).
	 */
	public function testShouldTargetStaticContentParentWithLeafMimeMap() {
		$contents = $this->getRawNewContents();

		$this->assertStringContainsString( '<!-- @parent /configuration/system.webServer/staticContent -->', $contents );
		$this->assertStringContainsString( '<mimeMap fileExtension=".avif" mimeType="image/avif" />', $contents );
		$this->assertStringNotContainsString( '<staticContent', $contents );
		$this->assertStringNotContainsString( 'name=', $contents );
	}
}
