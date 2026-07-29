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

	/**
	 * Tests that check_permissions() returns true when the context allows.
	 */
	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$ability = new GetSettings();
		$this->assertTrue( $ability->check_permissions() );
	}

	/**
	 * Tests that check_permissions() returns false when the context denies.
	 */
	public function testReturnsFalseWhenContextDenies(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return false;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$ability = new GetSettings();
		$this->assertFalse( $ability->check_permissions() );
	}
}
