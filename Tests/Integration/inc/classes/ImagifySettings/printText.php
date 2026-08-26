<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\inc\classes\ImagifySettings;

use Imagify_Settings;
use Imagify\Tests\Integration\TestCase;

/**
 * @covers Imagify_Settings::print_text
 *
 * @group  ImagifySettings
 */
class Test_PrintText extends TestCase {

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldPrintTheExpectedMarkup( $config, $expected ) {
		ob_start();
		Imagify_Settings::print_text( $config['text'] );
		$output = ob_get_clean();

		$this->assertSame( $expected, $output );
	}
}
