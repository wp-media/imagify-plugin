<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\OptimizeMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\OptimizeMedia::check_permissions().
 *
 * @covers \Imagify\Abilities\OptimizeMedia::check_permissions
 * @group  OptimizeMedia
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true when current_user_can( 'manage_options' ) is true.
	 */
	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )
			->justReturn( true );

		$ability = new OptimizeMedia();
		$result  = $ability->check_permissions();

		$this->assertTrue( $result );
	}

	/**
	 * Tests that check_permissions() returns false when current_user_can( 'manage_options' ) is false.
	 */
	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )
			->justReturn( false );

		$ability = new OptimizeMedia();
		$result  = $ability->check_permissions();

		$this->assertFalse( $result );
	}

	/**
	 * Tests that check_permissions() calls current_user_can() exactly once with 'manage_options'.
	 */
	public function testCallsCurrentUserCanWithManageOptionsCapability(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		$ability = new OptimizeMedia();
		$ability->check_permissions();
	}
}
