<?php

namespace Imagify\Tests\Integration\Functions;

use Imagify\Tests\Integration\TestCase;

class Test_ImagifyGetMimeTypes extends TestCase {
	protected $useApi = false;

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

	public function testShouldReturnNotImageMimeTypesOnly() {
		$this->assertSame(
			[ 'pdf' => 'application/pdf' ],
			imagify_get_mime_types( 'not-image' )
		);
	}

	public function testShouldApplyMimeTypesFilter() {
		$callback = function ( $mimes ) {
			$mimes['avif'] = 'image/avif';
			unset( $mimes['pdf'] );

			return $mimes;
		};

		add_filter( 'imagify_get_mime_types', $callback );

		$mimes = imagify_get_mime_types();

		remove_filter( 'imagify_get_mime_types', $callback );

		$this->assertSame( 'image/avif', $mimes['avif'] );
		$this->assertArrayNotHasKey( 'pdf', $mimes );
	}
}
