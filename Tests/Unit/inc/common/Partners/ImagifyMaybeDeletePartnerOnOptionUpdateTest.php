<?php
declare( strict_types=1 );

namespace Imagify\Tests\Unit\inc\common\Partners;

use Brain\Monkey\Functions;
use Imagify\Tests\Unit\TestCase;

/**
 * @group Partners
 * @covers ::imagify_maybe_delete_partner_on_option_update
 */
class ImagifyMaybeDeletePartnerOnOptionUpdateTest extends TestCase {
	public static function set_up_before_class(): void {
		parent::set_up_before_class();

		require_once IMAGIFY_PLUGIN_ROOT . 'inc/common/partners.php';
	}

	/**
	 * @dataProvider configTestData
	 *
	 * @runInSeparateProcess
	 */
	public function testShouldDoExpected( $old_value, $new_value, $partner, $expected ) {
		if ( false === $expected ) {
			Functions\expect( 'imagify_get_partner' )
				->never();

			Functions\expect( 'imagify_save_partner_hide_our_plugins' )
				->never();

			Functions\expect( 'imagify_delete_partner' )
				->never();
		} else {
			Functions\expect( 'imagify_get_partner' )
				->once()
				->andReturn( $partner );

			Functions\expect( 'imagify_save_partner_hide_our_plugins' )
				->once()
				->with( $partner );

			Functions\expect( 'imagify_delete_partner' )
				->once();
		}

		imagify_maybe_delete_partner_on_option_update( $old_value, $new_value );
	}
}
