<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GenerateMissingNextgen;

use Brain\Monkey\Functions;
use Imagify\Abilities\GenerateMissingNextgen;
use Imagify\Bulk\Bulk;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GenerateMissingNextgen::check_permissions().
 *
 * Because `Bulk` is `final`, Mockery cannot mock it. We construct the ability
 * with a real `Bulk::get_instance()` — no Bulk methods are called by
 * `check_permissions()`, so no additional stubbing is needed.
 *
 * @covers \Imagify\Abilities\GenerateMissingNextgen::check_permissions
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Returns a GenerateMissingNextgen instance for permission checks.
	 *
	 * No Bulk methods are invoked by check_permissions(), so a real
	 * Bulk instance suffices.
	 *
	 * @return GenerateMissingNextgen
	 */
	private function make_ability(): GenerateMissingNextgen {
		return new GenerateMissingNextgen( Bulk::get_instance() );
	}

	/**
	 * Tests that check_permissions() returns true when the user has manage_options (AC #4).
	 */
	public function testReturnsTrueWhenUserHasManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( true );

		$this->assertTrue( $this->make_ability()->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when the user lacks manage_options (AC #4).
	 */
	public function testReturnsFalseWhenUserLacksManageOptions(): void {
		Functions\when( 'current_user_can' )
			->justReturn( false );

		$this->assertFalse( $this->make_ability()->check_permissions() );
	}

	/**
	 * Tests that check_permissions() calls current_user_can with 'manage_options'.
	 */
	public function testCallsCurrentUserCanWithManageOptions(): void {
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'manage_options' )
			->andReturn( true );

		$this->make_ability()->check_permissions();
	}
}
