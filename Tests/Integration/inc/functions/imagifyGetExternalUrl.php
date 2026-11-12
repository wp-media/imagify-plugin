<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\functions;

use Imagify\Tests\Integration\TestCase;

/**
 * Tests for imagify_get_external_url().
 *
 * @covers ::imagify_get_external_url
 *
 * @group  Functions
 */
class Test_ImagifyGetExternalUrl extends TestCase {
	/**
	 * The documentation-nextgen-delivery target is what the settings page links to
	 * when explaining the layout risk of the <picture> tag method, so its slug must
	 * not drift silently.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array  $config   Target and optional query args.
	 * @param string $expected Expected URL.
	 */
	public function testShouldReturnExpectedUrl( $config, $expected ) {
		$this->assertSame(
			$expected,
			imagify_get_external_url( $config['target'], $config['query_args'] ?? [] )
		);
	}
}
