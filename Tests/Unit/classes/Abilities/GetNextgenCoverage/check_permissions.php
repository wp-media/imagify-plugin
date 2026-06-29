<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetNextgenCoverage;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetNextgenCoverage;
use Imagify\Stats\StatInterface;
use Imagify\Tests\Unit\TestCase;
use Mockery;

/**
 * Tests for \Imagify\Abilities\GetNextgenCoverage::check_permissions().
 *
 * @covers \Imagify\Abilities\GetNextgenCoverage::check_permissions
 * @group  GetNextgenCoverage
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when the user has the manage_options capability.
	 */
	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new GetNextgenCoverage( Mockery::mock( StatInterface::class ) );
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when the user lacks the manage_options capability.
	 */
	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new GetNextgenCoverage( Mockery::mock( StatInterface::class ) );
		$this->assertFalse( $ability->check_permissions() );
	}
}
