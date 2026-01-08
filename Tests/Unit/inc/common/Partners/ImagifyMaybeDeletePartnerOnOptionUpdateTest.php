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
	public static function set_up_before_class() {
		parent::set_up_before_class();

		require_once IMAGIFY_PLUGIN_ROOT . 'inc/common/partners.php';
	}

	/**
	 * @dataProvider configTestData
	 *
	 * @runInSeparateProcess
	 */
	public function testShouldDoExpected( $old_value, $new_value, $partner, $expected ) {

	}
}
