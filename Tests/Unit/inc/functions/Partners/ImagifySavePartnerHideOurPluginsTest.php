<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\inc\functions\Partners;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;

/**
 * @group Partners
 * @covers ::imagify_save_partner_hide_our_plugins
 */
class ImagifySavePartnerHideOurPluginsTest extends TestCase {
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		require_once IMAGIFY_PLUGIN_ROOT . 'inc/functions/partners.php';
	}

	/**
	 * @dataProvider configTestData
	 */
	public function testShouldDoExpected( $partner, $expected ) {
		if ( false === $expected ) {
			Functions\expect( 'update_option' )
				->never();
		} else {
			Functions\expect( 'update_option' )
				->once()
				->with( 'imagify_partner_hide_our_plugins', $expected );
		}

		imagify_save_partner_hide_our_plugins( $partner );
	}
}
