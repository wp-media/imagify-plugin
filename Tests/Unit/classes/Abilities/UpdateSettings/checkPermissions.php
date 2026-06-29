<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\UpdateSettings;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\UpdateSettings;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Abilities\UpdateSettings::check_permissions
 * @group  UpdateSettings
 */
class Test_CheckPermissions extends TestCase {

	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertTrue( ( new UpdateSettings() )->check_permissions() );
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
			->with( 'imagify/update-settings', 'Update Imagify settings', 'manage' );

		$this->assertFalse( ( new UpdateSettings() )->check_permissions() );
	}
}
