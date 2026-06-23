<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetMediaStatus;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetMediaStatus;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetMediaStatus::check_permissions().
 *
 * @covers \Imagify\Abilities\GetMediaStatus::check_permissions
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

		$ability = new GetMediaStatus();
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

		$ability = new GetMediaStatus();
		$this->assertFalse( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() casts a non-boolean truthy value to bool.
	 */
	public function testCastsTruthyValueToBool(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( 1 );

		$ability = new GetMediaStatus();
		$result  = $ability->check_permissions();

		$this->assertIsBool( $result );
		$this->assertTrue( $result );
	}
}
