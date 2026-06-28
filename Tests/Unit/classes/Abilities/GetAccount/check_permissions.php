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
	 * Tests that check_permissions() returns true when the user has the manage_options capability.
	 */
	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new GetAccount();
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when the user lacks the manage_options capability.
	 */
	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new GetAccount();
		$this->assertFalse( $ability->check_permissions() );
	}
}
