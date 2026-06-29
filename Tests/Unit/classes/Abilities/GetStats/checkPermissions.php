<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\GetStats;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\GetStats;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Abilities\GetStats::check_permissions
 * @group  GetStats
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that get_id() returns the ability slug.
	 */
	public function testGetIdReturnsAbilitySlug(): void {
		$this->assertSame( 'imagify/get-stats', ( new GetStats() )->get_id() );
	}

	/**
	 * Tests that get_name() returns the human-readable ability label.
	 */
	public function testGetNameReturnsAbilityLabel(): void {
		$this->assertSame( 'Get Imagify optimization stats', ( new GetStats() )->get_name() );
	}

	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertTrue( ( new GetStats() )->check_permissions() );
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
			->with( 'imagify/get-stats', 'Get Imagify optimization stats', 'manage' );

		$this->assertFalse( ( new GetStats() )->check_permissions() );
	}
}
