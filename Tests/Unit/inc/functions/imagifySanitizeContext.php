<?php

namespace Imagify\Tests\Unit\Functions;

use Brain\Monkey;
use Imagify\Tests\Unit\TestCase;

class Test_ImagifySanitizeContext extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/common.php';
	}

	/**
	 * Test should return sanitized key.
	 */
	public function testShouldReturnSanitizedKey() {
		$data = [
			'httpsimagifyio' => 'https://imagify.io/',
			'wpmediaimagify' => 'WPMedia Imagify'
		];
		foreach ( $data as $expected => $value ) {
			Monkey\Functions\expect( 'sanitize_key' )
				->once()
				->with( $value )
				->andReturn( $expected );
			$this->assertSame( $expected, imagify_sanitize_context( $value ) );
		}
	}
}
