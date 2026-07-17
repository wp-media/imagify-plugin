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
 * @covers \Imagify\Abilities\GenerateMissingNextgen::check_permissions
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true for a user with manage_options.
	 */
	public function testReturnsTrueWhenUserCanManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new GenerateMissingNextgen( Bulk::get_instance() );
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false for a user without manage_options.
	 */
	public function testReturnsFalseWhenUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new GenerateMissingNextgen( Bulk::get_instance() );
		$this->assertFalse( $ability->check_permissions() );
	}
}
