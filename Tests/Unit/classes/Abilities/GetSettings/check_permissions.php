<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetSettings;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetSettings::check_permissions().
 *
 * @covers \Imagify\Abilities\GetSettings::check_permissions
 * @group  GetSettings
 */
class Test_CheckPermissions extends TestCase {

	public function testReturnsTrueWhenUserHasManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( true );

		$ability = new GetSettings();
		$this->assertTrue( $ability->check_permissions() );
	}

	public function testReturnsFalseWhenUserLacksManageOptionsCapability(): void {
		Functions\when( 'current_user_can' )->justReturn( false );

		$ability = new GetSettings();
		$this->assertFalse( $ability->check_permissions() );
	}
}
