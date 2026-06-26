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

	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new OptimizeMedia();
		$this->assertTrue( $ability->check_permissions() );
	}

	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new OptimizeMedia();
		$this->assertFalse( $ability->check_permissions() );
	}
}
