<?php

namespace Imagify\Tests\Unit\Abilities\UpdateSettings;

use Imagify\Abilities\UpdateSettings;
use WP_UnitTestCase;
use WP_User;

/**
 * Unit tests for UpdateSettings::check_permissions()
 *
 * @covers Imagify\Abilities\UpdateSettings::check_permissions()
 */
class CheckPermissionsTest extends WP_UnitTestCase {

	/**
	 * Test that admin user can execute ability.
	 */
	public function test_admin_user_has_permission() {
		$admin_user = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $admin_user );

		$ability = new UpdateSettings();
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Test that non-admin user cannot execute ability.
	 */
	public function test_non_admin_user_denied_permission() {
		$subscriber_user = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_user );

		$ability = new UpdateSettings();
		$this->assertFalse( $ability->check_permissions() );
	}

	/**
	 * Test that unauthenticated user cannot execute ability.
	 */
	public function test_unauthenticated_user_denied_permission() {
		wp_set_current_user( 0 );

		$ability = new UpdateSettings();
		$this->assertFalse( $ability->check_permissions() );
	}
}
