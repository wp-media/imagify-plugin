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

	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new GetStats();
		$this->assertTrue( $ability->check_permissions() );
	}

	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new GetStats();
		$this->assertFalse( $ability->check_permissions() );
	}
}
