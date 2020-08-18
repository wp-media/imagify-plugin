<?php
namespace Imagify\Tests\Unit\inc\classes\ImagifySettings;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;
use Imagify_Settings;

/**
 * @covers Imagify_Settings::update_site_option_on_network
 *
 * @uses   imagify_check_nonce()
 * @uses   imagify_die()
 * @uses   imagify_maybe_redirect()
 *
 * @group  ImagifyAPI
 */
class Test_UpdateSiteOptionOnNetwork extends TestCase {

	/**
	 * @dataProvider provideData
	 */
	public function shouldUpdateNetworkSettings( $config, $expected) {
		var_dump( $config, $expected );
	}

	public function provideData() {
		return $this->loadTestDataConfig();
	}
}
