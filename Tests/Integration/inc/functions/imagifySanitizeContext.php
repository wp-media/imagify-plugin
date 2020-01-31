<?php

namespace Imagify\Tests\Integration\Functions;

use Imagify\Tests\Integration\TestCase;

class Test_ImagifySanitizeContext extends TestCase {
	protected $useApi = false;

	public function testShouldReturnSanitizedKey() {
		$this->assertSame( 'httpsimagifyio', imagify_sanitize_context( 'https://imagify.io/' ) );
		$this->assertSame( 'wpmediaimagify', imagify_sanitize_context( 'WPMedia Imagify' ) );
	}
}
