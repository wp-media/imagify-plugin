<?php
declare(strict_types=1);

namespace Imagify\Tests\Unit\classes\Abilities\OptimizeMedia;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Imagify\Abilities\OptimizeMedia;
use Imagify\Tests\Unit\TestCase;

/**
 * @covers \Imagify\Abilities\OptimizeMedia::check_permissions
 * @group  OptimizeMedia
 */
class Test_CheckPermissions extends TestCase {

	/**
	 * Tests that get_id() returns the ability slug.
	 */
	public function testGetIdReturnsAbilitySlug(): void {
		$this->assertSame( OptimizeMedia::ABILITY_ID, ( new OptimizeMedia() )->get_id() );
	}

	/**
	 * Tests that get_name() returns the human-readable ability label.
	 */
	public function testGetNameReturnsAbilityLabel(): void {
		$this->assertSame( OptimizeMedia::ABILITY_NAME, ( new OptimizeMedia() )->get_name() );
	}

	public function testReturnsTrueWhenContextAllows(): void {
		$context = new class {
			public function current_user_can( string $capability ): bool {
				return true;
			}
		};
		Functions\when( 'imagify_get_context' )->justReturn( $context );

		$this->assertTrue( ( new OptimizeMedia() )->check_permissions() );
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
			->with( OptimizeMedia::ABILITY_ID, OptimizeMedia::ABILITY_NAME, 'manage' );

		$this->assertFalse( ( new OptimizeMedia() )->check_permissions() );
	}
}
