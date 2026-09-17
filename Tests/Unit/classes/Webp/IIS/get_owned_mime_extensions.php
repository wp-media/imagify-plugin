<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\classes\Webp\IIS;

use Imagify\Tests\Unit\TestCase;
use Imagify\Webp\IIS;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for \Imagify\Webp\IIS::get_owned_mime_extensions().
 *
 * @covers \Imagify\Webp\IIS::get_owned_mime_extensions
 * @group  WriteFile
 * @group  IIS
 */
class Test_GetOwnedMimeExtensions extends TestCase {

	/**
	 * The class must declare `.webp` as its owned MIME extension, so
	 * AbstractIISDirConfFile::insert_contents() can dedupe it by @fileExtension.
	 */
	public function testShouldOwnTheWebpExtension() {
		$sut = ( new ReflectionClass( IIS::class ) )->newInstanceWithoutConstructor();

		$ref = new ReflectionMethod( IIS::class, 'get_owned_mime_extensions' );
		$ref->setAccessible( true );

		$this->assertSame( [ '.webp' ], $ref->invoke( $sut ) );
	}
}
