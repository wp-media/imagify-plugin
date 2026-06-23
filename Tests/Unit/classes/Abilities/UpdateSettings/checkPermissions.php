<?php

namespace Imagify\Tests\Unit\Abilities\UpdateSettings;

use Brain\Monkey\Functions;
use Imagify\Abilities\UpdateSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * Unit tests for UpdateSettings::check_permissions()
 *
 * @covers \Imagify\Abilities\UpdateSettings::check_permissions()
 */
class CheckPermissionsTest extends TestCase {

	/**
	 * Test that user with manage_options capability can execute ability.
	 */
	public function test_admin_user_has_permission() {
		$ability = new UpdateSettings();

		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Test that user without manage_options capability cannot execute ability.
	 */
	public function test_non_admin_user_denied_permission() {
		$ability = new UpdateSettings();

		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		$this->assertFalse( $ability->check_permissions() );
	}
}
