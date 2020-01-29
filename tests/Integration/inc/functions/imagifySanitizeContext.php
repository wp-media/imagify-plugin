<?php

namespace Imagify\tests\Integration\Functions;

use Imagify\tests\Integration\TestCase;

class Test_ImagifySanitizeContext extends TestCase {
	/**
	 * Test should return sanitized key.
	 */
	public function testShouldReturnSanitizedKey() {
		$this->assertSame( 'httpsimagifyio', imagify_sanitize_context( 'https://imagify.io/' ) );
		$this->assertSame( 'wpmediaimagify', imagify_sanitize_context( 'WPMedia Imagify' ) );
	}
}
