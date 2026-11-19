<?php
declare(strict_types=1);

namespace Imagify\Tests\Integration\classes\Abilities\GetAccount;

use Imagify\Tests\Integration\TestCase;

/**
 * A network-wide ability stays reserved to the `manage` capability: a site admin of a
 * network-activated install is denied.
 *
 * @covers \Imagify\Abilities\GetAccount::check_permissions
 * @group  Abilities
 */
class CheckPermissionsTest extends TestCase {

	protected $useApi = false;

	public function set_up() {
		parent::set_up();

		if ( version_compare( $GLOBALS['wp_version'], '6.9', '<' ) ) {
			$this->markTestSkipped( 'WordPress 6.9+ required for Abilities API.' );
		}
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		parent::tear_down();
	}

	/**
	 * Test: the ability is denied (WP_Error) or runs (array) for the given site type and user.
	 *
	 * @dataProvider configTestData
	 *
	 * @param array $config   The site type required (see skipUnlessSite()) and whether the admin is a super admin.
	 * @param array $expected Whether the call is denied.
	 */
	public function testShouldReturnExpected( $config, $expected ): void {
		$this->skipUnlessSite( $config['site'] );

		$user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );

		if ( $config['super_admin'] && is_multisite() ) {
			grant_super_admin( $user_id );
		}

		wp_set_current_user( $user_id );

		$result = wp_get_ability( 'imagify/get-account' )->execute();

		if ( $expected['is_denied'] ) {
			$this->assertInstanceOf( 'WP_Error', $result );
			return;
		}

		$this->assertIsArray( $result );
	}
}
