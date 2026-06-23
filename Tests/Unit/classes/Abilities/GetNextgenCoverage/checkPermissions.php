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
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when current user has manage_options.
	 */
	public function testReturnsTrueWhenUserHasManageOptions(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		$stat    = Mockery::mock( StatInterface::class );
		$ability = new GetNextgenCoverage( $stat );

		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when current user lacks manage_options.
	 */
	public function testReturnsFalseWhenUserLacksManageOptions(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( false );

		$stat    = Mockery::mock( StatInterface::class );
		$ability = new GetNextgenCoverage( $stat );

		$this->assertFalse( $ability->check_permissions() );
	}
}
