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
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when user has manage_options.
	 */
	public function testReturnsTrueWhenUserHasManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( true );

		$ability = new GetStats();

		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when user lacks manage_options.
	 */
	public function testReturnsFalseWhenUserLacksManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( false );

		$ability = new GetStats();

		$this->assertFalse( $ability->check_permissions() );
	}
}
