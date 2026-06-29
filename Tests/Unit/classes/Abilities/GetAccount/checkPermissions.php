<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetAccount;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\GetAccount;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Abilities\GetAccount::check_permissions
 * @group  GetAccount
 */
class Test_CheckPermissions extends TestCase {

	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertTrue( ( new GetAccount() )->check_permissions() );
	}

	public function testReturnsFalseWhenContextDenies(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return false;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );
		Actions\expectDone( 'imagify_mcp_permission_denied' )
			->once()
			->with( 'imagify/get-account', 'Get Imagify account status', 'manage' );

		$this->assertFalse( ( new GetAccount() )->check_permissions() );
	}
}
