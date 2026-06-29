<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetStats;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetStats;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetStats::check_permissions().
 *
 * @covers \Imagify\Abilities\GetStats::check_permissions
 * @group  GetStats
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when the user has the manage_options capability.
	 */
	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new GetStats();
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when the user lacks the manage_options capability.
	 */
	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new GetStats();
		$this->assertFalse( $ability->check_permissions() );
	}
}
