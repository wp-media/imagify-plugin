<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\UpdateSettings;

use Brain\Monkey\Functions;
use Imagify\Abilities\UpdateSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\UpdateSettings::check_permissions().
 *
 * @covers \Imagify\Abilities\UpdateSettings::check_permissions
 * @group  UpdateSettings
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when current_user_can('manage_options') is true.
	 */
	public function testReturnsTrueWhenCurrentUserCanManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( true );

		$ability = new UpdateSettings();

		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when current_user_can('manage_options') is false.
	 */
	public function testReturnsFalseWhenCurrentUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( false );

		$ability = new UpdateSettings();

		$this->assertFalse( $ability->check_permissions() );
	}
}
