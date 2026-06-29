<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetAccount;

use Brain\Monkey\Functions;
use Imagify\Abilities\GetAccount;
use Imagify\Tests\Unit\TestCase;

/**
 * Tests for \Imagify\Abilities\GetAccount::check_permissions().
 *
 * @covers \Imagify\Abilities\GetAccount::check_permissions
 * @group  GetAccount
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

		$ability = new GetAccount();
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

		$ability = new GetAccount();
		$this->assertFalse( $ability->check_permissions() );
	}
}
