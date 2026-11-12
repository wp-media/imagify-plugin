<?php

namespace Imagify\Tests\Unit\Functions;

use Brain\Monkey\Filters;
use Imagify\Tests\Unit\TestCase;

class Test_ImagifyGetMimeTypes extends TestCase {
	/**
	 * Test should return image mime types and PDF when no type is given.
	 */
	public function testShouldReturnAllMimeTypesByDefault() {
		$this->assertSame(
			[
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png'          => 'image/png',
				'gif'          => 'image/gif',
				'webp'         => 'image/webp',
				'pdf'          => 'application/pdf',
			],
			imagify_get_mime_types()
		);
	}

	/**
	 * Test should return only image mime types for the image type.
	 */
	public function testShouldReturnImageMimeTypesOnly() {
		$this->assertSame(
			[
				'jpg|jpeg|jpe' => 'image/jpeg',
				'png'          => 'image/png',
				'gif'          => 'image/gif',
				'webp'         => 'image/webp',
			],
			imagify_get_mime_types( 'image' )
		);
	}

	/**
	 * Test should return only PDF for the not-image type.
	 */
	public function testShouldReturnNotImageMimeTypesOnly() {
		$this->assertSame(
			[ 'pdf' => 'application/pdf' ],
			imagify_get_mime_types( 'not-image' )
		);
	}

	/**
	 * Test should pass the mime types through the imagify_get_mime_types filter.
	 */
	public function testShouldApplyMimeTypesFilter() {
		Filters\expectApplied( 'imagify_get_mime_types' )
			->once()
			->andReturn( [ 'avif' => 'image/avif' ] );

		$this->assertSame( [ 'avif' => 'image/avif' ], imagify_get_mime_types() );
	}

	/**
	 * Test should discard malformed entries returned by the filter.
	 */
	public function testShouldDiscardMalformedFilteredMimeTypes() {
		Filters\expectApplied( 'imagify_get_mime_types' )
			->once()
			->andReturn(
				[
					'avif'      => 'image/avif',
					'bad-bool'  => true,
					'bad-array' => [ 'image/png' ],
					'not-mime'  => 'image',
					0           => 'image/gif',
					''          => 'image/jpeg',
				]
			);

		$this->assertSame( [ 'avif' => 'image/avif' ], imagify_get_mime_types() );
	}
}
