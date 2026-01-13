<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\classes\ThirdParty\Hostings\Extendify;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify\ThirdParty\Hostings\Extendify;

class HidePluginFamilyTest extends TestCase {
	/**
	 * @dataProvider configTestData
	 */
	public function testShouldReturnExpected( $value, $option, $expected ) {
		$extendify = new Extendify();

		Functions\when( 'get_option' )
			->justReturn( $option );

		$this->assertSame(
			$expected,
			$extendify->hide_plugin_family( $value )
		);
	}
}
