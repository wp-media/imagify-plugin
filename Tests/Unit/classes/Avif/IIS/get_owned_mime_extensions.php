<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\classes\Avif\IIS;

use Imagify\Avif\IIS;
use Imagify\Tests\Unit\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Tests for \Imagify\Avif\IIS::get_owned_mime_extensions().
 *
 * @covers \Imagify\Avif\IIS::get_owned_mime_extensions
 * @group  WriteFile
 * @group  IIS
 */
class Test_GetOwnedMimeExtensions extends TestCase {

	/**
	 * The class must declare `.avif` as its owned MIME extension, so
	 * AbstractIISDirConfFile::insert_contents() can dedupe it by @fileExtension.
	 */
	public function testShouldOwnTheAvifExtension() {
		$sut = ( new ReflectionClass( IIS::class ) )->newInstanceWithoutConstructor();

		$ref = new ReflectionMethod( IIS::class, 'get_owned_mime_extensions' );
		$ref->setAccessible( true );

		$this->assertSame( [ '.avif' ], $ref->invoke( $sut ) );
	}
}
