<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetAccount;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetAccount;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetAccount::check_permissions().
 *
 * @covers \Imagify\Abilities\GetAccount::check_permissions
 * @group  GetAccount
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when current_user_can('manage_options') is true.
	 */
	public function testReturnsTrueWhenCurrentUserCanManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( true );

		$ability = new GetAccount();

		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when current_user_can('manage_options') is false.
	 */
	public function testReturnsFalseWhenCurrentUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( false );

		$ability = new GetAccount();

		$this->assertFalse( $ability->check_permissions() );
	}
}
