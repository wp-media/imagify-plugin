<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\RestoreMedia;

use Brain\Monkey\Functions;
use Imagify\Abilities\RestoreMedia;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\RestoreMedia::check_permissions().
 *
 * @covers \Imagify\Abilities\RestoreMedia::check_permissions
 * @group  MCP
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that check_permissions() returns true for a user with manage_options.
	 */
	public function testReturnsTrueWhenUserCanManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$this->assertTrue( ( new RestoreMedia() )->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false for a user without manage_options.
	 */
	public function testReturnsFalseWhenUserCannotManageOptions(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$this->assertFalse( ( new RestoreMedia() )->check_permissions() );
	}
}
