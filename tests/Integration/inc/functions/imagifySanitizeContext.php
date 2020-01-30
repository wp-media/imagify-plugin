<?php

namespace Imagify\tests\Integration\Functions;

use Imagify\tests\Integration\TestCase;

class Test_ImagifySanitizeContext extends TestCase {

	public function testShouldReturnSanitizedKey() {
		$this->assertSame( 'httpsimagifyio', imagify_sanitize_context( 'https://imagify.io/' ) );
		$this->assertSame( 'wpmediaimagify', imagify_sanitize_context( 'WPMedia Imagify' ) );
	}
}
